@echo off
REM PHPLame by Alex Litvinok

if "%PHPBIN%" == "" set PHPBIN=@php_bin@
if not exist "%PHPBIN%" if "%PHP_PEAR_PHP_BIN%" neq "" set PHPBIN=%PHP_PEAR_PHP_BIN%
"%PHPBIN%" "@bin_dir@\phplame" %*