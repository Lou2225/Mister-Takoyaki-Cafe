<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DirectionsController extends Controller
{
    /**
     * GET /api/directions?origin=lat,lng&destination=lat,lng
     * Proxies Google Directions API using a server-side key so the key
     * never ships inside the mobile app binary.
     */
    public function route(Request $request)
    {
        $request->validate([
            'origin' => 'required|regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/',
            'destination' => 'required|regex:/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/',
        ]);

        $key = config('services.google.directions_key');
        if (!$key) {
            return response()->json([
                'success' => false,
                'message' => 'Directions API key not configured on server.',
            ], 500);
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/directions/json', [
            'origin' => $request->origin,
            'destination' => $request->destination,
            'mode' => 'driving',
            'key' => $key,
        ]);

        $data = $response->json();

        if (($data['status'] ?? null) !== 'OK' || empty($data['routes'])) {
            return response()->json([
                'success' => false,
                'message' => $data['status'] ?? 'No route found',
            ], 422);
        }

        // Return only what the client needs — the encoded overview polyline.
        return response()->json([
            'success' => true,
            'polyline' => $data['routes'][0]['overview_polyline']['points'],
            'distance_meters' => $data['routes'][0]['legs'][0]['distance']['value'] ?? null,
            'duration_seconds' => $data['routes'][0]['legs'][0]['duration']['value'] ?? null,
        ]);
    }
}