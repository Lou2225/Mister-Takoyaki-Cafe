<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Save user address and coordinates from Flutter app
     */
    public function saveAddress(Request $request)
    {
        // 1. Validate the incoming data from Flutter
        $request->validate([
            'address' => 'required|string',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
        ]);

        // 2. Get the currently authenticated user
        $user = $request->user();
        
        // 3. Update their information
        $user->update([
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
        ]);

        // 4. Return success response to Flutter
        return response()->json([
            'success' => true,
            'message' => 'Address saved successfully',
            'data' => [
                'address' => $user->address,
                'latitude' => (float)$user->latitude,
                'longitude' => (float)$user->longitude,
            ]
        ], 200);
    }
}
