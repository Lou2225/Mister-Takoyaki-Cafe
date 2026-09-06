<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Accessor for full name (for compatibility with Auth::user()->name).
     */
    public function getNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Role checking helpers
     */
    public function isSuperAdmin(): bool
    {
        return $this->role_id === 1;
    }

    public function isAdmin(): bool
    {
        return $this->role_id === 2;
    }

    public function isStaff(): bool
    {
        return $this->role_id === 3;
    }

    public function isRider(): bool
    {
        return $this->role_id === 5;
    }

    /**
     * Centralized RBAC Theme Configuration
     */
    public function getRoleTheme(): array
    {
        return match($this->role_id) {
            1 => [ // Super Admin
                'primary'    => 'indigo',
                'bg_button'  => 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500',
                'border'     => 'border-indigo-600',
                'text'       => 'text-indigo-600',
                'hover_bg'   => 'bg-indigo-50',
                'gradient'   => 'from-gray-900 via-slate-800 to-gray-950',
                'ring'       => 'ring-white/10',
                'badge_bg'   => 'bg-white/10 text-white border border-white/20',
                'sub_color'  => 'text-gray-300',
                'icon_bg'    => 'bg-white/10',
                'icon_color' => 'text-white',
                'badge_text' => 'SUPER ADMIN ACCESS',
                'tagline'    => 'You have full system privileges across all branches.',
                'icon_path'  => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                'progress'   => 'bg-gradient-to-r from-indigo-400 via-purple-400 to-pink-400',
                'header_track' => 'tracking-tight',
            ],
            2 => [ // Admin
                'primary'    => 'rose',
                'bg_button'  => 'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500',
                'border'     => 'border-rose-600',
                'text'       => 'text-rose-600',
                'hover_bg'   => 'bg-rose-50',
                'gradient'   => 'from-rose-950 via-red-900 to-rose-950',
                'ring'       => 'ring-rose-500/20',
                'badge_bg'   => 'bg-white/10 text-white border border-white/20',
                'sub_color'  => 'text-gray-200',
                'icon_bg'    => 'bg-white/10',
                'icon_color' => 'text-white',
                'badge_text' => 'BRANCH MANAGER ACCESS',
                'tagline'    => $this->branch ? "Overseeing {$this->branch->branch_name} — your branch is live." : 'Your branch is live and operational.',
                'icon_path'  => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                'progress'   => 'bg-gradient-to-r from-red-400 via-rose-400 to-orange-400',
                'header_track' => 'tracking-tight',
            ],
            3 => [ // Cashier
                'primary'    => 'emerald',
                'bg_button'  => 'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500',
                'border'     => 'border-emerald-600',
                'text'       => 'text-emerald-600',
                'hover_bg'   => 'bg-emerald-50',
                'gradient'   => 'from-emerald-950 via-teal-900 to-emerald-950',
                'ring'       => 'ring-emerald-500/20',
                'badge_bg'   => 'bg-white/10 text-white border border-white/20',
                'sub_color'  => 'text-gray-200',
                'icon_bg'    => 'bg-white/10',
                'icon_color' => 'text-white',
                'badge_text' => 'CASHIER ACCESS',
                'tagline'    => 'Welcome to your shift dashboard.',
                'icon_path'  => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                'progress'   => 'bg-gradient-to-r from-emerald-400 via-teal-400 to-cyan-400',
                'header_track' => 'tracking-tight',
            ],
            5 => [ // Delivery Rider (Fallback if they access web)
                'primary'    => 'amber',
                'bg_button'  => 'bg-amber-600 hover:bg-amber-700 focus:ring-amber-500',
                'border'     => 'border-amber-600',
                'text'       => 'text-amber-600',
                'hover_bg'   => 'bg-amber-50',
                'gradient'   => 'from-amber-950 via-orange-900 to-amber-950',
                'ring'       => 'ring-amber-500/20',
                'badge_bg'   => 'bg-white/10 text-white border border-white/20',
                'sub_color'  => 'text-gray-200',
                'icon_bg'    => 'bg-white/10',
                'icon_color' => 'text-white',
                'badge_text' => 'RIDER ACCESS',
                'tagline'    => 'Mobile app access only.',
                'icon_path'  => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
                'progress'   => 'bg-gradient-to-r from-amber-400 via-orange-400 to-red-400',
                'header_track' => 'tracking-tight',
            ],
            default => [ // Others
                'primary'    => 'slate',
                'bg_button'  => 'bg-slate-600 hover:bg-slate-700 focus:ring-slate-500',
                'border'     => 'border-slate-600',
                'text'       => 'text-slate-600',
                'hover_bg'   => 'bg-slate-50',
                'gradient'   => 'from-slate-950 via-slate-900 to-slate-950',
                'ring'       => 'ring-slate-500/20',
                'badge_bg'   => 'bg-white/10 text-white border border-white/20',
                'sub_color'  => 'text-gray-200',
                'icon_bg'    => 'bg-white/10',
                'icon_color' => 'text-white',
                'badge_text' => 'GUEST ACCESS',
                'tagline'    => 'Limited access account.',
                'icon_path'  => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z',
                'progress'   => 'bg-slate-400',
                'header_track' => 'tracking-tight',
            ],
        };
    }

    /**
     * Orders processed by this user (Cashier).
     */
    public function ordersHandled()
    {
        return $this->hasMany(Order::class, 'user_id');
    }

    /**
     * Orders delivered by this user (Rider).
     */
    public function deliveries()
    {
        return $this->hasMany(Order::class, 'rider_id');
    }
    
    public function addresses()
    {
        return $this->hasMany(\App\Models\UserAddress::class);
    }
    
    /**
     * Stock movements performed by this user (Admin/Manager).
     */
    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'user_id');
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'address',
        'phone',
        'email_verified_at',
        'password',
        'role_id',
        'branch_id',
        'date_hired',
        'position',
        'avatar',
        'is_active',
        'hide_modules',
        'latitude',
        'longitude',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
        'hide_modules'      => 'boolean',
    ];
    
}
