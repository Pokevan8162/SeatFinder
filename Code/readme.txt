The Office Map System

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
    Powershell script ran on the server end that keeps track of user activity
    Powershell scripts that run on the users end that update the database with the necesarry dock and activity information to keep the map up to date in all scenarios
    Log files on the server end that are created every day and deleted after 14 days
    Log file on each users computer of all error cases


Functionality:

    The office map system utilizes two Powershell scripts that run on users' laptops. One runs constantly and detects live USB removal or insertions and uploads the dock serial number to the database,
    and one checks the current connected dock at wake/sign in and uploads it to the database. The first one stays alive with a while loop that runs every minute, and it will update the database with
    'Online' with each minute. The server will check the last updated time of their status, and if it is more than 2 minutes, it will set it to 'Away'. There is another 'root' or 'setup' script
    that writes the scripts to their task scheduler and sets up the tasks automatically on the DC file server. This also grabs their userID and their full name and uploads it to the database if
    they do not exist.


Admin Usage:

    Admins can:
        - Manipulate all employee info:
            - Add/remove employees from the database
            - Edit employee ids or full names
        - Manipulate all dock info:
            - Assign docks to desks based on their serial number (interactive map where your click is where the dot is located)
            - Change desk names
            - Add new desks
        - Add new dock types (ex. thunderbolt 5) based on the dock type (ex. 40AN)
        - Change the map image


Edge Cases:

    Macbooks do not work with lenovo docks. This is an issue outside of the office map system - I believe some desks will just be designated to Macbook PCs.

    Naming conventions: I am not entirely sure on naming convention normalization (userID and username on PC metadata) - I believe it is normalized since TriCore is involved with that setup,
    but I am not entirely sure. I might develop a separate script that simply grabs the userID and username and uploads it to the database, have every PC in the office run it, and take a look at
    what that provides me.

    Probably don't run the routines on hot spares. I am not sure how the userIDs will react and may add duplicates, or maybe it works beautifully.