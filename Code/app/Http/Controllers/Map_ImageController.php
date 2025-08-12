<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Map_Image;

// Handles map image data and handling
class Map_ImageController extends Controller
{
    // Grabs the current map image in the database
    public function getMapImage()
    {
        try {
            $image = Map_Image::select('image_path', 'image_type')->first();
            $base64Image = base64_encode($image->image_path);
            $mimeType = $image->image_type ?? 'application/octet-stream';

            $imageDataUri = "data:$mimeType;base64,$base64Image";

            Log::info('Map Image grabbed successfully');
            return response()->json([
                'image_path' => $imageDataUri
            ]);
        } catch (\Exception $e) {
            Log::error('Error grabbing map image: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error grabbing map image: ' . $e->getMessage(),
            ], 500);
        }
    }

    // Replaces the current map image in the database
    public function replaceMapImage(Request $request)
    {
        $file = $request->file('image');
        $image = file_get_contents($request->file('image')->getRealPath());
        $imageType = $file->getMimeType();

        try {
            // Delete all (its just a single row with the image path)
            Map_Image::truncate();

            // Create the new row with the new image path
            Map_Image::create([
                'image_path' => $image,
                'image_type' => $imageType
            ]);
            Log::info('Map Image updated successfully');
            return response()->json([
                'status' => 'success',
                'message' => 'Map Image updated successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating map image: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error updating map image: ' . $e->getMessage(),
            ], 500);
        }
    }
}