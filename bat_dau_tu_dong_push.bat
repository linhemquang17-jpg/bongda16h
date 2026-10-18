@echo off
chcp 65001 > nul
title Auto Git Push - BongDa16h
echo Dang khoi chay tien trinh tu dong theo doi va day code len GitHub...
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0.vscode\git-watch-push.ps1"
pause

