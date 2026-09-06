<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;

class UserController extends Controller
{
    // ─── GET /api/user/addresses ──────────────────────────────────────────────
    public function getUserAddresses(Request $request)
    {
        $addresses = UserAddress::where('user_id', $request->user()->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $addresses,
        ], 200);
    }

    // ─── POST /api/user/address ───────────────────────────────────────────────
    public function saveAddress(Request $request)
    {
        $request->validate([
            'label'     => 'nullable|string|max:100',
            'house_no'  => 'nullable|string|max:100',
            'street'    => 'nullable|string|max:255',
            'barangay'  => 'nullable|string|max:255',
            'city'      => 'nullable|string|max:255',
            'province'  => 'nullable|string|max:255',
            'note'      => 'nullable|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default'=> 'nullable|boolean',
        ]);

        $user = $request->user();

        // If this address is being set as default, clear all others first
        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', $user->id)
                ->update(['is_default' => false]);
        }

        // If this is the user's first address, auto-set as default
        $isFirst = UserAddress::where('user_id', $user->id)->count() === 0;

        $address = UserAddress::create([
            'user_id'    => $user->id,
            'label'      => $request->input('label', 'Home'),
            'house_no'   => $request->input('house_no', ''),
            'street'     => $request->input('street', ''),
            'barangay'   => $request->input('barangay', ''),
            'city'       => $request->input('city', ''),
            'province'   => $request->input('province', ''),
            'note'       => $request->input('note', ''),
            'latitude'   => $request->input('latitude'),
            'longitude'  => $request->input('longitude'),
            'is_default' => $request->boolean('is_default') || $isFirst,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address saved successfully',
            'data'    => $address,
        ], 201);
    }

    // ─── PUT /api/user/address/{id} ───────────────────────────────────────────
    // Updates an existing address in place. Without this, the Flutter app's
    // "edit address" flow had to fall back to calling saveAddress() again,
    // which creates a brand new row instead of modifying the original —
    // silently duplicating addresses every time a user edited one.
    public function updateAddress(Request $request, int $id)
    {
        $user = $request->user();

        $address = UserAddress::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        $request->validate([
            'label'     => 'nullable|string|max:100',
            'house_no'  => 'nullable|string|max:100',
            'street'    => 'nullable|string|max:255',
            'barangay'  => 'nullable|string|max:255',
            'city'      => 'nullable|string|max:255',
            'province'  => 'nullable|string|max:255',
            'note'      => 'nullable|string',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default'=> 'nullable|boolean',
        ]);

        // If this address is being set as default, clear all other defaults
        // for this user first (mirrors saveAddress()'s behavior).
        if ($request->boolean('is_default')) {
            UserAddress::where('user_id', $user->id)
                ->where('id', '!=', $address->id)
                ->update(['is_default' => false]);
        }

        $address->update([
            'label'      => $request->input('label', $address->label),
            'house_no'   => $request->input('house_no', $address->house_no),
            'street'     => $request->input('street', $address->street),
            'barangay'   => $request->input('barangay', $address->barangay),
            'city'       => $request->input('city', $address->city),
            'province'   => $request->input('province', $address->province),
            'note'       => $request->input('note', $address->note),
            'latitude'   => $request->input('latitude', $address->latitude),
            'longitude'  => $request->input('longitude', $address->longitude),
            'is_default' => $request->has('is_default')
                ? $request->boolean('is_default')
                : $address->is_default,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Address updated successfully',
            'data'    => $address->fresh(),
        ], 200);
    }

    // ─── PATCH /api/user/address/{id}/default ─────────────────────────────────
    // Marks a single address as default and clears the flag on all others
    // for this user. Without this, "set as default" in the app only changed
    // local UI state and never persisted — it would reset on every reload.
    public function setDefaultAddress(Request $request, int $id)
    {
        $user = $request->user();

        $address = UserAddress::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        UserAddress::where('user_id', $user->id)
            ->update(['is_default' => false]);

        $address->update(['is_default' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Default address updated',
            'data'    => $address->fresh(),
        ], 200);
    }

    // ─── DELETE /api/user/address/{id} ────────────────────────────────────────
    public function deleteAddress(Request $request, int $id)
    {
        $user    = $request->user();
        $address = UserAddress::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$address) {
            return response()->json([
                'success' => false,
                'message' => 'Address not found',
            ], 404);
        }

        $wasDefault = $address->is_default;
        $address->delete();

        // If the deleted address was the default, promote the next one
        if ($wasDefault) {
            $next = UserAddress::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();
            if ($next) {
                $next->update(['is_default' => true]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Address deleted successfully',
        ], 200);
    }

    // ─── POST /api/user/profile ───────────────────────────────────────────────
    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name'  => 'nullable|string|max:255',
            'email'      => 'nullable|email',
            'phone'      => 'nullable|string|max:20',
        ]);

        $user = $request->user();

        $user->update([
            'first_name' => $request->input('first_name', $user->first_name),
            'last_name'  => $request->input('last_name', $user->last_name),
            'email'      => $request->input('email', $user->email),
            'phone'      => $request->input('phone', $user->phone),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data'    => ['user' => $user],
        ]);
    }
    
        // ─── DELETE /api/user/delete ──────────────────────────────────────────────
    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // 1. Revoke all Sanctum tokens for this user
        $user->tokens()->delete();

        // 2. Delete user's saved addresses
        \App\Models\UserAddress::where('user_id', $user->id)->delete();

        // 3. Deactivate account to block future logins (matches AuthController::login check)
        $user->update([
            'is_active' => false,
        ]);

        // 4. Safely attempt hard deletion if no foreign key constraints fail
        try {
            $user->delete();
        } catch (\Exception $e) {
            // If historical orders or foreign keys exist, user remains deactivated (is_active = false)
            // preserving database integrity and order history.
        }

        return response()->json([
            'success' => true,
            'message' => 'Your account has been deleted successfully.',
        ], 200);
    }
}