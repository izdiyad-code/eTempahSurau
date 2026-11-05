<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ms',
        'name_en',
        'description',
        'total_quantity',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'total_quantity' => 'integer',
        ];
    }

    public function bookings()
    {
        return $this->belongsToMany(
            Booking::class,
            'booking_equipment'
        )
        ->withPivot('quantity')
        ->withTimestamps();
    }

    public function isAvailable(): bool
    {
        return $this->status === 'available';
    }

    /** Name in the language currently selected (EN / BM). */
    public function name(): string
    {
        return app()->getLocale() === 'ms' && $this->name_ms ? $this->name_ms : $this->name_en;
    }
}
