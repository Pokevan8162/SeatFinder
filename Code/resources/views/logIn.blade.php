<!-- User can either choose to be a guest or go to the admin login page. -->
<!-- To Do: Add log in form and fit the other buttons inside of the form so it looks good -->

@extends('layouts.default')

@section('content')

    <section class="h-screen flex items-center justify-center">
        <div class="container mx-auto max-w-xl bg-[rgba(33,33,33)] p-8 rounded-xl shadow-lg">
            <h1 class="text-white text-8xl mb-6 uppercase font-black text-center">Admin Log <span
                    class="text-[rgb(0,168,213)]">In</span></h1>
            <br>
            @if (isset($errors))
                @if ($errors->any())
                    <ul class="text-[rgb(255,0,0)] font-black text-center">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif
            @endif
            @if (session('error'))
                <ul class="text-[rgb(255,0,0)] font-black text-center">
                    @if (session('error'))
                        @php
                            $sessionErrors = session('error');
                            $sessionErrors = is_array($sessionErrors) ? $sessionErrors : [$sessionErrors];
                        @endphp
                        @foreach ($sessionErrors as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    @endif
                </ul>
            @endif
            <br>
            <form action="{{ route('log.in') }}" method="POST" class="mb-6">
                @csrf
                <label for="employee_email"
                    class="text-[rgb(255,255,255)] text-[1.2rem] font-black [text-shadow:_0_0_2px_black]">Username:</label>
                <input type="text" id="employee_email"
                    class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-100 shadow-md focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    name="email" required />
                <br>
                <label for="password"
                    class="text-[rgb(255,255,255)] text-[1.2rem] font-black [text-shadow:_0_0_2px_black]">Password:</label>
                <input type="password" id="password"
                    class="bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] block rounded-lg py-2 px-4 w-100 shadow-md focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]"
                    name="password" required />
                <br>
                <button type="submit" name="login"
                    class="w-[200px] bg-[rgb(255,255,255)] border border-[rgb(90,88,84)] text-[rgb(33,33,33)] text-[1rem] font-black rounded-lg p-3 shadow-md focus:outline-none focus:ring-2 focus:ring-[rgb(0,168,213)]">
                    Log In
                </button>
            </form>
            <br>
        </div>
        </div>
    </section>

@endsection
