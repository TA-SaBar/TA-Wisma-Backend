<?php

namespace App\Models;

use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    /** @use HasFactory<BookingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'booking_code',
        'user_id',
        'facility_id',
        'check_in',
        'check_out',
        'nights',
        'subtotal',
        'tax',
        'total_price',
        'status',
        'guest_name',
        'guest_nip',
        'guest_phone',
        'guest_email',
        'payment_method',
        'snap_token',
        'midtrans_order_id',
        'paid_at',
        'checked_in_at',
        'checked_out_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date:Y-m-d',
            'check_out' => 'date:Y-m-d',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'total_price' => 'decimal:2',
            'paid_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'checked_out_at' => 'datetime',
            'nights' => 'integer',
        ];
    }

    protected $appends = ['has_feedback'];

    /**
     * Get the has_feedback attribute to indicate if the booking has feedback.
     */
    public function getHasFeedbackAttribute(): bool
    {
        return $this->feedback()->exists();
    }

    /**
     * Generate a unique booking code.
     */
    public static function generateBookingCode(): string
    {
        $year = date('Y');
        $random = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        $code = "WDPR-{$year}-{$random}";

        // Ensure uniqueness
        while (self::where('booking_code', $code)->exists()) {
            $random = str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);
            $code = "WDPR-{$year}-{$random}";
        }

        return $code;
    }

    /**
     * Get the user that owns the booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the facility that is booked.
     */
    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    /**
     * Get the feedback for the booking.
     */
    public function feedback(): HasOne
    {
        return $this->hasOne(Feedback::class);
    }
}
