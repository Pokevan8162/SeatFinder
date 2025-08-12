The Office Map System

LOGIN: dcadmin, dcadmin8162 (user and pass)

If the website ever looks too cramped, then zoom out using ctrl -

Purpose:

    The office rennovation proposed a neighborhood system for office seating. Employees were no longer assigned to desks - they simply come in for work and sit at any desk of their choice.
    This absolutely works with the office culture and the goal of Design Collaborative, but it poses a main issue - finding employees. If the goal of this office rennovation is to advocate for
    collaboration, then we must be able to locate where someone is sitting in order to collaborate with them.

    The system can also be used to check for occupancy of privacy rooms, since those rooms will have a dock.

    The system can also be used for grabbing data on employee seating locations. We can grab who uses what desks most, what studios use what desks, what desks are left unused, etc.


How to start it:

    - Open the XAMPP shortcut found in the desktop-officemap folder
    - Start both Apache and MySQL for hosting the web server and the database
    - Find/run the terminal in Visual Studio Code and run 'npm run build' for website styling
    - Ensure that php exists in your SYSTEM PATH variables
    - The server status checker will automatically run at midnight, but if you need the status checker to run as soon as possible, run the 'powershellAdmin' powershell file (right click -
        run with powershell) in htdocs/resources/tasks/powershellAdmin.ps1


How to stop/uninstall it:

    - There will be an uninstaller written at the directory that the scripts are stored at on the user's computer (C:\dock_identifier_tools)
    - Restart the user's PC to fully remove the powershell routines


Structure:

    MySQL Database hosted locally on the PC that contains:
        Employee name information (userID and full name, ex. estoller Stoller, Evan), the desk they are sitting at, and their 'Away' or 'Online' presence
        Dock serial number, associated desk, the dock type (ex. 40AN, its on the back of the dock), and its x-y position on the map
        Dock types (ex. 40AN), its name, and how many are in use
        Statuses of users (ex. 'Online' or 'Away'), userID, and created_at / updated_at times
    Web interface with the backend
        Utilizes the UserIDs and their desk to create a map that visually shows where users are depending on the dock that they are plugged into
    Powershell script that user will run that will install the two 'listener' and 'checker' scripts that will run at log on/computer turn on of the user that ran it (ex. if estoller runs the
        powershell command, the task will only run on log on of userid estoller)
    Powershell script ran on the server end that keeps track of user activity
    Powershell scripts that run on the users end that update the database with the necesarry dock and activity information to keep the map up to date in all scenarios
    Log files on the server end that are created every day and deleted after 14 days
    Log file on each users computer of all error cases
    Website URL hosted on Stache/Auto open on Google/Edge (or both)


Functionality:

    The office map system utilizes two Powershell scripts that run on users' laptops. One runs constantly and detects live USB removal or insertions and uploads the dock serial number to the database,
        and one checks the current connected dock at wake/sign in and uploads it to the database. The first one stays alive with a while loop that runs every minute, and it will update the database with
        'Online' with each minute. The server will check the last updated time of their status, and if it is more than 2 minutes, it will set it to 'Away'. There is another 'root' or 'setup' script
        that writes the scripts to their task scheduler and sets up the tasks automatically on the DC file server. This also grabs their userID and their full name and uploads it to the database if
        they do not exist.
    The functionality relies upon dock serial numbers, dock types, and desk positions being kept up to date. There are three examples of when this intervention wil need to happen:
        - Office Rennovation. When the office is ready to be occupied again, the admin will have to have the users run the p_root PowerShell script on their PCs to auto-upload all dock serial numbers
            and user naming information to the database, and the admin will have to go through the admin panel to associate each dock serial number with a physical desk position.
        - Switching a dock in/out: If a dock fails and needs replaced


Admin Usage:

    Admins can:
        - Manipulate all employee info:
            - Add/remove employees from the database
            - Edit employee ids or full names
            - Note for View All Desks in Admin Panel: This allows the Admin to see all desks, active or not, and edit their dock and desk name
                data. Admins can hover their mouse over a desk and see the desk name, and they can also click on it to edit the data. If they
                want to edit the location of the desk, they will need to hit the View All Desks button again to get rid of all the points on
                the map and click where they would like the last selected desk to go.
        - Manipulate all dock info:
            - Assign docks to desks based on their serial number (interactive map where your click is where the dot is located)
            - Change desk names
            - Add new desks
        - Add new dock types (ex. thunderbolt 5) based on the dock type (ex. 40AN)
        - Change the map image


Admin Rules:

    Adding a new desk: Follow the naming convention starting with 001 for simplicity.
    Adding a private room desk: Follow the naming convention starting with P-001 for simplicity.
    Adding a mac desk: Follow the naming convention starting with M-001 for simplicity.


Edge Cases:

    Macbooks do not work with lenovo docks. This is an issue outside of the office map system - admins can define Mac Desks in the dock editor and users can find Mac Desks in the user interface.

    Naming conventions: I am not entirely sure on naming convention normalization (userID and username on PC metadata) - I believe it is normalized since TriCore is involved with that setup,
    but I am not entirely sure. A quick way to check and validate this is a script that grabs the users userID and fullName (just like in dock checker when it detects a new dock), uploads it to
    the database, have every PC in the office run it, and take a look at all of the names.

    Probably don't run the routines on hot spares. I am not sure how the userIDs will react and may add duplicates, or maybe it works beautifully.

    When a new dock type is added, users will have to restart their computers for the script to update to include the new dock info, or maybe make it update every 24 hours with a task?


To Do:

    Solve high CPU usage issue (might not exist in production but still make sure)
        I experienced high CPU usage by deleting and recreating the listener and checker tasks a bunch of times during development,
        making the script run heavier and heavier each time I believe. I do not think this is an issue in production because the script
        will only run once, and the old deleted scripts (that still run, causing this upward trend of CPU usage for each recreation of the
        task) delete themselves at restart of PC anyway.


Suggestions/Tiny Bugs:

    Weird case: Increment, change name, click on map, submit name, dot disappears. Might be necessary functionality for updating dot location
        though to show the admin that the location was not submit
    Make dot clickable that shows status, last updated, and desk number
    Status: custom offline for heads down time can be a circle with a line through it, lunch can have an on lunch pop up, etc etc. allow for
        user customization
    People can choose custom dot colors or ‘avatars’
    Name parsing with dashes and suffixes etc? Add data machine learning for desks unused, studio color coded icons
    Manage used docks: Users can select docks based on their name (ex. Thunderbolt 4) and set them to in use or out of use. When they are
        out of use, they are taken out of the ‘array’ that the script grabs and checks for to save resources
    Fix ‘In Use’ functionality (increment the in use counter in the docks table in the database when we add a new dock of that type and
        decrement when removing one (this is useful for knowing how many of each dock type we have (although thats technically more of an
        inventory system thing)))
    In case dock_listener is causing race conditions (ex. multiple insertons and removals in quick succession, listener runs before WMI grabs the usb value, etc), there are two solutions:
        1. $newSerial = $null
            for ($i=0; $i -lt 4 -and -not $newSerial; $i++) {
                Start-Sleep -Seconds (2 + $i)    # 2s, 3s, 4s, 5s
                $device = Get-CimInstance Win32_PnPEntity | Where-Object { $_.DeviceID -match [regex]::Escape($dockType) } | Select-Object -First 1
                if ($device) { $newSerial = $device.DeviceID }
            }
            if (-not $newSerial) { "No serial after retries" | Out-File $logPath -Append; return }

            This concept can prevent these race conditions
        2. Get rid of dock_listener entirely, and loop dock_checker every minute to update the DB.
    Weird error saying 'statuses' does not exist in the database, does not hurt functionality at all