# Creates the actual dock status observer task

# Grab Current Working Directory
$cwd = Get-Location
$cwdPath = $cwd.Path

$batchFilePath = Join-Path $cwdPath "run_observe_user_activity.bat"
$logFile = Join-Path $cwdPath "..\..\storage\logs\laravel.log" | Resolve-Path

$taskName = "dock_observe_user_activity"
$message = "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - Starting scheduled task creation.`r`n"
Add-Content -Path $logFile -Value $message

# Delete existing task if present
$taskExists = Get-ScheduledTask -TaskName $taskName -ErrorAction SilentlyContinue
if ($taskExists) {
    Unregister-ScheduledTask -TaskName $taskName -Confirm:$false
    Add-Content -Path $logFile -Value "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - Existing task '$taskName' deleted.`r`n"
}

# Define the action
$action = New-ScheduledTaskAction -Execute $batchFilePath

# Define trigger — run once at midnight today, repeat every minute for 24 hours
$trigger = New-ScheduledTaskTrigger -Once -At (Get-Date).Date `
    -RepetitionInterval (New-TimeSpan -Minutes 1) `
    -RepetitionDuration (New-TimeSpan -Hours 24)

# Define principal
$principal = New-ScheduledTaskPrincipal -UserId "SYSTEM" -LogonType ServiceAccount -RunLevel Highest

# Task settings
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -StartWhenAvailable

# Register the task
Register-ScheduledTask -TaskName $taskName -Action $action -Trigger $trigger -Principal $principal -Settings $settings

Add-Content -Path $logFile -Value "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss') - Scheduled task '$taskName' created successfully.`r`n"