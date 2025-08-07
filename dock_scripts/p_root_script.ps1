# The listener will detect any USB insertions/removals while the laptop is online and handle the database updates accordingly.
function writeListener {
    param($scriptPath)
    $scriptContent = @'
# Clear existing WMI subscriptions
Get-EventSubscriber | Where-Object { $_.SourceIdentifier -like "*Dock*" } | Unregister-Event

# Path for serial number text file
$currSerialPath = 'C:\dock_identifier_tools\currDockSerialNum.txt'

# Path for log file
$logPath = "C:\dock_identifier_tools\dock_logs.txt"

# Grab all in-use dock metadata from DB so the USB listener knows what to look for
try {
    $docks = Invoke-RestMethod -Uri "http://10.92.4.216:8080/api/getDockInfo"
}
catch {
    "Error connecting to server in listener $(Get-Date): $_" | Out-File C:\Users\estoller\Desktop\proj\powershell\d_dock_logs.txt -Append
}

# Ensures that we actually catch erorrs
$ErrorActionPreference = "Stop"

foreach ($dock in $docks) {
    $dockName = $dock.name
    $dockType = $dock.type

    # Insertion Event
    Register-WmiEvent -Query "SELECT * FROM __InstanceCreationEvent WITHIN 2 WHERE TargetInstance ISA 'Win32_PnPEntity' AND TargetInstance.DeviceID LIKE '%$dockType%'" -SourceIdentifier "$dockName Dock Insert" -Action {
        "Registering insert event with $dockName." | Out-File C:\Users\estoller\Desktop\proj\powershell\d_dock_logs.txt -Append
        $dockType = $event.MessageData.dockType
        $dockName = $event.MessageData.dockName
        $currSerialPath = $event.MessageData.currSerialPath
        try {
            # Save serial number to file ----
            $newSerial = Get-WmiObject Win32_PnPEntity | Where-Object {
                $_.DeviceID -match $dockType
            }
            $newSerial = $newSerial -replace '[\\\r\n"]', ''
            if ($newSerial -match "$dockType.*") {
                $newSerial = $matches[0]
            }
            if (Test-Path $currSerialPath) {
                $tempSerial = (Get-Content $currSerialPath -Raw).Trim()
                if ($tempSerial) {
                    $currSerial = $tempSerial
                }
            }
            # ------------------------------
            # If the newly detected serial number is different from the current one, then the user switched desks. Handle this accordingly.
            $type = $newSerial.Substring(0, 4) # Grab dock type for parsing in DB
            if ($currSerial -ne $newSerial) {
                # Grab the users ID and full name from their computer metadata ----
                $userID = $env:USERNAME
                $userInfo = Get-CimInstance -ClassName Win32_UserAccount -Filter "Name='$userID'"
                $fullName = $userInfo.FullName
                # -----------------------------------------------------------------
                # If user does not exist, create them. If dock does not exist, create it. Otherwise, update the desk for the employee and
                # remove current employees from the desk if they exist.
                $uri = "http://10.92.4.216:8080/api/updateDeskInfo"
                $headers = @{ "Content-Type" = "application/json" }
                $body = @{
                    userID     = $userID
                    serial_num = $newSerial
                    type       = $type
                    fullName   = $fullName
                } | ConvertTo-Json
                $response = Invoke-RestMethod -Uri $uri -Method Post -Headers $headers -Body $body
                "Update Dock Info Response: $response" | Out-File $logPath -Append
            }
            "$dockName ($newSerial) inserted at $(Get-Date)" | Out-File $logPath -Append
            Set-Content -Path $currSerialPath -Value $newSerial # Actually write the new serial number now
            "Wrote serial number to file." | Out-File $logPath -Append
        }
        catch {
            "Error during insert handler at $(Get-Date): $_" | Out-File $logPath -Append
        }
    } -MessageData @{ dockType = $dockType; dockName = $dockName; currSerialPath = $currSerialPath }

    # Removal Event
    Register-WmiEvent -Query "SELECT * FROM __InstanceDeletionEvent WITHIN 2 WHERE TargetInstance ISA 'Win32_PnPEntity' AND TargetInstance.DeviceID LIKE '%$dockType%'" -SourceIdentifier "$dockName Dock Remove" -Action {
        "Registering remove event with $dockName." | Out-File $logPath -Append
        $dockName = $event.MessageData.dockName
        $currSerialPath = $event.MessageData.currSerialPath

        try {
            $userID = $env:USERNAME

            # Find the desk at this userID and set the employee to None. and set the employee's status to Away.
            $uri = "http://10.92.4.216:8080/api/removeEmployeeFromDesk"
            $headers = @{ "Content-Type" = "application/json" }

            $body = @{
                userID = $userID
            } | ConvertTo-Json

            $response = Invoke-RestMethod -Uri $uri -Method Post -Headers $headers -Body $body

            Set-Content -Path $currSerialPath -Value '' # Erase the serial number contents because the employee no longer sits at a desk
            "$dockName removed at $(Get-Date)" | Out-File $logPath -Append
            "Remove Dock Info Response: $response" | Out-File $logPath -Append
        }
        catch {
            "Error during remove handler at $(Get-Date): $_" | Out-File $logPath -Append
        }
    } -MessageData @{ dockType = $dockType; dockName = $dockName; currSerialPath = $currSerialPath }
}

# Keep script alive
while ($true) {
    Start-Sleep -Seconds 60
    # Set user status to be online
    try {
        $userID  = $env:USERNAME
        $uri     = 'http://10.92.4.216:8080/api/setOnline'
        $headers = @{ 'Content-Type' = 'application/json' }
        $body    = @{ userID = $userID } | ConvertTo-Json

        Invoke-RestMethod -Uri $uri -Method Post -Headers $headers -Body $body
    } catch {
        "Error calling setOnline at $(Get-Date): $_" | Out-File $logPath -Append
    }
}
'@
    Set-Content -Path $scriptPath -Value $scriptContent -Encoding UTF8
}

# The checker will detect any different dock serial numbers at computer wake/log in and handle the database updates accordingly. This ensures that, if the user
# plugs in to any dock with their laptop closed, we can still handle the changes in the database whenever the computer is turned on (usually seconds
# after plug in)
function writeChecker {
    param($scriptPath)
    $scriptContent = @'
# If dock_listener is not running (crash from error, crash from not connecting to server on home wifi, etc), make it run
$taskName = "dock_listener"
$schedule = New-Object -ComObject Schedule.Service
$schedule.Connect()
$root = $schedule.GetFolder("\")
$task = $root.GetTask($taskName)

# Check if task is running
$running = $false
$runningTasks = $schedule.GetRunningTasks(0)
foreach ($t in $runningTasks) {
    if ($t.Name -eq $taskName) {
        $running = $true
        break
    }
}

if (-not $running) {
    $task.Run($null) | Out-Null
    Write-Output "Task '$taskName' was not running and has been started."
} else {
    Write-Output "Task '$taskName' is already running."
}

# Set user status to be online
try {
    $userID  = $env:USERNAME
    $uri     = 'http://10.92.4.216:8080/api/setOnline'
    $headers = @{ 'Content-Type' = 'application/json' }
    $body    = @{ userID = $userID } | ConvertTo-Json

    Invoke-RestMethod -Uri $uri -Method Post -Headers $headers -Body $body
} catch {
    "Error calling setOnline at $(Get-Date): $_" | Out-File $logPath -Append
}

# Path for log file and ping to logs to let know it is running
$logPath = "C:\dock_identifier_tools\dock_logs.txt"
"Running dock checker" | Out-File $logPath -Append

# Path for current connected serial number text file
$currSerialPath = "C:\dock_identifier_tools\currDockSerialNum.txt"
$currSerial = ''
$newSerial = ''

$maxLines = 500
$trimLines = 300

# Removes 300 oldest lines from the log file once we hit the 500 'maxLines' limit
if (Test-Path $logPath) {
    $lines = Get-Content $logPath
    if ($lines.Count -ge $maxLines) {
        $lines[$trimLines..($lines.Count - 1)] | Set-Content $logPath
    }
}

# Grab dock info from server and check for a connected dock at wake
try {
    $docks = Invoke-RestMethod -Uri "http://10.92.4.216:8080/api/getDockInfo"
}
catch {
    "Error connecting to server in checker $(Get-Date): $_" | Out-File C:\Users\estoller\Desktop\proj\powershell\d_dock_logs.txt -Append
}
# Look for a Thunderbolt dock already connected to the laptop at wake
foreach ($dock in $docks) {
    $dockValueFound = Get-WmiObject Win32_PnPEntity | Where-Object { $_.DeviceID -match $dock.type }
    if ($dockValueFound) {
        $dockType = $dock.type
        "Connected dock found: $dockValueFound" | Out-File $logPath -Append
        $newSerial = $dockValueFound
    }
}

# If we have found a dock serial number in the USB insertions, handle it
if ($newSerial) {
    $newSerial = $newSerial -replace '[\\\r\n"]', ''
    if ($newSerial -match "$dockType.*") {
        $newSerial = $matches[0]
        "Extracted serial: $newSerial" | Out-File $logPath -Append
    }
    if (Test-Path $currSerialPath) {
        $tempSerial = (Get-Content $currSerialPath -Raw).Trim()
        if ($tempSerial) {
            $currSerial = $tempSerial
        }
    } else {
        New-Item -Path $currSerialPath -ItemType File -Force
    }
    if ($currSerial -ne $newSerial) {
        "Curr Serial: $currSerial, new serial: $newSerial" | Out-File $logPath -Append
        "Different serial detected at $(Get-Date), updating DB" | Out-File $logPath -Append
        # The user switched desks. Handle the database updates accordingly.
        try {
            # Grab the user information from the computer metadata and update the database
            $userID = $env:USERNAME
            $userInfo = Get-CimInstance -ClassName Win32_UserAccount -Filter "Name='$userID'"
            $fullName = $userInfo.FullName
            $type = $newSerial.Substring(0, 4)
            $uri = "http://10.92.4.216:8080/api/updateDeskInfo"
            $headers = @{ "Content-Type" = "application/json" }
            $body = @{
                userID     = $userID
                serial_num = $newSerial
                type       = $type
                fullName   = $fullName
            } | ConvertTo-Json
            $response = Invoke-RestMethod -Uri $uri -Method Post -Headers $headers -Body $body
            "Update Dock Info Response: $response" | Out-File $logPath -Append
        }
        catch {
            "Error during dock checker update handler at $(Get-Date): $_" | Out-File $logPath -Append
        }
    }
    # Write the new serial number to the text file at the serial path
    Set-Content -Path $currSerialPath -Value $newSerial
}
'@
    Set-Content -Path $scriptPath -Value $scriptContent -Encoding UTF8
}

# Fully erases and uninstalls all programs and tasks created by this software.
function writeKiller {
    param($scriptPath)
    $scriptContent = @'
# Ensure CMD is in admin priviliges
if (-not ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()
).IsInRole([Security.Principal.WindowsBuiltinRole]::Administrator)) {
    Start-Process powershell "-NoProfile -ExecutionPolicy Bypass -File `"$PSCommandPath`"" -Verb RunAs
    exit
}

$scriptFolderPath = "C:\dock_identifier_tools"
$svc = New-Object -ComObject Schedule.Service
$svc.Connect()
$root = $svc.GetFolder("\")

# Remove tasks from task scheduler
function Remove-TaskIfExists {
    param(
        [string]$taskName,
        $svc,
        $root
    )

    try {
        $existingTask = $root.GetTask($taskName)
        if ($existingTask) {
            $runningTasks = $svc.GetRunningTasks(0)
            foreach ($task in $runningTasks) {
                if ($task.Name -eq $taskName) {
                    try {
                        $task.Stop()
                    }
                    catch {
                    }
                }
            }

            $root.DeleteTask($taskName, 0)
        }
    }
    catch {
    }
    $eventIds = @(
        "Dock Insert", "Dock Remove"
    )

    foreach ($id in $eventIds) {
        Unregister-Event -SourceIdentifier $id -ErrorAction SilentlyContinue
    }
}

Remove-TaskIfExists -taskName "dock_listener" -svc $svc -root $root
Remove-TaskIfExists -taskName "dock_checker" -svc $svc -root $root

# Delete all files in the script folder path
if ((Test-Path $scriptFolderPath)) {
    Remove-Item -Path $scriptFolderPath -Recurse -Force
}
'@
    Set-Content -Path $scriptPath -Value $scriptContent -Encoding UTF8
}

try {
    $logPath = "C:\dock_identifier_tools\dock_logs.txt"
    New-Item -Path $logPath -ItemType File -Force | Out-Null
    "Root script starting" | Out-File $logPath -Append

    # Path to the folder and script
    $folderPath = "\\DC-FS\HDrive\Administration\IT\dock_scripts"
    $scriptName = "p_dock_task_scheduler.ps1"

    $scriptFolderPath = "C:\dock_identifier_tools"

    if ((Test-Path $scriptFolderPath)) {
        Remove-Item -Path $scriptFolderPath -Recurse -Force
    }
    New-Item -Path $scriptFolderPath -ItemType Directory -Force | Out-Null
    New-Item -Path "C:\dock_identifier_tools\p_dock_listener.ps1" -ItemType File -Force | Out-Null
    (Get-Item "C:\dock_identifier_tools\p_dock_listener.ps1").Attributes += 'Hidden'
    writeListener -scriptPath "C:\dock_identifier_tools\p_dock_listener.ps1"
    New-Item -Path "C:\dock_identifier_tools\p_dock_checker.ps1" -ItemType File -Force | Out-Null
    (Get-Item "C:\dock_identifier_tools\p_dock_checker.ps1").Attributes += 'Hidden'
    writeChecker -scriptPath "C:\dock_identifier_tools\p_dock_checker.ps1"
    New-Item -Path "C:\dock_identifier_tools\uninstall.ps1" -ItemType File -Force | Out-Null
    writeKiller -scriptPath "C:\dock_identifier_tools\uninstall.ps1"
    New-Item -Path "C:\dock_identifier_tools\currDockSerialNum.txt" -ItemType File -Force | Out-Null
    (Get-Item "C:\dock_identifier_tools\currDockSerialNum.txt").Attributes += 'Hidden'

    $cmd = @"
Set-Location -Path "$folderPath"
.\$scriptName
"@
    "Root script finished, running task scheduler" | Out-File $logPath -Append
    Start-Process powershell -Verb RunAs -ArgumentList "-ExecutionPolicy", "Bypass", "-WindowStyle", "Hidden", "-Command", $cmd
}
catch {
    "Error during task scheduler at $(Get-Date): $_" | Out-File $logPath -Append
}