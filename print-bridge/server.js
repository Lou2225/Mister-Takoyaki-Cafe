/**
 * MTC Print Bridge — v2.0 (PowerShell edition)
 * ------------------------------------------------
 * A tiny local HTTP server that relays ESC/POS byte streams to any
 * Windows-registered printer via PowerShell's built-in Out-Printer cmdlet.
 *
 * Zero native dependencies — uses only Node.js built-ins:
 *   http, child_process, fs, os, path, crypto
 *
 * Endpoints:
 *   GET  /ping      → { ok: true, version: "2.0" }
 *   GET  /printers  → [ "Printer Name", ... ]
 *   POST /print     → { printerName, escposBase64 }  → { ok: true }
 */

'use strict';

const http        = require('http');
const { execFile } = require('child_process');
const fs          = require('fs');
const os          = require('os');
const path        = require('path');
const crypto      = require('crypto');

const PORT = 9100;

// ── CORS headers (allow the Electron/browser origin & HTTPS Private Network Access) ──
const CORS = {
    'Access-Control-Allow-Origin'         : '*',
    'Access-Control-Allow-Methods'        : 'GET, POST, OPTIONS',
    'Access-Control-Allow-Headers'        : 'Content-Type, Access-Control-Request-Private-Network',
    'Access-Control-Allow-Private-Network': 'true',
};

function send(res, status, body) {
    const payload = typeof body === 'string' ? body : JSON.stringify(body);
    res.writeHead(status, { 'Content-Type': 'application/json', ...CORS });
    res.end(payload);
}

// ── PowerShell helpers ─────────────────────────────────────────────────────

/**
 * Run a PowerShell command and return stdout as a string.
 */
function ps(command) {
    return new Promise((resolve, reject) => {
        execFile(
            'powershell.exe',
            ['-NoProfile', '-NonInteractive', '-Command', command],
            { timeout: 15000 },
            (err, stdout, stderr) => {
                if (err) return reject(new Error(stderr || err.message));
                resolve(stdout.trim());
            }
        );
    });
}

/**
 * Return an array of Windows printer objects with status, port, and hardware presence details.
 */
async function listPrinters() {
    const script = `
        $usbDevices = Get-PnpDevice | Where-Object { $_.InstanceId -like "*USBPRINT*" }
        Get-Printer | ForEach-Object {
            $p = $_
            $port = [string]$p.PortName
            $isPluggedIn = $false
            if ($port -match '^USB\\d+') {
                $usbMatch = $usbDevices | Where-Object { $_.InstanceId -like "*$port*" }
                if ($usbMatch) {
                    $isPluggedIn = [bool]($usbMatch.Present -and ($usbMatch.Status -eq 'OK'))
                }
            } elseif ($port -match 'nul:|PORTPROMPT:|PDF|XPS|OneNote|Fax') {
                $isPluggedIn = $true
            } else {
                $isPluggedIn = [bool](-not $p.WorkOffline -and $p.PrinterStatus -ne 128)
            }
            [PSCustomObject]@{
                Name = $p.Name
                PortName = $p.PortName
                DriverName = $p.DriverName
                PrinterStatus = $p.PrinterStatus
                WorkOffline = $p.WorkOffline
                IsPresent = $isPluggedIn
            }
        } | ConvertTo-Json -Compress
    `;
    const raw = await ps(script);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    const list = Array.isArray(parsed) ? parsed : [parsed];
    return list.map(p => {
        const port = String(p.PortName || '');
        const name = String(p.Name || '');
        const driver = String(p.DriverName || '');
        const isVirtual = /PORTPROMPT:|nul:|PDF|XPS|OneNote|Fax/i.test(port) || /PDF|XPS|OneNote|Fax/i.test(name);
        const isUsb = port.toUpperCase().startsWith('USB');
        const isOnline = isVirtual ? true : Boolean(p.IsPresent);

        return {
            name: name,
            port: port,
            driver: driver,
            isOnline: isOnline,
            isOffline: !isOnline,
            isUsb: isUsb,
            isVirtual: isVirtual,
            isThermalCandidate: isUsb || /POS|Receipt|Thermal|Generic|58|80/i.test(name + ' ' + driver)
        };
    });
}

const PRINT_SCRIPT = path.join(__dirname, 'print.ps1');

/**
 * Send raw ESC/POS buffer to Windows printer spooler via print.ps1
 */
function runPrintScript(printerName, filePath) {
    return new Promise((resolve, reject) => {
        execFile(
            'powershell.exe',
            [
                '-NoProfile',
                '-NonInteractive',
                '-ExecutionPolicy', 'Bypass',
                '-File', PRINT_SCRIPT,
                '-PrinterName', printerName,
                '-FilePath', filePath
            ],
            { timeout: 20000 },
            (err, stdout, stderr) => {
                if (err) return reject(new Error((stderr || stdout || err.message).trim()));
                resolve(stdout.trim());
            }
        );
    });
}

async function printRawEscPos(printerName, buffer) {
    const tmpFile = path.join(os.tmpdir(), `mtc-print-${crypto.randomBytes(6).toString('hex')}.bin`);
    try {
        fs.writeFileSync(tmpFile, buffer);
        await runPrintScript(printerName, tmpFile);
    } finally {
        try { fs.unlinkSync(tmpFile); } catch (_) {}
    }
}

// ── Request body reader ────────────────────────────────────────────────────
function readBody(req) {
    return new Promise((resolve, reject) => {
        let data = '';
        req.on('data', chunk => { data += chunk; if (data.length > 1e6) reject(new Error('Payload too large')); });
        req.on('end', () => resolve(data));
        req.on('error', reject);
    });
}

// ── HTTP Server ────────────────────────────────────────────────────────────
const server = http.createServer(async (req, res) => {
    // Preflight
    if (req.method === 'OPTIONS') {
        res.writeHead(204, CORS);
        return res.end();
    }

    try {
        // GET /ping
        if (req.method === 'GET' && req.url === '/ping') {
            return send(res, 200, { ok: true, version: '2.0' });
        }

        // GET /printers
        if (req.method === 'GET' && req.url === '/printers') {
            const printers = await listPrinters();
            return send(res, 200, printers);
        }

        // POST /print
        if (req.method === 'POST' && req.url === '/print') {
            const body = JSON.parse(await readBody(req));
            const { printerName, escposBase64 } = body;

            if (!printerName || !escposBase64) {
                return send(res, 400, { error: 'printerName and escposBase64 are required.' });
            }

            const buffer = Buffer.from(escposBase64, 'base64');
            await printRawEscPos(printerName, buffer);
            return send(res, 200, { ok: true });
        }

        send(res, 404, { error: 'Not found.' });

    } catch (err) {
        console.error('[MTC Print Bridge] Error:', err.message);
        send(res, 500, { error: err.message });
    }
});

server.listen(PORT, '127.0.0.1', () => {
    console.log(`[MTC Print Bridge v2.0] Listening on http://127.0.0.1:${PORT}`);
    console.log('  GET  /ping     — health check');
    console.log('  GET  /printers — list Windows printers');
    console.log('  POST /print    — send ESC/POS bytes to printer');
});

server.on('error', (err) => {
    if (err.code === 'EADDRINUSE') {
        console.error(`[MTC Print Bridge] Port ${PORT} is already in use. Another instance may be running.`);
    } else {
        console.error('[MTC Print Bridge] Server error:', err.message);
    }
    process.exit(1);
});

