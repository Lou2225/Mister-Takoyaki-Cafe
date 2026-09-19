@echo off
:: MTC Print Bridge — Installer v2
:: Registers the Print Bridge to auto-start on Windows login (current user only).
:: Run this once per PC. No admin rights required.

setlocal

set "BRIDGE_DIR=%~dp0"
set "SERVER_JS=%BRIDGE_DIR%server.js"
set "REG_KEY=HKCU\Software\Microsoft\Windows\CurrentVersion\Run"
set "REG_NAME=MTC Print Bridge"

echo.
echo  MTC Print Bridge — Installer
echo  =============================================

:: Check Node.js is installed
where node >nul 2>&1
if errorlevel 1 (
    echo.
    echo  [ERROR] Node.js is not installed or not in PATH.
    echo  Please download and install Node.js from https://nodejs.org/
    echo  then run this installer again.
    echo.
    pause
    exit /b 1
)

for /f "tokens=*" %%v in ('node --version 2^>nul') do set NODE_VERSION=%%v
echo  Found Node.js %NODE_VERSION%

:: Register auto-start via registry (no admin required, user-scoped)
:: Uses powershell to start hidden — truly detached from any console window
set "START_CMD=powershell -WindowStyle Hidden -NonInteractive -Command \"Start-Process node -ArgumentList '\"%SERVER_JS%\"' -WindowStyle Hidden\""
reg add "%REG_KEY%" /v "%REG_NAME%" /t REG_SZ /d "%START_CMD%" /f >nul 2>&1

if errorlevel 1 (
    echo.
    echo  [ERROR] Could not write to registry. Auto-start registration failed.
    echo.
    pause
    exit /b 1
)

echo  Auto-start registered: will launch on every Windows login.
echo.

:: Kill any existing stale instance first
echo  Stopping any existing instance...
for /f "tokens=5" %%a in ('netstat -aon 2^>nul ^| findstr ":9100 "') do (
    taskkill /PID %%a /F >nul 2>&1
)
timeout /t 1 /nobreak >nul

:: Start it now using PowerShell — truly detached, survives this window closing
echo  Starting Print Bridge now...
powershell -WindowStyle Hidden -NonInteractive -Command "Start-Process node -ArgumentList '\"%SERVER_JS%\"' -WindowStyle Hidden -PassThru" >nul 2>&1

:: Give it a moment to bind
timeout /t 3 /nobreak >nul

:: Confirm it's up
curl -s http://127.0.0.1:9100/ping >nul 2>&1
if errorlevel 1 (
    echo  [WARNING] Bridge may still be starting. Wait a few seconds then refresh the POS settings page.
) else (
    echo  Print Bridge is running on http://127.0.0.1:9100
)

echo.
echo  Done! You can now close this window.
echo  The Print Bridge will start automatically every time this PC turns on.
echo.
pause
