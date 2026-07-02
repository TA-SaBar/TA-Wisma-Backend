<?php

namespace App\Models;

use Database\Factories\FacilityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Facility extends Model
{
    /** @use HasFactory<FacilityFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'gedung',
        'lantai',
        'capacity',
        'price',
        'unit',
        'luas',
        'bed',
        'status',
        'photo',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'capacity' => 'integer',
        ];
    }

    /**
     * Get the bookings for the facility.
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
