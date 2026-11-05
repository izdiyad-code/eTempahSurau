<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ms',
        'name_en',
        'description',
        'status',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Name in the language currently selected (EN / BM). */
    public function name(): string
    {
        return app()->getLocale() === 'ms' && $this->name_ms ? $this->name_ms : $this->name_en;
    }
}
