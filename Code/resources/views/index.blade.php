@extends('layouts.default')
@section('content')
    <style>
        #mainDiv {
            pointer-events: none;
        }

        .select2-container--default .select2-selection--single {
            background-color: rgb(255, 255, 255);
            border: 1px solid rgb(90, 88, 84);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            height: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
            outline: none;
            margin-top: 2rem;
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
            margin-top: 1rem;
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
        let showAllEmployees = false;
        let showPrivateRooms = false;
        let showMacs = false;

        function showEmployeePosition(x, y, userStatus) {
            const pt = document.getElementById('point');
            if (pt) {
                pt.remove();
            }
            let map = document.getElementById('map-div');
            let point = document.createElement('div');
            if (userStatus === 'Away') {
                point.className =
                    "point absolute bg-green-600 rounded-full opacity-80 -translate-x-1/2 -translate-y-1/2 z-10";
            } else {
                point.className =
                    "point absolute bg-red-600 rounded-full opacity-80 -translate-x-1/2 -translate-y-1/2 z-10";
            }
            point.style.position = "absolute";
            point.style.position = "inline-block";

            // x and y is set as a percent, not a set value
            point.style.left = `${x}%`;
            point.style.top = `${y}%`;
            point.id = 'point';
            point.name = 'point';

            map.appendChild(point);
        }

        function showMultipleEmployeePositions(x, y, fullName, userStatus) {
            let map = document.getElementById('map-div');
            let point = document.createElement('div');
            if (userStatus === 'Away') {
                point.className =
                    "point absolute bg-green-600 rounded-full opacity-80 -translate-x-1/2 -translate-y-1/2 z-10 cursor-pointer";
            } else {
                point.className =
                    "point absolute bg-red-600 rounded-full opacity-80 -translate-x-1/2 -translate-y-1/2 z-10 cursor-pointer";
            }
            point.style.position = "absolute";

            // x and y is set as a percent, not a set value
            point.style.left = `${x}%`;
            point.style.top = `${y}%`;
            point.id = 'point';
            point.name = 'point';

            // Create a tooltip div for the fullName when hovering over the point
            let tooltip = document.createElement('div');
            tooltip.className =
                "tooltip absolute bg-black text-white text-xs rounded px-1 py-0.5 opacity-0 pointer-events-none transition-opacity";
            tooltip.innerText = fullName;
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

        function deleteAllPoints() {
            const points = document.querySelectorAll('.point');
            points.forEach(pt => {
                pt.remove();
            });
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

        document.addEventListener("DOMContentLoaded", () => {
            // Grab the map image and set the map img source to the image
            fetch('/api/getMapImage').then(res => res.json()).then(data => {
                    hideLoading();
                    document.getElementById('mapImage').src = data.image_path;
                })
                .catch(err => {
                    hideLoading();
                    console.error('Error loading map image:', err);
                });
            showLoading();

            let employeeInputDiv = document.getElementById('employeeInput');
            // initialize employee selector select2 dropdown that allows typing
            $(document).ready(function() {
                $('#employeeDropdown').select2({
                    placeholder: "-- Type a name --",
                    allowClear: false,
                    width: '50%',
                    height: '100%',
                    tags: false, // disallows user from submitting their own text
                    dropdownParent: $('#dynamicForm')
                });
            });

            // When changing the name in the employee dropdown, show the employee location in the map at that employee name
            $('#employeeDropdown').on('change', function() {
                let userID = $(this).val();
                // If we are selecting a not null employee name
                if (userID !== '') {
                    fetch(`/api/getEmployeeDesk/${userID}`).then(res => res.json()).then(desks => {
                        hideLoading();
                        let desk = desks.desk;
                        let x = desks.x;
                        let y = desks.y;
                        let userStatus = desks.userStatus;

                        if (userStatus === 'Away') {
                            showEmployeePosition(x, y, userStatus);
                            window.showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">Employee is away (laptop is turned off). Their location may not be accurate.</p>`,
                                'red',
                                6000
                            );
                        }

                        // If new dock, make notification to whoever searched them
                        if (desk === 'New dock - update to include desk when possible.') {
                            window.showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">Employees dock is not registered yet.</p>`,
                                'red',
                                6000
                            );
                        } else if (desk === 'None.') {
                            // If the employee has no current desk,
                            // let the user know
                            window.showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">Employee currently not at a desk.</p>`,
                                'red',
                                6000
                            );
                        } else if (desk === 'Null') {
                            // Employee not found
                            window.showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">Employee not found.</p>`,
                                'red',
                                6000
                            );
                        } else {
                            // We have their desk number. Show the position and say what desk they are at
                            showEmployeePosition(x, y, userStatus);
                            // if user status is away, pop up message that says the user hasnt been on their laptop since (timestamp value)
                            // so desk may not be accurate
                        }
                    });
                    showLoading();
                }
            });

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

            // Shows all employee locations with hover functionality
            document.getElementById('viewAllDesksBtn').addEventListener('click', function() {
                if (showAllEmployees === false) {
                    // Remove current point
                    const pt = document.getElementById('point');
                    if (pt) {
                        pt.remove();
                    }
                    // Reset type dropdown
                    const dropdown = document.getElementById('employeeDropdown');
                    dropdown.value = '';
                    const event = new Event('change', {
                        bubbles: true
                    });
                    dropdown.dispatchEvent(event);

                    // 'Switch' to provide functionality for the reset part in the else statement
                    showAllEmployees = true;
                    fetch(`/api/showAllActive`)
                        .then(res => res.json())
                        .then(desks => {
                            hideLoading();
                            desks.forEach(desk => {
                                showMultipleEmployeePositions(desk.x, desk.y, desk.fullName,
                                    desk.status);
                            });
                        });
                    showLoading();
                } else {
                    showAllEmployees = false;
                    deleteAllPoints();
                }
            });

            // Shows all mac desk locations
            document.getElementById('macBtn').addEventListener('click', function() {
                if (showMacs === false) {
                    // Remove current point
                    const pt = document.getElementById('point');
                    if (pt) {
                        pt.remove();
                    }
                    // Reset type dropdown
                    const dropdown = document.getElementById('employeeDropdown');
                    dropdown.value = '';
                    const event = new Event('change', {
                        bubbles: true
                    });
                    dropdown.dispatchEvent(event);

                    // 'Switch' to provide functionality for the reset part in the else statement
                    showMacs = true;
                    fetch(`/api/showAllMacs`)
                        .then(res => res.json())
                        .then(desks => {
                            hideLoading();
                            desks.forEach(desk => {
                                showMultipleEmployeePositions(desk.x, desk.y, 'Mac Desk', 'Active');
                            });
                        });
                    showLoading();
                } else {
                    showMacs = false;
                    deleteAllPoints();
                }
            });

            // Shows all private room locations
            document.getElementById('privateRoomBtn').addEventListener('click', function() {
                if (showPrivateRooms === false) {
                    // Remove current point
                    const pt = document.getElementById('point');
                    if (pt) {
                        pt.remove();
                    }
                    // Reset type dropdown
                    const dropdown = document.getElementById('employeeDropdown');
                    dropdown.value = '';
                    const event = new Event('change', {
                        bubbles: true
                    });
                    dropdown.dispatchEvent(event);

                    // 'Switch' to provide functionality for the reset part in the else statement
                    showPrivateRooms = true;
                    fetch(`/api/showAllPrivate`)
                        .then(res => res.json())
                        .then(desks => {
                            hideLoading();
                            desks.forEach(desk => {
                                showMultipleEmployeePositions(desk.x, desk.y, desk.fullName,
                                    desk.status);
                            });
                        });
                    showLoading();
                } else {
                    showPrivateRooms = false;
                    deleteAllPoints();
                }
            });
        });
    </script>
    <section class="h-screen flex items-center justify-center">
        <div id="mainDiv" class="absolute top-0 left-0 w-full h-full">
            <!-- Loading indicator will be inserted here -->
        </div>
        <form method="POST" id="dynamicForm" action="#">
            @csrf
            <!-- Add a select2 dropdown to choose an employee name -->
            <div id="map-container" class="relative flex justify-right items-right">
                <div id='map-div' class="relative w-7/10 max-w-none h-5/10">
                    <img src="" alt="Office Map" id='mapImage'
                        class="block ring-4 ring-orange-400 ring-opacity-75 rounded-lg shadow-2xl ml-5" id='map'>
                    <!-- Dots (added dynamically) will go here -->
                </div>
                <div id="employeeInput" class="ml-10 mt-60 mb-6">
                    <h1 class="text-gray-700 text-2xl uppercase font-black">Type employee name here:</h1>
                    <p class="text-gray-500 text:lg font-black">If the user's dot is green, that means their computer is off
                        and has not updated their current position recently.</p>
                    <div class="w-16 h-1 bg-blue-400 rounded-full mt-4"></div>
                    <select id="employeeDropdown" name="type"
                        class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-2/10 shadow-2xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]">
                        <option value="">-- Type a name --</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->userID }}">
                                {{ ucfirst($employee->fullName) }}</option>
                        @endforeach
                        <!-- Add a link for "Manually add myself" for those with macs where it will ask for desk number and name -->
                    </select>
                    <button
                        class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 mt-5 w-5/10 shadow-xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                        type='button' id='viewAllDesksBtn'>View all occupied desks (hover over dot to see who is occupying
                        the
                        desk, click again to reset)</button>
                    <button
                        class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 mt-5 w-5/10 shadow-xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                        type='button' id='privateRoomBtn'>View all private rooms (click again to reset, red dot means it is
                        in use)</button>
                    <button
                        class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 mt-5 w-5/10 shadow-xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                        type='button' id='macBtn'>View all mac desks (click again to reset)</button>
                    <button
                        class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 mt-5 w-5/10 shadow-xl focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                        type='button' id='fullscreenBtn'>Click here to reset view (esc to exit)</button>
                </div>
            </div>
            <button
                class="bg-[rgb(255,255,255)] absolute right-5 bottom-5 border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-1/15 focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                type='button' id='adminBtn' onclick="window.location.href='{{ route('logIn') }}'">Admin</button>
        </form>
    </section>
@endsection
