# MTC Print Bridge v2.0

A tiny local HTTP server that lets the Mister Takoyaki POS print receipts to any Windows-registered thermal printer — no native packages, no compilation, no Visual Studio.

## Requirements

- Windows 10 or later
- [Node.js](https://nodejs.org/) (LTS, any recent version)
- A thermal printer already installed in Windows (shows up in Control Panel → Devices and Printers)

## Quick Start

1. **Download** `mtc-print-bridge.zip` from the POS system (Settings → Thermal Printer → Wired → Download Print Bridge).
2. **Extract** to any permanent folder (e.g. `C:\MTC\print-bridge\`).
3. **Run `install.bat`** — double-click it. It will:
   - Verify Node.js is installed
   - Register the bridge to auto-start on every Windows login
   - Start the bridge immediately (no reboot needed)
4. Go back to the POS **Settings → Thermal Printer → Wired**, pick your printer from the dropdown, and click **Test Print**.

## How It Works

```
POS Browser  →  POST /print { printerName, escposBase64 }
                 │
                 ▼
         MTC Print Bridge (Node.js, port 9100)
                 │
                 ▼
         PowerShell → Windows Print Spooler → Printer
```

No native C++ bindings. No third-party npm packages. Just Node's built-in `http` and `child_process` talking to PowerShell's `winspool.drv`.

## Endpoints

| Method | URL | Description |
|--------|-----|-------------|
| GET | `/ping` | Health check — returns `{ ok: true, version: "2.0" }` |
| GET | `/printers` | Lists all Windows-registered printers |
| POST | `/print` | Sends ESC/POS bytes to the chosen printer |

## Troubleshooting

- **"Print Bridge is not running"** — make sure `install.bat` was run, or start it manually: open a Command Prompt in the bridge folder and run `node server.js`.
- **Port 9100 in use** — another instance is already running (that's fine — the POS will find it).
- **Printer not in dropdown** — make sure the printer is installed in Windows and visible in Control Panel → Devices and Printers.
- **Test print does nothing** — check that the printer's "RAW" datatype is enabled in its port settings (most thermal printers support this by default).

