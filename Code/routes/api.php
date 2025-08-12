<?php

// This is the backend to how we interact with controller methods inside of the website itself

use Illuminate\Http\Request;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\DockController;
use App\Http\Controllers\Map_ImageController;
use Illuminate\Support\Facades\Route;

Route::get('/getDockInfo', [EmployeeController::class, 'getDockInfo']);
Route::get('/showAllActive', [EmployeeController::class, 'showAllActive']);
Route::get('/showAllPrivate', [EmployeeController::class, 'showAllPrivate']);
Route::get('/showAllMacs', [DockController::class, 'showAllMacs']);
Route::get('/getEmployeeDesk/{userID}', [EmployeeController::class, 'getEmployeeDesk']);
Route::get('/showAllDesks', [EmployeeController::class, 'showAllDesks']);
Route::get('/getAllDockTypes', [DockController::class, 'getAllDockTypes']);
Route::get('/getDockDesk/{serial_num}', [DockController::class, 'getDockDesk']);
Route::get('/getDeskInfo/{desk}', [DockController::class, 'getDeskInfo']);
Route::get('/getMapImage', [Map_ImageController::class, 'getMapImage']);

Route::post('/updateEmployeeInfo', [EmployeeController::class, 'updateEmployeeInfo'])->name('updateEmployeeInfo');
Route::post('/setOnline', [EmployeeController::class, 'setOnline']);
Route::post('/updateDeskInfo', [EmployeeController::class, 'updateDeskInfo']);
Route::post('/removeEmployeeFromDesk', [EmployeeController::class, 'removeEmployeeFromDesk']);
Route::post('/updateDockInfo', [DockController::class, 'updateDockInfo']);
Route::post('/addDockType', [DockController::class, 'addDockType'])->name('addDockType');
Route::post('/updateDeskName', [DockController::class, 'updateDeskName']);
Route::post('/replaceMapImage', [Map_ImageController::class, 'replaceMapImage']);