<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facility extends Model
{
    use HasFactory;

    protected $fillable = [
        'name_ms',
        'name_en',
        'description',
        'description_ms',
        'image',
        'capacity',
        'deposit_amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'deposit_amount' => 'decimal:2',
        ];
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    /** Description in the selected language (falls back to the other one). */
    public function localDescription(): ?string
    {
        if (app()->getLocale() === 'ms') {
            return $this->description_ms ?: $this->description;
        }

        return $this->description ?: $this->description_ms;
    }

    /** Public URL of the facility picture, or null when none was uploaded. */
    public function imageUrl(): ?string
    {
        return $this->image ? asset('images/facilities/' . $this->image) : null;
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
