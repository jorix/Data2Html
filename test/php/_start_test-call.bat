@echo off
:repeat
    %1% ../vendor/codeception/codeception/codecept run --steps --no-colors
    echo.
    echo --- %1% ---
    echo.
    pause
goto repeat
