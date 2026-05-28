const { app, BrowserWindow } = require('electron');

function createWindow() {

    const win = new BrowserWindow({

        width: 1400,
        height: 900,

        autoHideMenuBar: true,

        webPreferences: {

            nodeIntegration: false,
            contextIsolation: true

        }

    });

    /*
    |--------------------------------------------------------------------------
    | LOAD IKOMEZA POS
    |--------------------------------------------------------------------------
    */

    win.loadURL('http://127.0.0.1:8000/dashboard');

}

app.whenReady().then(() => {

    createWindow();

});

app.on('window-all-closed', () => {

    if (process.platform !== 'darwin') {

        app.quit();

    }

});