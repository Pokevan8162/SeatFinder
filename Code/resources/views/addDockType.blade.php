<!-- User can either choose to be a guest or go to the admin login page. -->
<!-- To Do: Add log in form and fit the other buttons inside of the form so it looks good -->

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
    </style>

    <script>
        // function to show the loading symbol
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

        // function to hide the loading symbol
        function hideLoading() {
            const loading = document.getElementById('loading');
            if (loading) loading.remove();
        }

        // function to show an alert
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
            // on submit, add the dock type and name to the database
            document.getElementById('dynamicForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Stop normal form submission

                dockType = document.getElementById('dockType').value;
                dockName = document.getElementById('dockName').value;

                fetch('/api/addDockType', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector(
                                'meta[name="csrf-token"]').getAttribute(
                                'content')
                        },
                        body: JSON.stringify({
                            dockType,
                            dockName
                        })
                    }).then(response => response.json()).then(data => {
                        if (data.status === 'success') {
                            window.showAlertWithTimeout(
                                `<p class="mb-2 font-bold text-center">Desk name updated: ${data.message}</p>`,
                                'green',
                                6000
                            );
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
            });
        });
    </script>

    <!-- main HTMl code -->
    <section class="h-screen flex items-center justify-center">
        <div id="mainDiv" class="absolute top-0 left-0 w-full h-full">
            <!-- Loading indicator will be inserted here -->
        </div>
        <div class="container mx-auto max-w-xl bg-[rgba(33,33,33)] p-8 rounded-xl shadow-lg">
            <h1 class="text-white text-[4rem] mb-6 uppercase font-black text-center">Add Dock <span
                    class="text-[rgb(0,168,213)]">Type</span></h1>
            <br>
            <form id='dynamicForm' action="{{ route('updateEmployeeInfo') }}" method="POST" class="mb-6">
                @csrf
                <label for="dockType"
                    class="text-[rgb(255,255,255)] text-[1.2rem] font-black [text-shadow:_0_0_2px_black]">Dock Type (flip
                    the dock over, it should say Type: and have 4 characters in a box):</label>
                <br>
                <br>
                <input required type="text" id="dockType"
                    class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-100 shadow-md focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    name="dockType" placeholder="Enter new Dock Type:"required />
                <br>
                <label for="dockName"
                    class="text-[rgb(255,255,255)] text-[1.2rem] font-black [text-shadow:_0_0_2px_black]">Dock Name (flip
                    the dock over, right below the Lenovo label, it should say the name of the dock, ex. Thunderbolt 4,
                    Thunderbolt 3 Gen 2, etc):</label>
                <br>
                <br>
                <input required type="text" id="dockName"
                    class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-100 shadow-md focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    name="dockName" placeholder="Enter Name of New Dock:"required />
                <br>
                <button type="submit" name="addType"
                    class="w-[200px] bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] text-[rgb(33,33,33)] text-[1rem] font-black rounded-lg p-3 shadow-md focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]">
                    Add Dock
                </button>
                <button
                    class="bg-[rgb(255,255,255)] absolute right-55 bottom-5 border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-1/15 focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    type='button' id='adminBtn' onclick="window.location.href='{{ route('adminPanel') }}'">Back to Admin
                    View</button>
                <button
                    class="bg-[rgb(255,255,255)] absolute right-5 bottom-5 border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-1/15 focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    type='button' id='adminBtn' onclick="window.location.href='{{ route('home') }}'">Back to User
                    View</button>
            </form>
            <br>
        </div>
    </section>

    <!-- alerts section for showAlertWithTimeout function -->
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
