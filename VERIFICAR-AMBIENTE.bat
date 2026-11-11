@echo off
setlocal
cd /d "%~dp0"
title Pousada Costa Serena - Diagnostico
where php >nul 2>nul
if errorlevel 1 (
 echo PHP nao esta no PATH. Configure o PHP 8.3+ do ServBay.
 pause
 exit /b 1
)
php scriptserificar.php
set "RET=%ERRORLEVEL%"
echo.
pause
exit /b %RET%
