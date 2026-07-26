<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:auto-cancel-bookings')]
#[Description('Command description')]
class AutoCancelBookings extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredBookings = \App\Models\Booking::where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes(60))
            ->get();

        $count = 0;
        foreach ($expiredBookings as $booking) {
            $booking->update(['status' => 'cancelled']);
            $count++;
        }

        $this->info("Berhasil membatalkan {$count} pesanan (booking) yang kadaluwarsa.");
    }
}
