@echo off
setlocal
cd /d "%~dp0"
title Atualizar Pousada Costa Serena - Versao 3
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\atualizar-v3.ps1"
set "RET=%ERRORLEVEL%"
echo.
if not "%RET%"=="0" (echo [FALHA] Atualizacao nao concluida. Leia o erro acima.) else (echo [OK] Arquivos atualizados, veja as verificacoes acima.)
echo.
pause
exit /b %RET%
