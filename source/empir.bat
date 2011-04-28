@echo off

rem **************************************************************
rem ** Empir CLI for Windows based systems (based on phing.bat) **
rem **************************************************************

rem - This script will do the following:
rem - check for PHP_COMMAND env, if found, use it.
rem - if not found detect php, if found use it, otherwise err and terminate

if "%OS%"=="Windows_NT" @setlocal

rem %~dp0 is expanded pathname of the current script under NT
set SCRIPT_DIR=@php_dir@

goto init

:init

if "%PHP_COMMAND%" == "" goto no_phpcommand

%PHP_COMMAND% -dphar.readonly=0 "%SCRIPT_DIR%\empir.php" %*

goto cleanup

:no_phpcommand
rem echo ------------------------------------------------------------------------
rem echo WARNING: Set environment var PHP_COMMAND to the location of your php.exe
rem echo          executable (e.g. C:\PHP\php.exe).  (assuming php.exe on PATH)
rem echo ------------------------------------------------------------------------
set PHP_COMMAND=@php_bin@
goto init

:cleanup
if "%OS%"=="Windows_NT" @endlocal
rem pause
