@extends('layouts.default')
@section('content')
    <style>
        .select2-container--default .select2-selection--single {
            background-color: rgb(255, 255, 255);
            border: 1px solid rgb(90, 88, 84);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            height: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            outline: none;
            margin-top: 0.5rem;
        }

        .select2-container--open .select2-dropdown {
            z-index: 9999;
            /* or another high value */
        }

        .select2-container--default .select2-selection--single:focus {
            border-color: rgb(0, 168, 213);
            box-shadow: 0 0 0 3px rgba(0, 168, 213, 0.4);
        }

        .select2-container--default .select2-selection--single .select2-selection__rendered {
            color: #000;
            line-height: 1.75rem;
        }

        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 100%;
            top: 0;
            right: 0.75rem;
            margin-top: 0.25rem;
        }

        .select2-container {
            min-width: 200px;
        }

        .point {
            width: 2rem;
            height: 2rem;
        }

        @media (max-width: 640px) {
            .point {
                width: 1.25rem;
                height: 1.25rem;
            }
        }

        @media (min-width: 640px) {
            .point {
                width: 1.35rem;
                height: 1.35rem;
            }
        }

        @media (min-width: 768px) {
            .point {
                width: 1.5rem;
                height: 1.5rem;
            }
        }

        @media (min-width: 1024px) {
            .point {
                width: 2rem;
                height: 2rem;
            }
        }
    </style>
    <script>
        let currentXDotPos = 0; // Save current X position of dot for submission
        let currentYDotPos = 0; // Save current Y position of dot for submission
        let checkbox = false; // Track if the checkbox is checked
        let showAllEmployees =
            false; // Will be true when we are viewingAll, and false otherwise. Utilized for 'reset' button and removing map click functionality in viewAll mode.
        let viewAllMode =
            false; // Helps prevent unwanted point removals and handling in deskDropdown when we click on a point after hitting the 'viewAllDesksBtn'
        let bottomXValue; // Incremented to show all null desks in clickable format for viewAllDesks

        // Save each desk option
        const dockOptions = [
            @foreach ($docks as $dock)
                "{{ $dock->desk }}",
            @endforeach
        ];

        // Show an employee position on the map
        function showEmployeePosition(x, y) {
            const pt = document.getElementById('point');
            if (pt) {
                pt.remove();
            }
            if (!x || !y) {
                return;
            }
            let map = document.getElementById('map-div');
            let point = document.createElement('div');
            point.className =
                "point absolute bg-red-600 rounded-full opacity-80 -translate-x-1/2 -translate-y-1/2 z-10";
            point.style.position = "absolute";

            // x and y is set as a percent, not a set value
            point.style.left = `${x}%`;
            point.style.top = `${y}%`;

            currentXDotPos = x; // Update current X position
            currentYDotPos = y; // Update current Y position

            point.id = 'point';

            map.appendChild(point);
        }
        function showLoading() {
            const div = document.getElementById('mainDiv');
            const loading = document.createElement('div');
            loading.id = 'loading';
            loading.innerHTML = `
                <div class="bg-[rgb(243,112,33)] p-2 rounded-2xl flex items-center fixed right-5 bottom-17 space-x-2 mt-2">
                    <svg class="animate-spin h-10 w-10 text-gray-700" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="white" stroke-width="4"></circle>
                    <path class="opacity-100" fill="#93c5fd" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span class="text-sm text-white font-semibold">Loading...</span>
                </div>
                `;
            div.appendChild(loading);
        }
        function hideLoading() {
            const loading = document.getElementById('loading');
            if (loading) loading.remove();
        }
        window.showAlertWithTimeout = function(alertHtml, color, timeout) {
            // Map of color names to Tailwind classes
            const colorClasses = {
                red: 'bg-red-100 border border-red-400 text-red-800',
                green: 'bg-green-100 border border-green-400 text-green-800',
                yellow: 'bg-yellow-100 border border-yellow-400 text-yellow-800',
                blue: 'bg-blue-100 border border-blue-400 text-blue-800'
                // Add more as needed
            };

            let alertBox = document.createElement('div');
            alertBox.id = 'alert';
            alertBox.className =
                `fixed alert alert-danger w-3/10 bottom-10 z-10 left-1/2 transform -translate-x-1/2 mt-4 p-4 ${colorClasses[color] || colorClasses.red} rounded fixed-bottom-alert transition-opacity duration-3000`;
            alertBox.innerHTML = alertHtml;
            document.getElementById('mainDiv').appendChild(alertBox);

            // Fade out after 1 second
            setTimeout(() => {
                alertBox.classList.add('opacity-0');
            }, 3000); // wait 3 seconds before fading

            // Auto-remove the alert after timeout
            setTimeout(() => {
                alertBox.remove();
            }, timeout);
        }

        // Show multiple employee positions on the map (does not remove the last point when a new point is inserted, allows for desk name visibility when hovering, allows for click funcitonality
            // to quickly edit the clicked dot)
        function showMultipleEmployeePositions(x, y, deskName, serial_num, type, id) {
            // If we have a no position desk, place them in a row on bottom
            if (x == null) {
                x = bottomXValue;
                bottomXValue += 10;
                y = 95;
            }
            let map = document.getElementById('map-div');
            let point = document.createElement('div');
            point.className =
                "point absolute bg-red-600 rounded-full opacity-80 -translate-x-1/2 -translate-y-1/2 z-10 cursor-pointer";
            point.style.position = "absolute";

            // x and y is set as a percent, not a set value
            point.style.left = `${x}%`;
            point.style.top = `${y}%`;
            point.id = 'point';
            point.name = 'point';

            // Create a tooltip div for the deskName when hovering over the point
            let tooltip = document.createElement('div');
            tooltip.className =
                "tooltip absolute w-30 text-center bg-black text-white text-xs rounded px-1 py-0.5 opacity-0 pointer-events-none transition-opacity";
            tooltip.innerText = deskName;
            tooltip.style.transform = "translate(-50%, -100%)"; // Position above the point
            // Position tooltip relative to point
            tooltip.style.left = '50%';
            tooltip.style.bottom = '100%'; // just above the point
            // Append tooltip inside point (relative positioning will help)
            point.style.position = 'absolute';
            point.style.display = 'inline-block';
            point.appendChild(tooltip);

            // Show tooltip on hover
            point.addEventListener('mouseenter', () => {
                tooltip.style.opacity = '1';
                tooltip.style.pointerEvents = 'auto';
            });
            point.addEventListener('mouseleave', () => {
                tooltip.style.opacity = '0';
                tooltip.style.pointerEvents = 'none';
            });
            // Add click listener handling so the admin can click the desk and edit its information quickly
            point.addEventListener('click', function() {
                viewAllMode = true;
                const value =
                    `${serial_num},${type},${id}`;
                $('#deskDropdown').val(value).trigger('change');
            });

            map.appendChild(point);
        }

        // Used to clear the points in 'viewAll' mode
        function deleteAllPoints() {
            const points = document.querySelectorAll('.point');
            points.forEach(pt => {
                pt.remove();
            });
        }

        document.addEventListener("DOMContentLoaded", () => {
            // Initialize dockdeskDropdown and hide it
            $('#dockdeskDropdown').select2({
                placeholder: "-- Type a dock type --",
                allowClear: false,
                width: '100%',
                height: '100%',
                tags: true, // allows user from submitting their own text
                dropdownParent: $('#dynamicForm')
            });
            $('#dockdeskDropdown').next('.select2-container').hide();

            // Fill dockTypes dropdown
            fetch('/api/getAllDockTypes')
                .then(response => response.json())
                .then(data => {
                    let option2 = new Option('None.', 'None.', true,
                        true);
                    data.forEach(function(data) {
                        console.log(data.type);
                        let option = new Option(data.type, data.type, false, false);
                        $('#dockdeskDropdown').append(option);
                    });
                    $('#dockdeskDropdown').append(option2);
                })
                .catch(error => {
                    console.error('Error fetching dock types:', error);
                });

            // Grab the map image and set it to the image
            fetch('/api/getMapImage').then(res => res.json()).then(data => {
                    hideLoading();
                    document.getElementById('map').src = data.image_path;
                })
                .catch(err => {
                    hideLoading();
                    console.error('Error loading map image:', err);
                });
            showLoading();

            // Prevent submission from just hitting the enter key
            document.querySelector('form').addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                }
            });

            // On change of the serial number, grab the prefix and autofill the type
            document.getElementById('serial_num').addEventListener('change', function() {
                // Take the first four characters of the serial number, and if it matches anything in dock types, then autofill with that value
                let serial_num = this.value.trim();
                if (serial_num.length >= 4) {
                    // Take the first four characters of the serial number
                    let prefix = serial_num.substring(0, 4);
                    // Check if the prefix matches any dock types
                    const dockdeskDropdown = $('#dockdeskDropdown');
                    let prefixFound = false;

                    dockdeskDropdown.find('option').each(function() {
                        if ($(this).val().includes(prefix) && $(this).val() !== 'None.') {
                            prefixFound = true;
                            return false; // Break out of .each loop early
                        }
                    });

                    if (prefixFound) {
                        // Autofill the input with the matching dock type
                        dockdeskDropdown.val(prefix).trigger('change');
                        console.log('prefix: ' + prefix);
                    } else {
                        dockdeskDropdown.val('None.').trigger('change');
                    }
                }
            });

            // Click listener for inserting new dock locations
            document.getElementById('map').addEventListener('click', function(e) {
                // Disable this functionality in viewAllMode
                if (!showAllEmployees) {
                    const rect = this.getBoundingClientRect();
                    const x = e.clientX - rect.left; // X within the element
                    const y = e.clientY - rect.top; // Y within the element

                    currentXDotPos = (x / rect.width) * 100 + 1.5; // Adding 1.5% to center dot on cursor
                    currentYDotPos = (y / rect.height) * 100;

                    // Example: Save to a variable or send to backend
                    const coords = {
                        x_percent: currentXDotPos,
                        y_percent: currentYDotPos
                    };

                    showEmployeePosition(currentXDotPos, currentYDotPos);
                }
            });

            let employeeInputDiv = document.getElementById('employeeInput');
            // initialize select2 dropdown that allows typing for desk dropdown list
            $(document).ready(function() {
                $('#deskDropdown').select2({
                    placeholder: "-- Type a name --",
                    allowClear: false,
                    width: '100%',
                    height: '100%',
                    tags: false, // disallows user from submitting their own text
                    dropdownParent: $('#dynamicForm')
                });
            });

            // Listener for 'viewAllDesksBtn'
            document.getElementById('viewAllDesksBtn').addEventListener('click', function() {
                if (showAllEmployees === false) {
                    // Remove current point
                    const pt = document.getElementById('point');
                    if (pt) {
                        pt.remove();
                    }
                    // Uncheck increment checkbox
                    document.getElementById('incrementCheckbox').checked = false;

                    // Reset type dropdown
                    const dropdown = document.getElementById('deskDropdown');
                    dropdown.value = 'None.';
                    const event = new Event('change', {
                        bubbles: true
                    });
                    dropdown.dispatchEvent(event);

                    // Uncheck edit desk name checkbox
                    const checkbox = document.getElementById('editDeskNameCheckbox');
                    checkbox.checked = false;
                    const event2 = new Event('change', {
                        bubbles: true
                    });
                    checkbox.dispatchEvent(event2);

                    // 'Switch' to provide functionality for the reset part in the else statement
                    showAllEmployees = true;
                    bottomXValue = 10; // Reset the bottom x value for null desks
                    fetch(`/api/showAllDesks`)
                        .then(res => res.json())
                        .then(desks => {
                            hideLoading();
                            desks.forEach(desk => {
                                showMultipleEmployeePositions(desk.x, desk.y, desk.desk, desk
                                    .serial_num, desk.type, desk.id);
                            });
                        });
                    showLoading();
                } else {
                    showAllEmployees = false;
                    deleteAllPoints();
                }
            });

            // Checkbox for incrementing the desk input automatically based on the largest desk value
            document.getElementById('incrementCheckbox').addEventListener('change', function() {
                if (this.checked) {
                    showAllEmployees = true;
                    document.getElementById('viewAllDesksBtn').click();
                    document.getElementById('addNewDeskCheckbox').classList.add('hidden');
                    document.getElementById('addNewDeskCheckboxLabel').classList.add('hidden');
                    const checkbox = document.getElementById('editDeskNameCheckbox');
                    checkbox.checked = false;
                    const event = new Event('change', {
                        bubbles: true
                    });
                    checkbox.dispatchEvent(event);
                    document.getElementById('editDeskNameCheckbox').classList.add('hidden');
                    document.getElementById('editDeskNameCheckboxLabel').classList.add('hidden');
                    document.getElementById('updateDeskName').classList.add('hidden');
                    // Increment the desk number based on the largest desk value
                    const dockOptions = $('#deskDropdown option').map(function() {
                        return $(this).text().trim();
                    }).get();
                    let largestDeskNumber = Math.max(
                        ...dockOptions
                        .map(d => {
                            const match = d.match(/\d+$/);
                            return match ? parseInt(match[0]) : NaN;
                        })
                        .filter(n => !isNaN(n))
                    );
                    let newDeskNumber = largestDeskNumber + 1;
                    let newDeskName = `00${newDeskNumber}`;
                    // Remove extra leading zeros if necessary
                    while (newDeskName.length > 3) {
                        newDeskName = newDeskName.slice(1);
                    }
                    $('#deskDropdown').append(new Option(newDeskName, newDeskName, true, true));
                    $('#deskDropdown').val(newDeskName).trigger('change');
                } else {
                    document.getElementById('addNewDeskCheckbox').classList.remove('hidden');
                    document.getElementById('addNewDeskCheckboxLabel').classList.remove('hidden');
                    document.getElementById('editDeskNameCheckbox').classList.remove('hidden');
                    document.getElementById('editDeskNameCheckboxLabel').classList.remove('hidden');
                    // Remove all options that are not in dockOptions (newly typed options by the user that have not been submit)
                    $('#deskDropdown option').each(function() {
                        const optionText = $(this).text().trim();
                        if (!dockOptions.includes(optionText) && optionText !==
                            '-- Type a name --') {
                            $(this).remove();
                        }
                    });
                    // Reset to the first desk
                    $('#deskDropdown').val('None.').trigger('change');
                }
            });

            // Checkbox for adding a new desk (prevents the user from selecting old desk values in the dropdown)
            document.getElementById('addNewDeskCheckbox').addEventListener('change', function() {
                $('#deskDropdown').select2('destroy');

                if (this.checked) {
                    showAllEmployees = true;
                    document.getElementById('viewAllDesksBtn').click();
                    document.getElementById('incrementCheckbox').classList.add('hidden');
                    document.getElementById('incrementCheckboxLabel').classList.add('hidden');
                    const checkbox = document.getElementById('editDeskNameCheckbox');
                    checkbox.checked = false;
                    const event = new Event('change', {
                        bubbles: true
                    });
                    checkbox.dispatchEvent(event);
                    document.getElementById('editDeskNameCheckbox').classList.add('hidden');
                    document.getElementById('editDeskNameCheckboxLabel').classList.add('hidden');
                    document.getElementById('updateDeskName').classList.add('hidden');
                    $('#deskDropdown').select2({
                        placeholder: "-- Type a name --",
                        tags: true,
                        dropdownParent: $('#dynamicForm'),
                        width: '100%'
                    });

                    document.getElementById('serialNumLabel').classList.add('hidden');
                    document.getElementById('serial_num').classList.add('hidden');
                    document.getElementById('dockTypeLabel').classList.add('hidden');
                    $('#dockdeskDropdown').next('.select2-container').hide();
                    document.getElementById('submitBtn').classList.add('hidden');
                    // clear all options
                    $('#deskDropdown option').each(function() {
                        $(this).prop('disabled', true);
                    });
                    $('#deskDropdown').val('None.').trigger('change');
                } else {
                    document.getElementById('incrementCheckbox').classList.remove('hidden');
                    document.getElementById('incrementCheckboxLabel').classList.remove('hidden');
                    document.getElementById('editDeskNameCheckbox').classList.remove('hidden');
                    const checkbox = document.getElementById('editDeskNameCheckbox');
                    checkbox.checked = false;
                    const event2 = new Event('change', {
                        bubbles: true
                    });
                    checkbox.dispatchEvent(event2);
                    document.getElementById('editDeskNameCheckboxLabel').classList.remove('hidden');
                    document.getElementById('updateDeskName').classList.remove('hidden');
                    $('#deskDropdown').select2({
                        placeholder: "-- Type a name --",
                        allowClear: false,
                        width: '100%',
                        height: '100%',
                        tags: false, // disallows user from submitting their own text
                        dropdownParent: $('#dynamicForm')
                    });
                    $('#deskDropdown option').each(function() {
                        $(this).prop('disabled', false);
                    });

                    // Remove all options that are not in dockOptions (newly typed options by the user that have not been submit)
                    $('#deskDropdown option').each(function() {
                        const optionText = $(this).text().trim();
                        if (!dockOptions.includes(optionText) && optionText !==
                            '-- Type a name --') {
                            $(this).remove();
                        }
                    });

                    $('#deskDropdown').val('None.').trigger('change');
                }
            });

            // Checkbox for removing a dock from a desk, or assigning a new desk to no dock.
            document.getElementById('dockRemovalCheckbox').addEventListener('change', function() {
                if (this.checked) {
                    // If checked, hide all relevant fields
                    document.getElementById('serial_num').value = 'None.';
                    const dockdeskDropdown = $('#dockdeskDropdown');
                    dockdeskDropdown.val('None.').trigger('change');
                } else {
                    // If unchecked, show all relevant fields
                    let selectedVal = $('#deskDropdown').val() || '';
                    let serialAndType = selectedVal.split(',');

                    // Extract serial_num and type with fallback defaults
                    let serial_num = serialAndType[0]?.trim() || 'None.';
                    let type = serialAndType.length > 1 ? serialAndType[1]?.trim() : '';

                    // We actually have the serial num and type
                    if (serialAndType.length === 3) {
                        document.getElementById('serial_num').value = serial_num;
                        const dockdeskDropdown = $('#dockdeskDropdown');
                        dockdeskDropdown.val(type).trigger('change');
                    } else {
                        // This is a new dock, we do not have the serial num or type
                        document.getElementById('serial_num').value = 'None.';
                        const dockdeskDropdown = $('#dockdeskDropdown');
                        dockdeskDropdown.val('None.').trigger('change');
                    }
                }
            });

            // Checkbox for editing the name of the currently selected desk
            document.getElementById('editDeskNameCheckbox').addEventListener('change', function() {
                if (this.checked) {
                    // If checked, show the new desk name text field
                    document.getElementById('newDeskLabel').classList.remove('hidden');
                    document.getElementById('newDeskName').classList.remove('hidden');
                    document.getElementById('updateDeskName').classList.remove('hidden');
                } else {
                    // If unchecked, hide the new desk name text field
                    document.getElementById('newDeskLabel').classList.add('hidden');
                    document.getElementById('newDeskName').classList.add('hidden');
                    document.getElementById('updateDeskName').classList.add('hidden');
                }
            });

            // Listener for changing the desk
            $('#deskDropdown').on('change', function() {
                // If we are not currently selecting a point by clicking it and we are selecting it normally, leave viewAllMode
                if (!viewAllMode) {
                    showAllEmployees = true;
                    document.getElementById('viewAllDesksBtn').click();
                }
                
                // if we have an empty desk value or selected no desk
                if ($(this).val() === '' || $(this).val() === null) {
                    const pt = document.getElementById('point');
                    if (pt) {
                        pt.remove();
                    }
                    document.getElementById('editDeskNameCheckbox').classList.add('hidden');
                    document.getElementById('editDeskNameCheckboxLabel').classList.add('hidden');
                    document.getElementById('updateDeskName').classList.add('hidden');
                    document.getElementById('serialNumLabel').classList.add('hidden');
                    document.getElementById('serial_num').classList.add('hidden');
                    document.getElementById('dockTypeLabel').classList.add('hidden');
                    document.getElementById('newDeskLabel').classList.add('hidden');
                    document.getElementById('newDeskName').classList.add('hidden');
                    document.getElementById('dockRemovalCheckbox').classList.add('hidden');
                    document.getElementById('dockRemovalCheckboxLabel').classList.add('hidden');
                    $('#dockdeskDropdown').next('.select2-container').hide();
                    document.getElementById('submitBtn').classList.add('hidden');
                    return;
                }
                // Show 'Auto set to None.' checkbox
                document.getElementById('dockRemovalCheckbox').classList.remove('hidden');
                document.getElementById('dockRemovalCheckboxLabel').classList.remove('hidden');
                document.getElementById('editDeskNameCheckbox').classList.remove('hidden');
                document.getElementById('editDeskNameCheckboxLabel').classList.remove('hidden');
                // Show and autofill the serial number and dock type fields
                let serialAndType = $(this).val().split(',');
                let serial_num = serialAndType[0]?.trim() || 'None.';
                // If we don't know the type (we are adding a new dock), show the options and add it to a select2 and allow typing
                let type = serialAndType.length > 1 ? serialAndType[1]?.trim() : 'None.';
                let id = serialAndType.length > 1 ? serialAndType[2]?.trim() : '';
                console.log(serialAndType);

                // If we have a value and the length = 3, then we are editing an existing desk. Populate the fields from the existing desk info.
                // Case 1: Set a single type
                let newDesk = false;
                if (serialAndType.length === 3) {
                    console.log('Changing serial num');
                    document.getElementById('serial_num').value = serial_num;
                    document.getElementById('serial_num').dispatchEvent(new Event('change', {
                        bubbles: true
                    }));
                } else {
                    // Case 2: If we have one value, we are adding a new desk, so populate all dock types
                    document.getElementById('serial_num').value = 'None.';
                    $('#dockdeskDropdown').val('None.').trigger('change');
                    newDesk = true;
                }

                let employeeInputDiv = document.getElementById('employeeInput');
                document.getElementById('serialNumLabel').classList.remove('hidden');
                document.getElementById('serial_num').classList.remove('hidden');
                document.getElementById('dockTypeLabel').classList.remove('hidden');
                $('#dockdeskDropdown').next('.select2-container').show();
                document.getElementById('submitBtn').classList.remove('hidden');

                // If we are editing a new desk, we cannot get their info in the database so dont even try it
                if (!newDesk) {
                    fetch(`/api/getDeskInfo/${id}`).then(res => res.json()).then(desks => {
                        hideLoading();
                        let x = desks.x;
                        let y = desks.y;
                        console.log(serial_num);

                        // If new dock, make notification to whoever searched them
                        if (x === null || y === null) {
                            const pt = document.getElementById('point');
                            // Dont remove the point if we are in viewAllMode
                            if (pt && !viewAllMode) {
                                pt.remove();
                            }
                            window.showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">Dock is not registered to a position yet.</p>`,
                                'red',
                                6000
                            );
                        } else {
                            // We have their desk number. Show the position and say what desk they are at
                            // Dont show the new position if we are in viewAllMode
                            if (!viewAllMode) {
                                showEmployeePosition(x, y);
                            }
                            // if user status is away, pop up message that says the user hasnt been on their laptop since (timestamp value)
                            // so desk may not be accurate
                        }
                        // Turn off viewAllMode. It is only turned on in the point click listener.
                        viewAllMode = false;
                    });
                    showLoading();
                }
            });

            // Listener for the updateDeskName button
            document.getElementById('updateDeskName').addEventListener('click', function() {
                // Preserve old name, serial number, and type values
                let oldDeskName = $('#deskDropdown option:selected');
                let oldVal = $('#deskDropdown').val();
                let newDeskName = document.getElementById('newDeskName').value;
                let dockID = $('#deskDropdown').val().split(',');
                let id = dockID[2]?.trim();

                // Ensure we aren't trying to edit the desk name to a desk that already exists
                let exists = false;
                $('#deskDropdown option').each(function() {
                    if ($(this).text().trim().toLowerCase() === newDeskName.toLowerCase()) {
                        exists = true;
                        return false; // break the each loop
                    }
                });

                if (exists) {
                    window.showAlertWithTimeout(
                        `<p class="mb-2 font-bold text-center">Desk name already exists</p>`,
                        'red',
                        6000
                    );
                    return; // exit
                }

                showLoading();

                fetch(`/api/updateDeskName`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            newDeskName,
                            id
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        hideLoading();
                        if (data.status === 'success') {
                            window.showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">${data.message}</p>`,
                                'green',
                                6000
                            );
                            // Update the current option to the new desk name
                            oldDeskName.text(newDeskName);
                            $('#deskDropdown').val(oldVal);
                            $('#deskDropdown').trigger('change');
                            $('#deskDropdown').select2('destroy').select2({
                                placeholder: "-- Type a name --",
                                allowClear: false,
                                width: '100%',
                                height: '100%',
                                tags: false,
                                dropdownParent: $('#dynamicForm')
                            });
                        } else {
                            window.showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">Desk name not updated: ${data.message}</p>`,
                                'red',
                                6000
                            );
                        }
                    })
                    .catch(error => {
                        hideLoading();
                        window.showAlertWithTimeout(
                            `<p class="mb-2 font-bold text-center">Error updating desk name: ${error}</p>`,
                            'red',
                            6000
                        );
                    });
            });

            // Listener for actual dock update submit button
            document.getElementById('submitBtn').addEventListener('click', function() {
                document.getElementById('incrementCheckbox').checked =
                    false; // Uncheck the increment checkbox
                document.getElementById('addNewDeskCheckbox').checked =
                    false; // Uncheck the add new desk checkbox
                document.getElementById('dockRemovalCheckbox').checked =
                    false; // Uncheck the dock removal checkbox
                let serial_num = document.getElementById('serial_num').value;
                let type = document.getElementById('dockdeskDropdown').value;
                let desk = $('#deskDropdown option:selected').text().trim();
                if (serial_num && type && desk && currentXDotPos && currentYDotPos) {
                    console.log(
                        `Submitting dock info: Serial: ${serial_num}, Type: ${type}, Desk: ${desk}, X: ${currentXDotPos}, Y: ${currentYDotPos}`
                    );
                    // Example fetch to submit the new dock position
                    fetch('/api/updateDockInfo', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify({
                                serial_num,
                                type,
                                desk,
                                x: currentXDotPos,
                                y: currentYDotPos
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.status === 'success') {
                                // Flash success to session, then redirect
                                return fetch('/api/set-flash', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content')
                                    },
                                    body: JSON.stringify({
                                        flash: {
                                            success: data.message
                                        }
                                    })
                                }).then(() => {
                                    window.location.href = "/adminPanel"
                                });
                            } else {
                                window.showAlertWithTimeout(
                                    `<p class="mb-2 font-bold text-center">Desk name not updated: ${data.message}</p>`,
                                    'red',
                                    6000
                                );
                            }
                        })
                        .catch(error => {
                            hideLoading();
                            showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">Desk name not updated: ${error}</p>`,
                                'red', 6000);
                        });

                    showLoading();
                } else {
                    window.showAlertWithTimeout(
                        `<p class="mb-2 font-bold text-center">Please fill in all fields before submitting.</p>`,
                        'red', 6000);
                }
            });

            // Opens the user browser in fullscreen mode
            function openFullscreen() {
                const elem = document.documentElement; // or any specific element
                if (elem.requestFullscreen) {
                    elem.requestFullscreen();
                } else if (elem.webkitRequestFullscreen) {
                    /* Safari */
                    elem.webkitRequestFullscreen();
                } else if (elem.msRequestFullscreen) {
                    /* IE11 */
                    elem.msRequestFullscreen();
                }
            }
            document.getElementById('fullscreenBtn').addEventListener('click', openFullscreen);
        });
    </script>
    
    <section class="h-screen flex items-center justify-center">
        <div id="mainDiv" class="absolute top-0 left-0 w-full h-full">
            <!-- Loading indicator will be inserted here -->
        </div>
        <form id="dynamicForm">
            @csrf
            <!-- Add a select2 dropdown to choose an employee name -->
            <div id="map-container" class="relative flex justify-right items-right">
                <div id='map-div' class="relative w-7/10 max-w-none h-5/10">
                    <img src="" alt="Office Map"
                        class="block ring-4 ring-orange-400 ring-opacity-75 rounded-lg shadow-2xl ml-5" id='map'>
                    <!-- Dots (added dynamically) will go here -->
                </div>
                <div class="ml-10 mt-10 mb-6">
                    <div id="employeeInput">
                        <p class="text-gray-500 text:lg font-black"><span class='text-black'>Adding a new desk:<br></span>
                            Follow the naming convention starting
                            with
                            <span class="text-red-500">001.</span><br><span class='text-black'>Adding a private room
                                desk:<br></span> Follow the naming
                            convention starting with
                            <span class="text-red-500">P-001.</span><br><span class='text-black'>Adding a mac
                                desk:<br></span> Follow the naming convention
                            starting with <span class="text-red-500">M-001.<br>If the interface is hard to see/use, try
                                zooming out.</span>
                        </p>
                        <div class="w-16 h-1 bg-blue-400 rounded-full mt-4"></div>
                        <label for="deskDropdown" class="block mt-5 text-gray-700 text-lg font-bold">Desk:</label>
                        <select id="deskDropdown" name="type"
                            class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-3/10 shadow-2xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]">
                            <option value=''>-- Type a name --</option>
                            @foreach ($docks as $dock)
                                <option value="{{ $dock->serial_num ?? 'None.' }},{{ $dock->type }},{{ $dock->id }}">
                                    {{ ucfirst($dock->desk) }}
                                </option>
                            @endforeach
                            <!-- Add a link for "Manually add myself" for those with macs where it will ask for desk number and name -->
                        </select>
                        <label id='addNewDeskCheckboxLabel' class="mt-2 text-gray-700 block text-lg font-bold mb-2">
                            <input type="checkbox" id="addNewDeskCheckbox" class="mr-2"> Add new desk
                        </label>
                        <label id='incrementCheckboxLabel' class="mt-2 text-gray-700 block text-lg font-bold mb-2">
                            <input type="checkbox" id="incrementCheckbox" class="mr-2"> Increment from last desk
                        </label>
                        <label id='dockRemovalCheckboxLabel' class="mt-2 text-gray-700 block hidden text-lg font-bold mb-2">
                            <input type="checkbox" id="dockRemovalCheckbox" class="hidden mr-2"> No dock is assigned to this
                            desk
                        </label>
                        <label id='editDeskNameCheckboxLabel'
                            class="mt-2 text-gray-700 block hidden text-lg font-bold mb-2">
                            <input type="checkbox" id="editDeskNameCheckbox" class="hidden mr-2"> Edit the desk name
                        </label>
                        <label id='newDeskLabel' class="mt-2 text-gray-700 block text-lg font-bold mb-2 hidden">New Desk
                            Name:</label>
                        <input type="text" id="newDeskName" name="newDeskName" value=""
                            class="bg-[rgb(255,255,255)] block border border-[rgb(90,88,84)] rounded-lg hidden py-2 px-4 w-10/10 shadow-2xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)] mt-2" />
                        <button
                            class="bg-[rgb(255,255,255)] border hidden border-[rgb(90,88,84)] rounded-lg py-2 px-4 mt-5 w-5/10 shadow-xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                            type='button' id='updateDeskName'>Update desk name</button>
                        <!-- Inside #employeeInput -->
                        <label id='serialNumLabel' class="mt-2 text-gray-700 block text-lg font-bold mb-2 hidden">Serial
                            Number:</label>
                        <input type="text" id="serial_num" name="serial_num" value=""
                            class="bg-[rgb(255,255,255)] block border border-[rgb(90,88,84)] rounded-lg hidden py-2 px-4 w-10/10 shadow-2xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)] mt-2" />
                        <label id='dockTypeLabel' class="mt-2 block text-gray-700 hidden text-lg font-bold">Type:</label>
                        <select name="dockdeskDropdown" id="dockdeskDropdown"
                            class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] hidden rounded-lg py-2 px-4 w-3/10 shadow-2xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]">
                            <option value=''>-- Type a dock type --</option>
                        </select>
                        <button
                            class="bg-[rgb(255,255,255)] border hidden border-[rgb(90,88,84)] rounded-lg py-2 px-4 mt-5 w-5/10 shadow-xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                            type='button' id='submitBtn'>Submit updates</button>
                    </div>
                </div>
            </div>
            <div
                class="fixed bottom-0 left-0 w-full bg-white border-t border-[rgb(90,88,84)] py-2 px-4 flex justify-center space-x-4 z-50">
                <button
                    class="bg-white border border-[rgb(90,88,84)] rounded-lg py-1 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    type="button" onclick="window.location.href='{{ route('editEmployees') }}'">
                    Edit Employee Info
                </button>

                <button
                    class="bg-white border border-[rgb(90,88,84)] rounded-lg py-1 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    type="button" onclick="window.location.href='{{ route('addDockType') }}'">
                    Add a New Dock Type
                </button>

                <button
                    class="bg-white border border-[rgb(90,88,84)] rounded-lg py-1 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    type="button" onclick="window.location.href='{{ route('replaceMapImage') }}'">
                    Change Map Image
                </button>

                <button
                    class="bg-white border border-[rgb(90,88,84)] rounded-lg py-1 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    type="button" onclick="window.location.href='{{ route('home') }}'">
                    Back to User View
                </button>

                <button
                    class="bg-white border border-[rgb(90,88,84)] rounded-lg py-1 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    type="button" id="viewAllDesksBtn">
                    View All Desks (click again to reset)
                </button>

                <button
                    class="bg-white border border-[rgb(90,88,84)] rounded-lg py-1 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    type="button" id="fullscreenBtn">
                    Click here to reset view (esc to exit)
                </button>
            </div>
        </form>
    </section>

    <section name='alerts'>
        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    showAlertWithTimeout(`<p class="mb-2 font-bold text-center">{{ e(session('success')) }}</p>`, 'green',
                        6000);
                });
            </script>
        @endif

        @if (isset($errors))
            @if ($errors->any())
                @foreach ($errors->all() as $error)
                    <script>
                        document.addEventListener('DOMContentLoaded', () => {
                            showAlertWithTimeout(`<p class="mb-2 font-bold text-center">{{ $error }}.</p>`, 'red', 6000);
                        });
                    </script>
                @endforeach
            @endif
        @endif
        @if (session('error'))
            @php
                $sessionErrors = session('error');
                $sessionErrors = is_array($sessionErrors) ? $sessionErrors : [$sessionErrors];
            @endphp
            @foreach ($sessionErrors as $error)
                <!-- Print out error message -->
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showAlertWithTimeout(`<p class="mb-2 font-bold text-center">{{ e($error) }}</p>`, 'red', 6000);
                    });
                </script>
            @endforeach
        @endif
    </section>
@endsection
