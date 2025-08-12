<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Dock;
use App\Models\Dock_Type;
use App\Models\Status;

// Handles employee data and handling
class EmployeeController extends Controller
{

    // Grabs all employee information
    public function getAllEmployees($viewPath)
    {
        $employees = DB::table('employees')
            ->select('userID', 'fullName')
            ->orderBy('fullName')
            ->get();
        Log::info('Retrieved all employees.');
        return view($viewPath, compact('employees'));
    }

    // Grabs user status at specific userID
    public function checkEmployeeStatus(Request $request)
    {
        $userID = $request->input('userID');

        $userStatus = DB::table('statuses')->select('status')->where('userID', $userID)->get();

        return $userStatus;
    }

    // Returns in JSON all in use dock names and types
    public function getDockInfo()
    {
        try {
            $docks = DB::table('dock_types')
                ->select('type', 'name')
                ->where('in_use', true)
                ->get();
        } catch (\Exception $e) {
            Log::error('Error grabbing docks: ' . $e->getMessage());
            $docks = collect(); // return empty array to prevent crashes
            Log::info('Retrieved all in use docks.');
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating desk name: ' . $e->getMessage(),
            ], 500);
        }
        return response()->json($docks);
    }

    // Set a user to be online (also update their updated_at for parsing in the status checker)
    public function setOnline(Request $request)
    {
        try {
            $userID = $request->input('userID');
            $status = Status::where('userID', $userID)->first();
            if (!$status) {
                Log::info('User ' . $userID . ' does not exist yet.');
                return;
            }
            $status->status = 'Online'; // Refresh status to be online
            $status->save();
            $status->touch(); // forces updated at to refresh
            return;
        } catch (\Exception $e) {
            Log::error('Error setting status: ' . $e->getMessage());
            return;
        }
    }

    // Grab every active user and their dot positions
    public function showAllActive()
    {
        $allEmployeesLocation = Employee::whereNotNull('dockID')
            ->join('docks', 'employees.dockID', '=', 'docks.id')
            ->join('statuses', 'employees.userID', '=', 'statuses.userID')
            ->select('docks.x', 'docks.y', 'employees.fullName', 'statuses.status', 'docks.serial_num', 'docks.type', 'docks.desk', 'docks.id')
            ->get();

        return response()->json($allEmployeesLocation);
    }

    // Grab every desk
    public function showAllDesks()
    {
        $allDesks = Dock::select('docks.x', 'docks.y', 'docks.serial_num', 'docks.type', 'docks.desk', 'docks.id')->get();

        return response()->json($allDesks);
    }

    // Grab every private room and their activity
    public function showAllPrivate()
    {
        $allPrivates = Employee::whereNotNull('dockID')
            ->join('docks', 'employees.dockID', '=', 'docks.id')
            ->join('statuses', 'employees.userID', '=', 'statuses.userID')
            ->where('docks.desk', 'like', '%P%')
            ->select('docks.x', 'docks.y', 'employees.fullName', 'statuses.status')
            ->get();

        return response()->json($allPrivates);
    }

    // Change employee userIDs, full names, or to remove them altogether
    public function updateEmployeeInfo(Request $request)
    {
        try {
            $oldUserID = $request->input('oldUserID');
            $newUserID = $request->input('userID');
            $newFullName = $request->input('fullName');
            $removeCheckbox = $request->input('removeEmployeeCheckbox');
            $addEmployeeCheckbox = $request->input('addEmployeeCheckbox');

            // If we are removing the employee
            if ($removeCheckbox) {
                Employee::where('userID', $oldUserID)->delete();
                Status::where('userID', $oldUserID)->delete();
                Log::info('Succesfully removed employee ' . $oldUserID . '.');
                return response()->json([
                    'status' => 'success',
                    'message' => 'Succesfully removed employee ' . $oldUserID . '.',
                ]);
            }

            if ($addEmployeeCheckbox) {
                Employee::create([
                    'userID' => $newUserID,
                    'fullName' => $newFullName,
                    'dockID' => null,
                ]);
                Status::create([
                    'userID' => $newUserID,
                    'status' => 'Away'
                ]);
                Log::info('Successfully added employee ' . $newUserID . '.');
                return response()->json([
                    'status' => 'success',
                    'message' => 'Successfully added employee ' . $newUserID . '.',
                ]);
            }

            // If we are changing the userID
            if ($newUserID) {
                Employee::where('userID', $oldUserID)->update(['userID' => $newUserID]);
                Status::where('userID', $oldUserID)->update(['userID' => $newUserID]);
            }

            // If we are changing the fullName
            if ($newFullName) {
                Employee::where('userID', $oldUserID)->update(['fullName' => $newFullName]);
            }

            Log::info('Succesfully updated employee info.');
            return response()->json([
                'status' => 'success',
                'message' => 'Employee info updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::info('Error with editing employee info: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating employee info: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Grab a desk at a specific userID
    public function getEmployeeDesk($userID)
    {
        try {
            Log::info('Grabbing employee desk...');
            $employee = Employee::where('userID', $userID)->first();

            if (!$employee) {
                return response()->json(['desk' => 'Null', 'x' => null, 'y' => null, 'status' => null]);
            }

            $deskInfo = Dock::where('id', $employee->dockID)
                ->select('desk', 'x', 'y')
                ->first();

            if (!$deskInfo) {
                return response()->json(['desk' => 'None.', 'x' => null, 'y' => null, 'status' => null]);
            }

            $userStatus = DB::table('statuses')
                ->where('userID', $userID)
                ->select('status')
                ->first();
            Log::info('Succesfully grabbed employee desk and status.');
            return response()->json([
                'desk' => $deskInfo->desk,
                'x' => $deskInfo->x,
                'y' => $deskInfo->y,
                'userStatus' => $userStatus->status,
            ]);
        } catch (\Exception $e) {
            Log::info('Error with grabbing employee desk: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating desk name: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Forcibly remove an employee from a desk
    public function removeEmployeeFromDesk(Request $request)
    {
        try {
            $userID = $request->input('userID');
            DB::transaction(function () use ($userID) {
                Employee::where('userID', $userID)
                    ->update(['dockID' => null]);
            });
            DB::transaction(function () use ($userID) {
                Status::where('userID', $userID)
                    ->update(['status' => 'Away']);
            });
            Log::info('Succesfully removed employee from desk.');
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error removing employee from desk: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Create dock and employee info if it does not exist in the database and update it to be accurate otherwise - the bread and butter of the powershell scripts functionality
    public function updateDeskInfo(Request $request)
    {
        $userID = $request->input('userID');
        $serial_num = $request->input('serial_num');
        $type = $request->input('type');
        $fullName = $request->input('fullName');

        // Ensure atomicity to avoid race conditions
        DB::transaction(function () use ($userID, $type, $serial_num, $fullName) {
            // If employee does not exist, create them.
            $employee = Employee::firstOrNew([
                'userID' => $userID,
                'fullName' => $fullName,
            ]);

            $dock = Dock::where('serial_num', $serial_num)->first();
            // If dock does not exist, create one and set desk to a warning
            if (!$dock) {
                Log::info("Dock not found, creating new dock.");
                $dock = Dock::create(
                    ['type' => $type, 'serial_num' => $serial_num, 'desk' => 'New dock - update to include desk when possible.']
                );
                // Assign the desk to this employee
                $employee->dockID = $dock->id;
                $employee->save();

                // Update or set the users status
                $status = Status::firstOrNew(['userID' => $userID]);
                $status->status = 'Online'; // Refresh status to be online
                $status->save();
                $status->touch(); // forces updated at to refresh
            } else {
                // If the desk exists, and if someone else occupies this desk, set their desk to 'None.'
                Log::info("Dock found! Erasing other employees from this desk and assigning the desk to the new employee.");
                $dockID = $dock->id;
                Employee::where('dockID', $dockID)
                    ->where('userID', '!=', $userID)
                    ->update(['dockID' => 'None.']);
                $employee->dockID = $dockID;
                $employee->save();

                // Update or set the users status
                $status = Status::firstOrNew(['userID' => $userID]);
                $status->status = 'Online'; // Refresh status to be online
                $status->save();
                $status->touch(); // forces updated at to refresh
            }
        });

        Log::info('Succesfully updated DB.');
        return response()->json(['status' => 'success']);
    }
}
