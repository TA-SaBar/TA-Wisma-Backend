<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'feedbacks';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_id',
        'user_id',
        'rating_cleanliness',
        'rating_facilities',
        'rating_service',
        'average_rating',
        'comment',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'average_rating' => 'decimal:2',
            'rating_cleanliness' => 'integer',
            'rating_facilities' => 'integer',
            'rating_service' => 'integer',
        ];
    }

    /**
     * Get the booking that the feedback belongs to.
     */
    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /**
     * Get the user that wrote the feedback.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
