@echo off
setlocal
cd /d "%~dp0"
title Instalador - Pousada Costa Serena
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\install.ps1"
set "RESULT=%ERRORLEVEL%"
echo.
if not "%RESULT%"=="0" (echo [FALHA] Instalacao interrompida, codigo %RESULT%.) else (echo [OK] Instalacao finalizada.)
echo.
pause
exit /b %RESULT%
