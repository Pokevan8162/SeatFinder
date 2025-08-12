<?php

// This is the backend to how we interact with the web page routes (ex. clicking a button leading to 'editEmployees.blade.php', or giving post values to selecting a web page)

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Middleware\EnsureUserIsAuthenticated;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DockController;

Route::get('/', function () {
    $controller = app()->make(\App\Http\Controllers\EmployeeController::class);
    return $controller->getAllEmployees('index');
})->name('home');

Route::get('/editEmployees', function () {
    $controller = app()->make(\App\Http\Controllers\EmployeeController::class);
    return $controller->getAllEmployees('editEmployees');
})->name('editEmployees');

Route::get('/adminPanel', function () {
    if (session('role') !== 'admin') {
        return redirect()->route('home');
    }
    $controller = app()->make(\App\Http\Controllers\DockController::class);
    return $controller->getAllDocks('adminPanel');
})->name('adminPanel');

Route::get('/addDockType', function () {
    if (session('role') !== 'admin') {
        return redirect()->route('home');
    }
    return view('addDockType');
})->name('addDockType');

Route::get('/replaceMapImage', function () {
    if (session('role') !== 'admin') {
        return redirect()->route('home');
    }
    return view('replaceMapImage');
})->name('replaceMapImage');

Route::get('/logIn', function () {
    return view('logIn');
})->name('logIn');

Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware([EnsureUserIsAuthenticated::class])->name('log.in');

Route::post('/api/set-flash', function (\Illuminate\Http\Request $request) {
    foreach ($request->input('flash', []) as $key => $value) {
        session()->flash($key, $value);
    }
    return response()->json(['status' => 'ok']);
});