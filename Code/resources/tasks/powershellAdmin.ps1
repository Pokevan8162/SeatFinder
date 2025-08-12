# Automatically run the script in admin

$scriptPath = Join-Path -Path $PSScriptRoot -ChildPath "create_observe_user_task.ps1"

Start-Process powershell.exe -ArgumentList "-ExecutionPolicy Bypass -File `"$scriptPath`"" -Verb RunAs