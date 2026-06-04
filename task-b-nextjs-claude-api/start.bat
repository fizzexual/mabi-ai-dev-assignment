@echo off
cd /d "%~dp0"

if not exist node_modules (
    echo Installing dependencies...
    call npm install
)

if not exist .env.local (
    echo .env.local not found — creating from .env.example...
    copy .env.example .env.local
    echo.
    echo *** Open .env.local and set your ANTHROPIC_API_KEY before continuing ***
    pause
)

echo Starting dev server...
npm run dev
