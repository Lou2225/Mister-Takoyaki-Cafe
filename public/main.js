const { app, BrowserWindow, Menu, ipcMain } = require('electron');
const path = require('path');
const isDev = !app.isPackaged;

let mainWindow;

function createWindow() {
    mainWindow = new BrowserWindow({
        width: 1400,
        height: 900,
        minWidth: 1024,
        minHeight: 768,
        webPreferences: {
            preload: path.join(__dirname, 'preload.js'),
            nodeIntegration: false,
            contextIsolation: true,
            enableRemoteModule: false,
        },
        icon: path.join(__dirname, 'favicon.ico'),
    });

    const startUrl = isDev
        ? 'http://localhost:5173'
        : 'https://mistertakoyakicafe.site';

    mainWindow.loadURL(startUrl);

    if (isDev) {
        mainWindow.webContents.openDevTools();
    }

    // Set zoom to 80% after page loads
    mainWindow.webContents.on('did-finish-load', () => {
        mainWindow.webContents.setZoomFactor(0.8);
    });

    mainWindow.on('closed', () => {
        mainWindow = null;
    });
}

app.on('ready', createWindow);

app.on('window-all-closed', () => {
    if (process.platform !== 'darwin') {
        app.quit();
    }
});

app.on('activate', () => {
    if (mainWindow === null) {
        createWindow();
    }
});

// Menu
const menu = [
    {
        label: 'File',
        submenu: [
            {
                label: 'Exit',
                accelerator: 'CmdOrCtrl+Q',
                click: () => {
                    app.quit();
                },
            },
        ],
    },
    {
        label: 'View',
        submenu: [
            {
                label: 'Reload',
                accelerator: 'CmdOrCtrl+R',
                click: () => {
                    if (mainWindow) mainWindow.reload();
                },
            },
            {
                label: 'Toggle DevTools',
                accelerator: 'CmdOrCtrl+Shift+I',
                click: () => {
                    if (mainWindow) mainWindow.webContents.toggleDevTools();
                },
            },
        ],
    },
];

Menu.setApplicationMenu(Menu.buildFromTemplate(menu));
