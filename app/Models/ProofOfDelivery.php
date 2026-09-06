<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProofOfDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'rider_id',
        'photo_path',
        'latitude',
        'longitude',
        'captured_at',
    ];

    protected $casts = [
        'latitude'    => 'float',
        'longitude'   => 'float',
        'captured_at' => 'datetime',
    ];

    // photo_url is computed, never stored — append it so it's automatically
    // included whenever this model is serialized (e.g. via Order::load()).
    protected $appends = ['photo_url'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rider_id');
    }

    /**
     * Public URL for the stored POD photo.
     * Assumes the 'public' disk (storage/app/public + `storage` symlink).
     * If this project already uses a different disk/URL convention for
     * other uploads (product images, avatars, etc.), swap this to match.
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if (!$this->photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }
}