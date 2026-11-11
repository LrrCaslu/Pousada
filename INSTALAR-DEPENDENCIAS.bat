@echo off
setlocal
cd /d "%~dp0"
title Pousada Costa Serena - Corrigir vendor/autoload.php
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\dependencias.ps1"
set "RET=%ERRORLEVEL%"
echo.
if not "%RET%"=="0" (echo [FALHA] Corrija o erro acima e tente novamente.) else (echo [OK] Dependencias Laravel preparadas.)
echo.
pause
exit /b %RET%
