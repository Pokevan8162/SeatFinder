try {
    $scriptPath = "C:\dock_identifier_tools\p_dock_listener.ps1"
    $logPath = "C:\dock_identifier_tools\dock_logs.txt"
    $currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
    "Task scheduler starting" | Out-File $logPath -Append

    $svc = New-Object -ComObject Schedule.Service
    $svc.Connect()
    $root = $svc.GetFolder("\")

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

    $td = $svc.NewTask(0)
    $td.Settings.Compatibility = 2

    $td.RegistrationInfo.Description = "Detects dock changes while using computer."

    $td.Principal.UserId = $currentUser
    $td.Principal.LogonType = 3
    $td.Principal.RunLevel = 1

    $td.Settings.StartWhenAvailable = $true
    $td.Settings.WakeToRun = $true
    $td.Settings.DisallowStartIfOnBatteries = $false
    $td.Settings.StopIfGoingOnBatteries = $false
    $td.Settings.MultipleInstances = 0
    $td.Settings.ExecutionTimeLimit = "PT0S"
    $td.Settings.StartWhenAvailable = $true

    $logonTrigger = $td.Triggers.Create(9)  
    $logonTrigger.UserId = $currentUser

    $connectTrigger = $td.Triggers.Create(11)
    $connectTrigger.StateChange = 1
    $connectTrigger.UserId = $currentUser

    $unlockTrigger = $td.Triggers.Create(11)
    $unlockTrigger.StateChange = 8
    $unlockTrigger.UserId = $currentUser

    $action = $td.Actions.Create(0)
    $action.Path = "powershell.exe"
    $action.Arguments = "-ExecutionPolicy Bypass -NoProfile -WindowStyle Hidden -File `"$scriptPath`""


    $scriptPath2 = "C:\dock_identifier_tools\p_dock_checker.ps1"

    $svc2 = New-Object -ComObject Schedule.Service
    $svc2.Connect()

    $root2 = $svc2.GetFolder("\")

    Remove-TaskIfExists -taskName "dock_checker" -svc $svc2 -root $root2
    
    $td2 = $svc2.NewTask(0)
    $td2.Settings.Compatibility = 2

    $td2.RegistrationInfo.Description = "Checks for a dock switch while device goes offline."

    $td2.Principal.UserId = $currentUser
    $td2.Principal.LogonType = 3
    $td2.Principal.RunLevel = 1

    $td2.Settings.StartWhenAvailable = $true
    $td2.Settings.WakeToRun = $true
    $td2.Settings.DisallowStartIfOnBatteries = $false
    $td2.Settings.StopIfGoingOnBatteries = $false
    $td2.Settings.MultipleInstances = 0
    $td2.Settings.ExecutionTimeLimit = "PT0S"
    $td2.Settings.StartWhenAvailable = $true

    $logonTrigger2 = $td2.Triggers.Create(9)  
    $logonTrigger2.UserId = $currentUser

    $connectTrigger2 = $td2.Triggers.Create(11)
    $connectTrigger2.StateChange = 1
    $connectTrigger2.UserId = $currentUser

    $unlockTrigger2 = $td2.Triggers.Create(11)
    $unlockTrigger2.StateChange = 8
    $unlockTrigger2.UserId = $currentUser

    $action2 = $td2.Actions.Create(0)
    $action2.Path = "powershell.exe"
    $action2.Arguments = "-ExecutionPolicy Bypass -NoProfile -WindowStyle Hidden -File `"$scriptPath2`""

    $root.RegisterTaskDefinition(
        "dock_listener",
        $td,
        6,
        $null, $null,
        3
    )

    $root2.RegisterTaskDefinition(
        "dock_checker",
        $td2,
        6,
        $null, $null,
        3
    )

    $task = $root.GetTask("dock_listener")
    $task.Run($null) | Out-Null

    $task = $root2.GetTask("dock_checker")
    $task.Run($null) | Out-Null

    "Task scheduler finished, starting dock checker's initial run" | Out-File $logPath -Append
}
catch {
    "Error during task scheduler at $(Get-Date): $_" | Out-File $logPath -Append
}