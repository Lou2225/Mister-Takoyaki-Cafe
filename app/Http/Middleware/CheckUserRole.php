<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            abort(401, 'Unauthorized');
        }

        // Map role names to role IDs
        $roleMap = [
            'super_admin' => 1,
            'admin'       => 2,
            'staff'       => 3,
            'cashier'     => 3, // Staff is now Cashier
            'customer'    => 4,
            'rider'       => 5,
        ];

        // Explode roles by pipe or comma
        $allowedRoles = explode('|', $roles);
        $allowedRoleIds = [];

        foreach ($allowedRoles as $role) {
            if (isset($roleMap[trim($role)])) {
                $allowedRoleIds[] = $roleMap[trim($role)];
            }
        }

        if (empty($allowedRoleIds) || !in_array($user->role_id, $allowedRoleIds)) {
            abort(403, "Unauthorized: This action requires special privileges.");
        }

        return $next($request);
    }
}
