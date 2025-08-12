<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;

// Utilized for Admin LogIn
class AuthController extends Controller
{

    public function login(Request $request)
    {
        session()->flush();
        session()->regenerate();
        Log::info("Logging in...");
        $email = $request->input('email');

        $user = User::where('username', $email)->first();

        if ($user) {
            if (is_null($user->password)) {
                return redirect()->back()->with('error', 'No password set.');
            }
            if (Hash::check($request->input('password'), $user->password)) {
                session([
                    'role' => $user->role,
                ]);
                if (!session()->has('role')) {
                    Log::info('Session does not have role');
                } else {
                    Log::info('Session set.');
                }
            } else {
                return redirect()->back()->with('error', 'Invalid credentials.');
            }

            Log::info('Logged in as admin');
            return redirect('/adminPanel')->with('success', 'Logged in as admin');
        } else {
            Log::info('User not found: ' . $email);
            return redirect()->back()->with('error', 'User not found.');
        }
    }

    public function logout()
    {
        Log::info('Logged out as ' . session('role'));
        session()->flush();
        session()->regenerate();
        return redirect('/')->with('success', 'Logged out successfully.');
    }
}
