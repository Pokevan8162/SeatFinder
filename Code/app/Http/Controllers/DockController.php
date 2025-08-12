<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Employee;
use App\Models\Dock;
use App\Models\Dock_Type;

// Handles dock data and handling
class DockController extends Controller
{
    // Returns all dock types (ex. 40B0 (TB4), 40AN (TB3 G2))
    public function getAllDockTypes() {
        $dockTypes = Dock_Type::select('type')->where('type', '!=', 'None.')->get();
        Log::info('Retrieved all dock types: ' . $dockTypes->toJson());
        return $dockTypes;
    }

    // Gets all docks and all their info
    public function getAllDocks($viewPath)
    {
        $docks = Dock::select('id', 'serial_num', 'type', 'desk', 'x', 'y')
            ->orderBy('desk')
            ->get();
        Log::info('Retrieved all docks: ' . $docks->toJson());
        return view($viewPath, compact('docks'));
    }

    // Adds a new dock type to the database
    public function addDockType(Request $request)
    {
        $dockType = $request->input('dockType');
        $dockName = $request->input('dockName');

        try {
            Dock_Type::create([
                'type' => $dockType,
                'name' => $dockName,
                'in_use' => 0
            ]);

            Log::info('Dock type successfully added');
            return response()->json([
                'status' => 'success',
                'message' => 'Dock type added successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error adding dock type: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error adding dock type: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Update relevant dock information in the DB (ex. adding a new dock, editing associated desk values, etc)
    public function updateDockInfo(Request $request)
    {
        Log::info('Updating dock info...');

        $serial_num = $request->input('serial_num');
        $type = $request->input('type');
        $desk = $request->input('desk');
        $x = $request->input('x');
        $y = $request->input('y');

        try {
            $dockAtDesk = Dock::where('desk', $desk)->first();
            if ($serial_num !== 'None.') {
                $dockAtSerial = Dock::where('serial_num', $serial_num)->first();
            } else {
                $dockAtSerial = null;
            }
            // Check if theres a dock at this desk already
            if ($dockAtDesk) {
                $dockAtDesk->update([
                    'serial_num' => $serial_num,
                    'type' => $type,
                    'x' => $x,
                    'y' => $y
                ]);
            }
            // Check if theres a dock at this serial number already
            else if ($dockAtSerial !== null) {
                $dockAtSerial->update([
                    'desk' => $desk,
                    'type' => $type,
                    'x' => $x,
                    'y' => $y
                ]);
            }
            // This is for sure a new dock.
            else {
                Dock::create([
                    'serial_num' => $serial_num,
                    'desk' => $desk,
                    'type' => $type,
                    'x' => $x,
                    'y' => $y
                ]);
                Dock_Type::where('type', $type)->increment('in_use');
            }
            Log::info('Dock info updated successfully for serial number: ' . $serial_num);
            return response()->json([
                'status' => 'success',
                'message' => 'Dock info updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating dock info: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating dock info: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Show every mac desk
    public function showAllMacs()
    {
        $allMacs = Dock::where('docks.desk', 'like', '%M%')
            ->select('docks.x', 'docks.y')
            ->get();

        return response()->json($allMacs);
    }

    // Remove dock type from database so it is no longer parsed
    public function removeDock($type)
    {
        try {
        Dock_Type::where('type', $type)->delete();
        return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Error removing dock: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error removing dock: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Update the name of a desk
    public function updateDeskName(Request $request)
    {
        $id = $request->input('id');
        Log::info($id);
        $newDeskName = $request->input('newDeskName');
        try {
            Dock::where('id', $id)->update(['desk' => $newDeskName]);
            Log::info('Desk name updates successfully.');
            return response()->json([
                'status' => 'success',
                'message' => 'Desk name updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating desk name: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating desk name: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Returns in JSON all in use dock names and types
    public function getDockDesk($serial_num)
    {
        Log::info('Grabbing dock desk...');

        try {
            Log::info('Grabbing dock desk for serial number: ' . $serial_num);
            $deskInfo = Dock::where('serial_num', $serial_num)
                ->select('x', 'y')
                ->first();
        } catch (\Exception $e) {
            Log::error('Error grabbing dock desk: ' . $e->getMessage());
            $deskInfo = null; // return null to prevent crashes
        }
        Log::info('Succesfully grabbed dock desk at: ' . $deskInfo);
        return response()->json($deskInfo);
    }

    // Grab desk information at a specific dockID
    public function getDeskInfo($id)
    {
        Log::info('Grabbing dock desk...');

        try {
            Log::info('Grabbing dock desk for serial number: ' . $id);
            $deskInfo = Dock::where('id', $id)
                ->select('x', 'y')
                ->first();
        } catch (\Exception $e) {
            Log::error('Error grabbing dock desk: ' . $e->getMessage());
            $deskInfo = null; // return null to prevent crashes
        }
        Log::info('Succesfully grabbed dock desk at: ' . $deskInfo);
        return response()->json($deskInfo);
    }
}
