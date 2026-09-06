/**
 * Web Bluetooth thermal printer client.
 * Supported: Chrome/Edge on Windows, Android. NOT supported: Safari/iOS.
 *
 * Fixed for 58mm & 80mm thermal printers:
 *  - CRLF (\r\n) line termination for reliable line advancement on PT-210 / 58mm
 *  - Mutex lock (isPrinting) to prevent concurrent writes from interleaving
 *  - Clean layout formatting & two-column items/totals alignment
 *  - ESC/POS raster bit image for store logo with auto-inversion for dark backgrounds
 *  - Large, high-visibility 1-bit raster QR code with white quiet zone for instant camera scanning
 */
const PRINTER_SERVICE_UUID = '000018f0-0000-1000-8000-00805f9b34fb';
const PRINTER_CHARACTERISTIC_UUID = '00002af1-0000-1000-8000-00805f9b34fb';

const ESC = 0x1b;
const GS  = 0x1d;

const CMD = {
    init:            () => [ESC, 0x40],
    alignLeft:       () => [ESC, 0x61, 0x00],
    alignCenter:     () => [ESC, 0x61, 0x01],
    alignRight:      () => [ESC, 0x61, 0x02],
    boldOn:          () => [ESC, 0x45, 0x01],
    boldOff:         () => [ESC, 0x45, 0x00],
    doubleHeightOn:  () => [GS, 0x21, 0x01],  // height only, NOT width
    doubleHeightOff: () => [GS, 0x21, 0x00],
    feed: (n = 1)    => [ESC, 0x64, n],
    cut:             () => [GS, 0x56, 0x42, 0x00],
};

// ── Lightweight Pure JS QR Code Matrix Engine (0 external dependencies) ──
const QREngine = (function() {
    function QR8BitByte(data) { this.mode = 4; this.data = data; }
    QR8BitByte.prototype = {
        getLength: function() { return this.data.length; },
        write: function(buffer) {
            for (let i = 0; i < this.data.length; i++) buffer.put(this.data.charCodeAt(i), 8);
        }
    };
    function QRCode(typeNumber, errorCorrectLevel) {
        this.typeNumber = typeNumber;
        this.errorCorrectLevel = errorCorrectLevel;
        this.modules = null;
        this.moduleCount = 0;
        this.dataCache = null;
        this.dataList = [];
    }
    QRCode.prototype = {
        addData: function(data) { this.dataList.push(new QR8BitByte(data)); this.dataCache = null; },
        isDark: function(row, col) { return this.modules[row][col]; },
        getModuleCount: function() { return this.moduleCount; },
        make: function() {
            if (this.typeNumber < 1) {
                let typeNumber = 1;
                for (typeNumber = 1; typeNumber < 40; typeNumber++) {
                    const rsBlocks = QRRSBlock.getRSBlocks(typeNumber, this.errorCorrectLevel);
                    const buffer = new QRBitBuffer();
                    let totalDataCount = 0;
                    for (let i = 0; i < rsBlocks.length; i++) totalDataCount += rsBlocks[i].dataCount;
                    for (let i = 0; i < this.dataList.length; i++) {
                        const data = this.dataList[i];
                        buffer.put(data.mode, 4);
                        buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
                        data.write(buffer);
                    }
                    if (buffer.getLengthInBits() <= totalDataCount * 8) break;
                }
                this.typeNumber = typeNumber;
            }
            this.makeImpl(false, this.getBestMaskPattern());
        },
        makeImpl: function(test, maskPattern) {
            this.moduleCount = this.typeNumber * 4 + 17;
            this.modules = new Array(this.moduleCount);
            for (let r = 0; r < this.moduleCount; r++) {
                this.modules[r] = new Array(this.moduleCount);
                for (let c = 0; c < this.moduleCount; c++) this.modules[r][c] = null;
            }
            this.setupPositionProbePattern(0, 0);
            this.setupPositionProbePattern(this.moduleCount - 7, 0);
            this.setupPositionProbePattern(0, this.moduleCount - 7);
            this.setupPositionAdjustPattern();
            this.setupTimingPattern();
            this.setupTypeInfo(test, maskPattern);
            if (this.typeNumber >= 7) this.setupTypeNumber(test);
            if (this.dataCache == null) this.dataCache = QRCode.createData(this.typeNumber, this.errorCorrectLevel, this.dataList);
            this.mapData(this.dataCache, maskPattern);
        },
        setupPositionProbePattern: function(row, col) {
            for (let r = -1; r <= 7; r++) {
                if (row + r <= -1 || this.moduleCount <= row + r) continue;
                for (let c = -1; c <= 7; c++) {
                    if (col + c <= -1 || this.moduleCount <= col + c) continue;
                    if ((0 <= r && r <= 6 && (c == 0 || c == 6)) || (0 <= c && c <= 6 && (r == 0 || r == 6)) || (2 <= r && r <= 4 && 2 <= c && c <= 4)) {
                        this.modules[row + r][col + c] = true;
                    } else {
                        this.modules[row + r][col + c] = false;
                    }
                }
            }
        },
        getBestMaskPattern: function() {
            let minLostPoint = 0, pattern = 0;
            for (let i = 0; i < 8; i++) {
                this.makeImpl(true, i);
                const lostPoint = QRUtil.getLostPoint(this);
                if (i == 0 || minLostPoint > lostPoint) { minLostPoint = lostPoint; pattern = i; }
            }
            return pattern;
        },
        setupTimingPattern: function() {
            for (let r = 8; r < this.moduleCount - 8; r++) {
                if (this.modules[r][6] !== null) continue;
                this.modules[r][6] = (r % 2 == 0);
            }
            for (let c = 8; c < this.moduleCount - 8; c++) {
                if (this.modules[6][c] !== null) continue;
                this.modules[6][c] = (c % 2 == 0);
            }
        },
        setupPositionAdjustPattern: function() {
            const pos = QRUtil.getPatternPosition(this.typeNumber);
            for (let i = 0; i < pos.length; i++) {
                for (let j = 0; j < pos.length; j++) {
                    const row = pos[i], col = pos[j];
                    if (this.modules[row][col] !== null) continue;
                    for (let r = -2; r <= 2; r++) {
                        for (let c = -2; c <= 2; c++) {
                            this.modules[row + r][col + c] = (r == -2 || r == 2 || c == -2 || c == 2 || (r == 0 && c == 0));
                        }
                    }
                }
            }
        },
        setupTypeNumber: function(test) {
            const bits = QRUtil.getBCHTypeNumber(this.typeNumber);
            for (let i = 0; i < 18; i++) {
                const mod = (!test && ((bits >> i) & 1) == 1);
                this.modules[Math.floor(i / 3)][i % 3 + this.moduleCount - 8 - 3] = mod;
                this.modules[i % 3 + this.moduleCount - 8 - 3][Math.floor(i / 3)] = mod;
            }
        },
        setupTypeInfo: function(test, maskPattern) {
            const data = (this.errorCorrectLevel << 3) | maskPattern;
            const bits = QRUtil.getBCHTypeInfo(data);
            for (let i = 0; i < 15; i++) {
                const mod = (!test && ((bits >> i) & 1) == 1);
                if (i < 6) this.modules[i][8] = mod;
                else if (i < 8) this.modules[i + 1][8] = mod;
                else this.modules[this.moduleCount - 15 + i][8] = mod;
                if (i < 8) this.modules[8][this.moduleCount - i - 1] = mod;
                else if (i < 9) this.modules[8][15 - i - 1 + 1] = mod;
                else this.modules[8][15 - i - 1] = mod;
            }
            this.modules[this.moduleCount - 8][8] = !test;
        },
        mapData: function(data, maskPattern) {
            let inc = -1, row = this.moduleCount - 1, bitIndex = 7, byteIndex = 0;
            for (let col = this.moduleCount - 1; col > 0; col -= 2) {
                if (col == 6) col--;
                while (true) {
                    for (let c = 0; c < 2; c++) {
                        if (this.modules[row][col - c] === null) {
                            let dark = false;
                            if (byteIndex < data.length) dark = (((data[byteIndex] >>> bitIndex) & 1) == 1);
                            if (QRUtil.getMask(maskPattern, row, col - c)) dark = !dark;
                            this.modules[row][col - c] = dark;
                            bitIndex--;
                            if (bitIndex == -1) { byteIndex++; bitIndex = 7; }
                        }
                    }
                    row += inc;
                    if (row < 0 || this.moduleCount <= row) { row -= inc; inc = -inc; break; }
                }
            }
        }
    };
    QRCode.createData = function(typeNumber, errorCorrectLevel, dataList) {
        const rsBlocks = QRRSBlock.getRSBlocks(typeNumber, errorCorrectLevel);
        const buffer = new QRBitBuffer();
        for (let i = 0; i < dataList.length; i++) {
            const data = dataList[i];
            buffer.put(data.mode, 4);
            buffer.put(data.getLength(), QRUtil.getLengthInBits(data.mode, typeNumber));
            data.write(buffer);
        }
        let totalDataCount = 0;
        for (let i = 0; i < rsBlocks.length; i++) totalDataCount += rsBlocks[i].dataCount;
        if (buffer.getLengthInBits() + 4 <= totalDataCount * 8) buffer.put(0, 4);
        while (buffer.getLengthInBits() % 8 != 0) buffer.putBit(false);
        while (true) {
            if (buffer.getLengthInBits() >= totalDataCount * 8) break;
            buffer.put(0xEC, 8);
            if (buffer.getLengthInBits() >= totalDataCount * 8) break;
            buffer.put(0x11, 8);
        }
        return QRCode.createBytes(buffer, rsBlocks);
    };
    QRCode.createBytes = function(buffer, rsBlocks) {
        let offset = 0, maxDcCount = 0, maxEcCount = 0;
        const dcdata = new Array(rsBlocks.length), ecdata = new Array(rsBlocks.length);
        for (let r = 0; r < rsBlocks.length; r++) {
            const dcCount = rsBlocks[r].dataCount, ecCount = rsBlocks[r].totalCount - dcCount;
            maxDcCount = Math.max(maxDcCount, dcCount);
            maxEcCount = Math.max(maxEcCount, ecCount);
            dcdata[r] = new Array(dcCount);
            for (let i = 0; i < dcdata[r].length; i++) dcdata[r][i] = 0xff & buffer.buffer[i + offset];
            offset += dcCount;
            const rsPoly = QRUtil.getErrorCorrectPolynomial(ecCount);
            const rawPoly = new QRPolynomial(dcdata[r], rsPoly.getLength() - 1);
            const modPoly = rawPoly.mod(rsPoly);
            ecdata[r] = new Array(rsPoly.getLength() - 1);
            for (let i = 0; i < ecdata[r].length; i++) {
                const modIndex = i + modPoly.getLength() - ecdata[r].length;
                ecdata[r][i] = (modIndex >= 0) ? modPoly.get(modIndex) : 0;
            }
        }
        let totalCodeCount = 0;
        for (let i = 0; i < rsBlocks.length; i++) totalCodeCount += rsBlocks[i].totalCount;
        const data = new Array(totalCodeCount);
        let index = 0;
        for (let i = 0; i < maxDcCount; i++) {
            for (let r = 0; r < rsBlocks.length; r++) { if (i < dcdata[r].length) data[index++] = dcdata[r][i]; }
        }
        for (let i = 0; i < maxEcCount; i++) {
            for (let r = 0; r < rsBlocks.length; r++) { if (i < ecdata[r].length) data[index++] = ecdata[r][i]; }
        }
        return data;
    };
    const QRErrorCorrectLevel = { L: 1, M: 0, Q: 3, H: 2 };
    const QRMaskPattern = { PATTERN000: 0, PATTERN001: 1, PATTERN010: 2, PATTERN011: 3, PATTERN100: 4, PATTERN101: 5, PATTERN110: 6, PATTERN111: 7 };
    const QRUtil = {
        PATTERN_POSITION_TABLE: [
            [], [6, 18], [6, 22], [6, 26], [6, 30], [6, 34], [6, 22, 38], [6, 24, 42], [6, 26, 46], [6, 28, 50],
            [6, 30, 54], [6, 32, 58], [6, 34, 62], [6, 26, 46, 66], [6, 26, 48, 70], [6, 26, 50, 74], [6, 30, 54, 78],
            [6, 30, 56, 82], [6, 30, 58, 86], [6, 34, 62, 90], [6, 28, 50, 72, 94], [6, 26, 50, 74, 98], [6, 30, 54, 78, 102],
            [6, 28, 54, 80, 106], [6, 32, 58, 84, 110], [6, 30, 58, 86, 114], [6, 34, 62, 90, 118], [6, 26, 50, 74, 98, 122],
            [6, 30, 54, 78, 102, 126], [6, 26, 52, 78, 104, 130], [6, 30, 56, 82, 108, 134], [6, 34, 60, 86, 112, 138],
            [6, 30, 58, 86, 114, 142], [6, 34, 62, 90, 118, 146], [6, 30, 54, 78, 102, 126, 150], [6, 24, 50, 76, 102, 128, 154],
            [6, 28, 54, 80, 106, 132, 158], [6, 32, 58, 84, 110, 136, 162], [6, 26, 54, 82, 110, 138, 166], [6, 30, 58, 86, 114, 142, 170]
        ],
        G15: (1 << 10) | (1 << 8) | (1 << 5) | (1 << 4) | (1 << 2) | (1 << 1) | (1 << 0),
        G18: (1 << 12) | (1 << 11) | (1 << 10) | (1 << 9) | (1 << 8) | (1 << 5) | (1 << 2) | (1 << 0),
        G15_MASK: (1 << 14) | (1 << 12) | (1 << 10) | (1 << 4) | (1 << 1),
        getBCHTypeInfo: function(data) {
            let d = data << 10;
            while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15) >= 0) d ^= (QRUtil.G15 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G15)));
            return ((data << 10) | d) ^ QRUtil.G15_MASK;
        },
        getBCHTypeNumber: function(data) {
            let d = data << 12;
            while (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18) >= 0) d ^= (QRUtil.G18 << (QRUtil.getBCHDigit(d) - QRUtil.getBCHDigit(QRUtil.G18)));
            return (data << 12) | d;
        },
        getBCHDigit: function(data) { let digit = 0; while (data != 0) { digit++; data >>>= 1; } return digit; },
        getPatternPosition: function(typeNumber) { return QRUtil.PATTERN_POSITION_TABLE[typeNumber - 1]; },
        getMask: function(maskPattern, i, j) {
            switch (maskPattern) {
                case QRMaskPattern.PATTERN000: return (i + j) % 2 == 0;
                case QRMaskPattern.PATTERN001: return i % 2 == 0;
                case QRMaskPattern.PATTERN010: return j % 3 == 0;
                case QRMaskPattern.PATTERN011: return (i + j) % 3 == 0;
                case QRMaskPattern.PATTERN100: return (Math.floor(i / 2) + Math.floor(j / 3)) % 2 == 0;
                case QRMaskPattern.PATTERN101: return (i * j) % 2 + (i * j) % 3 == 0;
                case QRMaskPattern.PATTERN110: return ((i * j) % 2 + (i * j) % 3) % 2 == 0;
                case QRMaskPattern.PATTERN111: return ((i * j) % 3 + (i + j) % 2) % 2 == 0;
                default: throw new Error('bad maskPattern:' + maskPattern);
            }
        },
        getErrorCorrectPolynomial: function(errorCorrectLength) {
            let a = new QRPolynomial([1], 0);
            for (let i = 0; i < errorCorrectLength; i++) a = a.multiply(new QRPolynomial([1, QRMath.gexp(i)], 0));
            return a;
        },
        getLengthInBits: function(mode, type) {
            if (1 <= type && type < 10) {
                return mode == 1 ? 10 : (mode == 2 ? 9 : 8);
            } else if (type < 27) {
                return mode == 1 ? 12 : (mode == 2 ? 11 : 16);
            } else {
                return mode == 1 ? 14 : (mode == 2 ? 13 : 16);
            }
        },
        getLostPoint: function(qrCode) {
            const moduleCount = qrCode.getModuleCount();
            let lostPoint = 0;
            for (let row = 0; row < moduleCount; row++) {
                for (let col = 0; col < moduleCount; col++) {
                    let sameCount = 0;
                    const dark = qrCode.isDark(row, col);
                    for (let r = -1; r <= 1; r++) {
                        if (row + r < 0 || moduleCount <= row + r) continue;
                        for (let c = -1; c <= 1; c++) {
                            if (col + c < 0 || moduleCount <= col + c) continue;
                            if (r == 0 && c == 0) continue;
                            if (dark == qrCode.isDark(row + r, col + c)) sameCount++;
                        }
                    }
                    if (sameCount > 5) lostPoint += (3 + sameCount - 5);
                }
            }
            return lostPoint;
        }
    };
    const QRMath = {
        glog: function(n) { if (n < 1) throw new Error('glog(' + n + ')'); return QRMath.LOG_TABLE[n]; },
        gexp: function(n) { while (n < 0) n += 255; while (n >= 256) n -= 255; return QRMath.EXP_TABLE[n]; },
        EXP_TABLE: new Array(256), LOG_TABLE: new Array(256)
    };
    for (let i = 0; i < 8; i++) QRMath.EXP_TABLE[i] = 1 << i;
    for (let i = 8; i < 256; i++) QRMath.EXP_TABLE[i] = QRMath.EXP_TABLE[i - 4] ^ QRMath.EXP_TABLE[i - 5] ^ QRMath.EXP_TABLE[i - 6] ^ QRMath.EXP_TABLE[i - 8];
    for (let i = 0; i < 255; i++) QRMath.LOG_TABLE[QRMath.EXP_TABLE[i]] = i;
    function QRPolynomial(num, shift) {
        let offset = 0;
        while (offset < num.length && num[offset] == 0) offset++;
        this.num = new Array(num.length - offset + shift);
        for (let i = 0; i < num.length - offset; i++) this.num[i] = num[i + offset];
    }
    QRPolynomial.prototype = {
        get: function(index) { return this.num[index]; },
        getLength: function() { return this.num.length; },
        multiply: function(e) {
            const num = new Array(this.getLength() + e.getLength() - 1);
            for (let i = 0; i < this.getLength(); i++) {
                for (let j = 0; j < e.getLength(); j++) num[i + j] ^= QRMath.gexp(QRMath.glog(this.get(i)) + QRMath.glog(e.get(j)));
            }
            return new QRPolynomial(num, 0);
        },
        mod: function(e) {
            if (this.getLength() - e.getLength() < 0) return this;
            const ratio = QRMath.glog(this.get(0)) - QRMath.glog(e.get(0));
            const num = new Array(this.getLength());
            for (let i = 0; i < this.getLength(); i++) num[i] = this.get(i);
            for (let i = 0; i < e.getLength(); i++) num[i] ^= QRMath.gexp(QRMath.glog(e.get(i)) + ratio);
            return new QRPolynomial(num, 0).mod(e);
        }
    };
    function QRRSBlock(totalCount, dataCount) { this.totalCount = totalCount; this.dataCount = dataCount; }
    QRRSBlock.RS_BLOCK_TABLE = [
        [1, 26, 19], [1, 26, 16], [1, 26, 13], [1, 26, 9],
        [1, 44, 34], [1, 44, 28], [1, 44, 22], [1, 44, 16],
        [1, 70, 55], [1, 70, 44], [2, 35, 17], [2, 35, 13],
        [1, 100, 80], [2, 50, 32], [2, 50, 24], [4, 25, 9],
        [1, 134, 108], [2, 67, 43], [2, 33, 15, 2, 34, 16], [2, 33, 11, 2, 34, 12],
        [2, 86, 68], [4, 43, 27], [4, 43, 19], [4, 43, 15],
        [2, 98, 78], [4, 49, 31], [2, 32, 14, 4, 33, 15], [4, 39, 13, 1, 40, 14],
        [2, 121, 97], [2, 60, 38, 2, 61, 39], [4, 40, 18, 2, 41, 19], [4, 40, 14, 2, 41, 15],
        [2, 146, 116], [3, 58, 36, 2, 59, 37], [4, 36, 16, 4, 37, 17], [4, 36, 12, 4, 37, 13],
        [2, 86, 68, 2, 87, 69], [4, 69, 43, 1, 70, 44], [6, 43, 19, 2, 44, 20], [6, 43, 15, 2, 44, 16]
    ];
    QRRSBlock.getRSBlocks = function(typeNumber, errorCorrectLevel) {
        let offset;
        switch (errorCorrectLevel) {
            case QRErrorCorrectLevel.L: offset = 0; break;
            case QRErrorCorrectLevel.M: offset = 1; break;
            case QRErrorCorrectLevel.Q: offset = 2; break;
            case QRErrorCorrectLevel.H: offset = 3; break;
            default: offset = 0;
        }
        const rsBlock = QRRSBlock.RS_BLOCK_TABLE[(typeNumber - 1) * 4 + offset];
        const length = rsBlock.length / 3, list = [];
        for (let i = 0; i < length; i++) {
            const count = rsBlock[i * 3 + 0], totalCount = rsBlock[i * 3 + 1], dataCount = rsBlock[i * 3 + 2];
            for (let j = 0; j < count; j++) list.push(new QRRSBlock(totalCount, dataCount));
        }
        return list;
    };
    function QRBitBuffer() { this.buffer = []; this.length = 0; }
    QRBitBuffer.prototype = {
        put: function(num, length) { for (let i = 0; i < length; i++) this.putBit(((num >>> (length - i - 1)) & 1) == 1); },
        getLengthInBits: function() { return this.length; },
        putBit: function(bit) {
            const bufIndex = Math.floor(this.length / 8);
            if (this.buffer.length <= bufIndex) this.buffer.push(0);
            if (bit) this.buffer[bufIndex] |= (0x80 >>> (this.length % 8));
            this.length++;
        }
    };

    return {
        create: function(text) {
            const qr = new QRCode(0, QRErrorCorrectLevel.M); // Better scan robustness on receipt printers
            qr.addData(text);
            qr.make();
            return qr;
        }
    };
})();

/**
 * Generate a large, crystal-clear 1-bit monochrome ESC/POS raster QR Code (GS v 0).
 * Scaled up to ~300 dots with 7-8 dots per module and a 4-module quiet zone,
 * allowing instant scanning by any camera on 58mm & 80mm thermal paper.
 * Generate a clean, medium-sized 1-bit monochrome ESC/POS raster QR Code (GS v 0).
 * Slightly more whitespace between modules and a stronger white border to keep the black squares
 * crisp and readable on thermal paper without making the pattern feel dense or muddy.
 */
function generateQrRasterBytes(text, charsPerLine = 32) {
    if (!text) return [];
    try {
        const qrText = String(text || '').trim();
        if (!qrText) return [];

        const qr = QREngine.create(qrText);
        const moduleCount = qr.getModuleCount();
        const quietZone = 6; // More "air" around the QR makes the black squares look cleaner and easier to read.
        const totalModules = moduleCount + quietZone * 2;

        // Keep the black area a little less packed so the pattern prints with cleaner contrast
        // and less muddy density on narrow thermal receipt paper.
        const maxDots = charsPerLine > 32 ? 220 : 180;
        const dotScale = Math.max(8, Math.min(12, Math.floor(maxDots / totalModules)));
        const rawWidth = totalModules * dotScale;
        // Width in bytes must be integer multiple of 8
        const bytesPerLine = Math.ceil(rawWidth / 8);
        const heightDots = totalModules * dotScale;

        const bitmapBytes = [];

        for (let y = 0; y < heightDots; y++) {
            const modY = Math.floor(y / dotScale) - quietZone;
            for (let bx = 0; bx < bytesPerLine; bx++) {
                let byteVal = 0;
                for (let b = 0; b < 8; b++) {
                    const x = bx * 8 + b;
                    const modX = Math.floor(x / dotScale) - quietZone;
                    let isDark = false;
                    if (modY >= 0 && modY < moduleCount && modX >= 0 && modX < moduleCount) {
                        isDark = qr.isDark(modY, modX);
                    }
                    if (isDark) {
                        byteVal |= (0x80 >> b);
                    }
                }
                bitmapBytes.push(byteVal);
            }
        }

        const xL = bytesPerLine & 0xFF;
        const xH = (bytesPerLine >> 8) & 0xFF;
        const yL = heightDots & 0xFF;
        const yH = (heightDots >> 8) & 0xFF;

        return [
            ESC, 0x61, 0x01, // Center align
            GS, 0x76, 0x30, 0x00, xL, xH, yL, yH,
            ...bitmapBytes,
            0x0D, 0x0A
        ];
    } catch (err) {
        console.warn('Thermal printer: QR raster generation failed:', err);
        return [];
    }
}

/**
 * Convert string to ASCII byte array, replacing currency and punctuation symbols
 * and stripping anything outside printable ASCII.
 */
function textToBytes(str) {
    const safe = (str || '')
        .replace(/\u20b1/g, 'P')          // Filipino peso sign ₱ -> P
        .replace(/[\u2018\u2019]/g, "'")  // Curly single quotes
        .replace(/[\u201c\u201d]/g, '"')  // Curly double quotes
        .replace(/[\u2013\u2014]/g, '-')  // Em/en dashes
        .replace(/[^\x20-\x7E\r\n\t]/g, ''); // Strip non-ASCII & control characters

    const bytes = [];
    for (let i = 0; i < safe.length; i++) {
        bytes.push(safe.charCodeAt(i) & 0xFF);
    }
    return bytes;
}

/**
 * Convert an image data URI into standard ESC/POS raster bit image command (GS v 0).
 * Intelligently detects dark background images (e.g. black background logos) and auto-inverts
 * them so only the artwork/text is burned as black dots onto white receipt paper.
 */
async function imageToRasterBytes(dataUri, maxWidth = 192) {
    if (!dataUri) return [];
    return new Promise((resolve) => {
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            try {
                // Ensure width is a multiple of 8 (required by ESC/POS GS v 0)
                let w = Math.min(maxWidth, img.width);
                w = Math.max(8, Math.floor(w / 8) * 8);
                const scale = w / img.width;
                const h = Math.max(1, Math.round(img.height * scale));

                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');

                // Draw original image
                ctx.drawImage(img, 0, 0, w, h);

                const imgData = ctx.getImageData(0, 0, w, h);
                const data = imgData.data;

                // Sample corners and borders to detect if the source image has a dark background
                let cornerLuminanceSum = 0;
                let opaqueCornerCount = 0;
                const cornerPixels = [
                    0,                                // top-left
                    (w - 1) * 4,                      // top-right
                    ((h - 1) * w) * 4,                // bottom-left
                    ((h - 1) * w + (w - 1)) * 4       // bottom-right
                ];

                for (const offset of cornerPixels) {
                    const r = data[offset];
                    const g = data[offset + 1];
                    const b = data[offset + 2];
                    const a = data[offset + 3];
                    if (a >= 128) {
                        cornerLuminanceSum += (0.299 * r + 0.587 * g + 0.114 * b);
                        opaqueCornerCount++;
                    }
                }

                // If corners are opaque and dark (luminance < 110), it's a dark background image -> invert it!
                const isDarkBackground = opaqueCornerCount >= 2 && (cornerLuminanceSum / opaqueCornerCount) < 110;

                const bytesPerLine = w / 8;
                const bitmapBytes = [];

                for (let y = 0; y < h; y++) {
                    for (let bx = 0; bx < bytesPerLine; bx++) {
                        let byteVal = 0;
                        for (let b = 0; b < 8; b++) {
                            const x = bx * 8 + b;
                            const idx = (y * w + x) * 4;
                            const r = data[idx];
                            const g = data[idx + 1];
                            const b_val = data[idx + 2];
                            const a = data[idx + 3];

                            const lum = 0.299 * r + 0.587 * g + 0.114 * b_val;
                            let isBlackDot = false;

                            if (isDarkBackground) {
                                // Inverted mode: Dark background becomes white paper; light text/art becomes black ink
                                isBlackDot = a >= 128 && lum >= 130;
                            } else {
                                // Standard mode: White/transparent background stays white; dark text/art becomes black ink
                                isBlackDot = a >= 128 && lum < 165;
                            }

                            if (isBlackDot) {
                                byteVal |= (0x80 >> b);
                            }
                        }
                        bitmapBytes.push(byteVal);
                    }
                }

                const xL = bytesPerLine & 0xFF;
                const xH = (bytesPerLine >> 8) & 0xFF;
                const yL = h & 0xFF;
                const yH = (h >> 8) & 0xFF;

                resolve([
                    ESC, 0x61, 0x01, // Center alignment
                    GS, 0x76, 0x30, 0x00, xL, xH, yL, yH,
                    ...bitmapBytes,
                    0x0D, 0x0A
                ]);
            } catch (err) {
                console.warn('Thermal printer: Logo rasterization skipped:', err);
                resolve([]);
            }
        };
        img.onerror = () => resolve([]);
        img.src = dataUri;
    });
}

class ThermalBluetoothPrinter {
    constructor() {
        this.device = null;
        this.characteristic = null;
        this.isPrinting = false;
        // Restore width preference or default to 32 chars (58mm)
        const savedWidth = localStorage.getItem('pos_receipt_width_pref') || '58mm';
        this.charsPerLine = savedWidth === '80mm' ? 42 : 32;
        // Holds the resolver for a paused multi-slip print job, so the UI
        // can call confirmContinue() once staff has torn off the current slip.
        this._resumeResolver = null;
    }

    /**
     * Pause the print job and notify the UI that a slip is ready to be torn
     * off. Resolves once confirmContinue() is called (or auto-resumes after
     * a timeout as a safety net in case the UI never confirms).
     */
            waitForTearOff(sectionType, timeoutMs = 15000) {
        return new Promise((resolve) => {
            let settled = false;
            const finish = (autoResumed) => {
                if (settled) return;
                settled = true;
                this._resumeResolver = null;
                clearTimeout(timer);
                // Tell the modal to close, whether the user clicked or the
                // timer fired — the modal itself no longer decides this.
                window.dispatchEvent(new CustomEvent('thermal-print-resumed', {
                    detail: { sectionType, autoResumed }
                }));
                resolve();
            };

            this._resumeResolver = () => finish(false);

            const timer = setTimeout(() => {
                console.warn(`⚠️ No tear-off confirmation for "${sectionType}" slip after ${timeoutMs}ms — auto-continuing.`);
                finish(true);
            }, timeoutMs);

            // Let the modal know both the slip type and how long it has
            // before auto-resuming, so it can render a live countdown.
            window.dispatchEvent(new CustomEvent('thermal-print-waiting', {
                detail: { sectionType, timeoutMs }
            }));
        });
    }

    /**
     * Call this from the UI (e.g. a "Continue Printing" button) once the
     * current slip has been torn off, to resume the paused print job.
     */
        confirmContinue() {
        // _resumeResolver (set in waitForTearOff) already handles clearing
        // the timeout and dispatching 'thermal-print-resumed' via finish().
        if (this._resumeResolver) {
            this._resumeResolver();
        }
    }

    isSupported() { return 'bluetooth' in navigator; }

    async connect() {
        if (!this.isSupported()) throw new Error('Web Bluetooth not supported.');
        this.device = await navigator.bluetooth.requestDevice({
            filters: [{ services: [PRINTER_SERVICE_UUID] }],
            optionalServices: [PRINTER_SERVICE_UUID],
        });
        this.device.addEventListener('gattserverdisconnected', () => {
            this.characteristic = null;
            this.isPrinting = false;
            window.dispatchEvent(new CustomEvent('thermal-bt-disconnected'));
        });
        const server = await this.device.gatt.connect();
        const svc = await server.getPrimaryService(PRINTER_SERVICE_UUID);
        this.characteristic = await svc.getCharacteristic(PRINTER_CHARACTERISTIC_UUID);
        localStorage.setItem('thermal_printer_name', this.device.name || 'Unknown');
        return this.device.name;
    }

    async disconnect() {
        if (this.device?.gatt?.connected) this.device.gatt.disconnect();
    }

    /** Mirrors the server-side "Test Printer Connection" button for Bluetooth. */
    async printTestPage() {
        if (!this.characteristic) throw new Error('Printer not connected.');
        let bytes = [];
        const push = arr => { bytes = bytes.concat(arr); };
        const ln = str => push(textToBytes((str || '') + '\r\n'));
        push(CMD.init());
        push(CMD.alignCenter());
        push(CMD.boldOn());
        ln('PRINTER TEST');
        push(CMD.boldOff());
        ln('Connection successful!');
        ln(new Date().toLocaleString());
        push(CMD.feed(3));
        push(CMD.cut());
        await this.write(bytes);
    }

    setPaperWidth(mm) { 
        this.charsPerLine = mm === 80 || mm === '80mm' ? 42 : 32; 
    }

    async write(bytes) {
        if (!this.characteristic) throw new Error('Printer not connected.');
        const chunk = 120; // 120 bytes per BLE write for maximum compatibility
        const data = new Uint8Array(bytes);
        for (let i = 0; i < data.length; i += chunk) {
            await this.characteristic.writeValueWithoutResponse(data.slice(i, i + chunk));
            await new Promise(r => setTimeout(r, 25));
        }
    }

    // ── Layout helpers ──────────────────────────────────────────────────

    divider(c = '-') { return c.repeat(this.charsPerLine); }

    twoColumns(left, right) {
        const r = String(right || '');
        const maxL = this.charsPerLine - r.length - 1;
        const l = (left || '').length > maxL ? (left || '').slice(0, maxL) : (left || '');
        const spaces = this.charsPerLine - l.length - r.length;
        return l + ' '.repeat(Math.max(1, spaces)) + r;
        const label = String(left || '');
        const value = String(right || '');
        const priceWidth = Math.min(Math.max(value.length + 1, 10), this.charsPerLine);
        const available = Math.max(4, this.charsPerLine - priceWidth);
        const trimmed = label.length > available
            ? label.slice(0, Math.max(0, available - 1)).trimEnd() + '…'
            : label;
        return trimmed.padEnd(available, ' ') + ' ' + value;
    }

    wrap(text) {
        const words = (text || '').split(/\s+/);
        const lines = [];
        let line = '';
        for (const w of words) {
            if (!w) continue;
            const candidate = (line ? line + ' ' + w : w).trim();
            if (candidate.length > this.charsPerLine) {
                if (line) lines.push(line.trim());
                let remaining = w;
                while (remaining.length > this.charsPerLine) {
                    lines.push(remaining.slice(0, this.charsPerLine));
                    remaining = remaining.slice(this.charsPerLine);
                }
                line = remaining;
            } else {
                line = candidate;
            }
        }
        if (line) lines.push(line.trim());
        return lines.join('\r\n');
    }

    money(amount, symbol) {
        const sym = (symbol || 'P').replace(/\u20b1/g, 'P');
        return sym + Number(amount || 0).toFixed(2);
    }

    /**
     * Print receipt sections received from /pos/orders/{id}/receipt-data.
     */
    async printReceipt(order, settings, receipts = [], options = {}) {
        if (this.isPrinting) {
            console.warn('⚠️ Thermal print already in progress, skipping duplicate request.');
            return;
        }

        this.isPrinting = true;

        // One-slip-at-a-time: pause after each kitchen/barista slip so
        // staff can tear it off before the next slip prints. Defaults to
        // on; can be disabled per-call or via settings.print_one_slip_at_a_time.
        const oneSlipAtATime = options.oneSlipAtATime
            ?? settings.print_one_slip_at_a_time
            ?? true;

        try {
            // Sync paper width with saved preference
            const savedWidth = localStorage.getItem('pos_receipt_width_pref') || '58mm';
            this.charsPerLine = savedWidth === '80mm' ? 42 : 32;

            const sections = receipts && receipts.length
                ? receipts
                : [{ type: 'customer', title: settings.business_name || 'Receipt', items: order.items || [] }];

            // How many times to print the customer copy. Kitchen/barista
            // slips are intentionally NOT multiplied — those are prep
            // tickets, not customer copies.
            const customerCopies = Math.max(1, parseInt(settings.receipt_copies, 10) || 1);

            const sym = (settings.currency_symbol || 'P').replace(/\u20b1/g, 'P');

            let bytes = [];
            const push = arr => { bytes = bytes.concat(arr); };
            // CRITICAL: Always use \r\n (CRLF) for thermal printers to advance newlines properly
            const ln = str => push(textToBytes((str || '') + '\r\n'));

            // Pre-rasterize logo if enabled
            let logoBytes = [];
            if (settings.receipt_logo_enabled !== false && settings.logo_data_uri) {
                const maxLogoWidth = this.charsPerLine > 32 ? 240 : 192;
                logoBytes = await imageToRasterBytes(settings.logo_data_uri, maxLogoWidth);
            }

            for (let sIdx = 0; sIdx < sections.length; sIdx++) {
                const section = sections[sIdx];
                const copiesForThisSection = section.type === 'customer' ? customerCopies : 1;

                for (let copyIdx = 0; copyIdx < copiesForThisSection; copyIdx++) {
                bytes = []; // start a fresh buffer for THIS section only
                push(CMD.init());

                // ── Header ─────────────────────────────────────────────────
                if (section.type === 'customer') {
                    // Logo (Customer receipt only)
                    if (logoBytes.length > 0) {
                        push(logoBytes);
                    }

                    push(CMD.alignCenter());
                    ln(this.divider('='));
                    push(CMD.boldOn());
                    push(CMD.doubleHeightOn());
                    ln((section.title || settings.business_name || 'MISTER TAKOYAKI CAFE').toUpperCase());
                    push(CMD.doubleHeightOff());
                    push(CMD.boldOff());
                    ln(settings.customer_receipt_title || 'Customer Receipt & Invoice');
                    ln(this.divider('='));

                    if (settings.business_address) ln(this.wrap(settings.business_address));
                    if (settings.business_phone)   ln('Tel: ' + settings.business_phone);
                    if (settings.business_email)   ln('Email: ' + settings.business_email);
                } else if (section.type === 'kitchen') {
                    push(CMD.alignCenter());
                    ln(this.divider('='));
                    push(CMD.boldOn());
                    push(CMD.doubleHeightOn());
                    ln(settings.kitchen_slip_title || 'KITCHEN SLIP');
                    push(CMD.doubleHeightOff());
                    push(CMD.boldOff());
                    ln(settings.kitchen_slip_subtitle || 'Food Preparation Order');
                    ln(this.divider('='));
                } else if (section.type === 'barista') {
                    push(CMD.alignCenter());
                    ln(this.divider('='));
                    push(CMD.boldOn());
                    push(CMD.doubleHeightOn());
                    ln(settings.barista_slip_title || 'BARISTA SLIP');
                    push(CMD.doubleHeightOff());
                    push(CMD.boldOff());
                    ln(settings.barista_slip_subtitle || 'Beverage Preparation Order');
                    ln(this.divider('='));
                }

                // ── Order Meta ──────────────────────────────────────────────
                push(CMD.alignLeft());
                ln('');
                ln('Order #: ' + (order.reference || order.reference_no || 'N/A'));
                ln('Date:    ' + (order.date || 'N/A'));
                ln('Type:    ' + (order.type || order.order_type || 'N/A').toUpperCase());
                if (order.table_number) {
                    ln('Table:   ' + order.table_number);
                }
                if (order.customer_name) {
                    ln('Customer:' + order.customer_name);
                }
                if (order.customer_phone) {
                    ln('Phone:   ' + order.customer_phone);
                }
                if (section.type === 'customer' && order.payment_method) {
                    ln('Payment: ' + order.payment_method);
                }

                // ── Delivery Address (customer receipt only) ──
                const orderType = (order.type || order.order_type || '').toLowerCase();
                if (section.type === 'customer' && orderType === 'delivery' && order.delivery_address) {
                    ln(this.divider('-'));
                    push(CMD.boldOn());
                    ln('DELIVER TO:');
                    push(CMD.boldOff());
                    ln(this.wrap(order.delivery_address));
                    if (order.delivery_notes) {
                        ln(this.wrap('Note: ' + order.delivery_notes));
                    }
                }

                ln(this.divider('-'));
                ln('ITEMS:');
                ln(this.divider('-'));

                // ── Items ───────────────────────────────────────────────────
                if ((section.items || []).length > 0) {
                    for (const item of section.items) {
                        const qty   = Number(item.qty ?? item.quantity ?? 1);
                        const name  = item.name || item.product_name || 'Item';
                        const label = qty + 'x ' + name;

                        if (section.type === 'customer') {
                            push(CMD.boldOn());
                            ln(this.twoColumns(label, this.money(item.subtotal ?? item.total ?? 0, sym)));
                            push(CMD.boldOff());
                        } else {
                            push(CMD.boldOn());
                            ln(this.wrap(label));
                            push(CMD.boldOff());
                        }

                        for (const opt of item.options || []) {
                            ln('  + ' + (opt.name || opt.option_name || 'Option'));
                            const optName = opt.name || opt.option_name || 'Option';
                            ln('  + ' + (optName.length > this.charsPerLine - 6 ? optName.slice(0, this.charsPerLine - 9).trimEnd() + '…' : optName));
                        }
                        for (const mod of item.modifiers || []) {
                            ln('  + ' + (mod.name || mod.modifier_name || 'Modifier'));
                            const modName = mod.name || mod.modifier_name || 'Modifier';
                            ln('  + ' + (modName.length > this.charsPerLine - 6 ? modName.slice(0, this.charsPerLine - 9).trimEnd() + '…' : modName));
                        }
                        const note = (item.notes || item.special_instructions || '').trim();
                        if (note) ln(this.wrap('  NOTE: ' + note));
                    }
                } else {
                    ln('No items in this order');
                }

                // ── Totals (Customer only) ──────────────────────────────────
                if (section.type === 'customer') {
                    ln(this.divider('-'));
                    ln(this.twoColumns('Subtotal', this.money(order.subtotal ?? order.total_amount ?? 0, sym)));

                    if (Number(order.discount_amount) > 0)
                        ln(this.twoColumns('Discount', '-' + this.money(order.discount_amount, sym)));
                    if (Number(order.service_charge) > 0)
                        ln(this.twoColumns('Service Charge', this.money(order.service_charge, sym)));
                    if (Number(order.delivery_fee) > 0)
                        ln(this.twoColumns('Delivery Fee', this.money(order.delivery_fee, sym)));

                    ln(this.divider('='));
                                        ln(this.divider('='));
                    push(CMD.boldOn());
                    ln(this.twoColumns('TOTAL', this.money(order.total ?? order.total_amount ?? 0, sym)));
                    push(CMD.boldOff());
                    ln(this.divider('='));

                    if ((order.payment_method || '').toLowerCase() === 'cash' && Number(order.amount_tendered) > 0 && settings.show_receipt_tendered !== false) {
                        ln(this.twoColumns('Cash Tendered', this.money(order.amount_tendered, sym)));
                        if (settings.show_receipt_change !== false) {
                            ln(this.twoColumns('Change', this.money(order.change_amount ?? 0, sym)));
                        }
                        ln(this.divider('-'));
                    }

                    ln('');

                    // Footer
                    if (settings.show_receipt_footer !== false) {
                        push(CMD.alignCenter());
                        push(CMD.boldOn());
                        ln(this.wrap(settings.receipt_footer_message || 'Thank you for your visit!'));
                        push(CMD.boldOff());
                        if (settings.receipt_return_policy) {
                            ln(this.wrap(settings.receipt_return_policy));
                        }
                    }

                    // QR Code — Large, Crystal-Clear 1-Bit Monochrome Raster QR with 4-Module Quiet Zone
                                        // QR Code — center aligned, tight spacing (no blank lines above/below the code itself)
                    const qrTarget = settings.qr_url || '';
                    if (settings.show_receipt_qr_code !== false && qrTarget) {
                        push(CMD.alignCenter());
                        ln('');
                        ln(this.divider('-'));
                        ln('SCAN TO REVIEW & RATE ORDER');
                        ln(this.divider('-'));
                        ln('');
                        // Generate enlarged, high-contrast 1-bit raster QR code bytes
                        const qrBytes = generateQrRasterBytes(qrTarget, this.charsPerLine);
                        if (qrBytes.length > 0) {
                            push(qrBytes);
                        }
                        ln('');
                        ln(this.wrap(qrTarget));
                        ln('');
                    }
                }

                // Feed extra paper so there's enough blank space to tear the
                // slip cleanly, then cut if the printer supports it.
                push(CMD.feed(4));
                if (settings.has_auto_cutter) {
                    push(CMD.cut());
                }

                                // Send just this section's bytes now, rather than waiting
                // until every section has been built.
                await this.write(bytes);
                } // end copyIdx loop

                const isLastSection = sIdx === sections.length - 1;
                const isTearOffSlip = section.type === 'kitchen' || section.type === 'barista';

                if (oneSlipAtATime && isTearOffSlip && !isLastSection) {
                    console.log(`⏸ Waiting for "${section.type}" slip to be torn off before continuing...`);
                    await this.waitForTearOff(section.type);
                }
            }

            await this.write(bytes);
            console.log('✅ Thermal receipt sent successfully.');
        } finally {
            this.isPrinting = false;
        }
    }
}

const thermalBluetoothPrinter = new ThermalBluetoothPrinter();
window.thermalBluetoothPrinter = thermalBluetoothPrinter;
export default thermalBluetoothPrinter;
