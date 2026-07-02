<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Complaint extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'complaint_code',
        'user_id',
        'title',
        'category',
        'location',
        'description',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique complaint code.
     */
    public static function generateComplaintCode(): string
    {
        $random = random_int(100, 999);
        $code = "COMP-{$random}";

        while (self::where('complaint_code', $code)->exists()) {
            $random = random_int(100, 999);
            $code = "COMP-{$random}";
        }

        return $code;
    }

    /**
     * Get the user that reported the complaint.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
