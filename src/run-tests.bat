@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/run-tests
php "%BIN_TARGET%" %*
