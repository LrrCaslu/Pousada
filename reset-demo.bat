@echo off
setlocal
cd /d "%~dp0"
title Reinicializar dados demonstrativos - Costa Serena
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scriptseset-demo.ps1"
set "RESULT=%ERRORLEVEL%"
echo.
if not "%RESULT%"=="0" (echo [AVISO] O reset nao foi concluido.) else (echo [OK] Dados demonstrativos recriados.)
pause
exit /b %RESULT%
