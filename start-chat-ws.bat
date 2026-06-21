@echo off
cd /d "%~dp0realtime\chat-server"
if not exist .env copy .env.example .env
call npm start
