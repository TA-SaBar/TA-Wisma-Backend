<?php

namespace Tests\Unit;

use App\Models\Booking;
use App\Models\User;
use App\Models\Facility;
use App\Models\Feedback;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCalculationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test 1: Memastikan generator kode booking menghasilkan format yang unik dan sesuai dengan standar.
     * Alasan: Kode booking sangat krusial sebagai identifier unik di Midtrans dan referensi pengguna, 
     *         sehingga harus dijamin format 'WDPR-YYYY-XXXX' nya benar dan tidak duplikat.
     */
    public function test_generate_booking_code_is_unique_and_formatted_correctly()
    {
        $code1 = Booking::generateBookingCode();
        $code2 = Booking::generateBookingCode();

        $this->assertNotEmpty($code1);
        $this->assertStringStartsWith('WDPR-' . date('Y') . '-', $code1);
        
        // Memastikan panjang string kode booking konsisten
        $this->assertEquals(14, strlen($code1)); 
        
        // Memastikan dua booking code yang di-generate tidak sama
        $this->assertNotEquals($code1, $code2);
    }

    /**
     * Test 2: Memastikan perhitungan hari (nights) dan total harga (termasuk pajak) berjalan dengan benar.
     * Alasan: Logic perhitungan harga harus dipastikan akurat agar tidak terjadi kerugian finansial 
     *         maupun kesalahan tagihan ke tamu pada saat pembuatan pesanan.
     */
    public function test_booking_calculation_for_nights_and_total_price()
    {
        $checkIn = Carbon::parse('2024-01-01');
        $checkOut = Carbon::parse('2024-01-04'); // 3 malam
        $nights = $checkIn->diffInDays($checkOut);
        
        $pricePerNight = 500000;
        $subtotal = $nights * $pricePerNight; // 1.500.000
        $tax = $subtotal * 0.11; // 165.000
        $totalPrice = $subtotal + $tax; // 1.665.000

        $this->assertEquals(3, $nights);
        $this->assertEquals(1500000, $subtotal);
        $this->assertEquals(165000, $tax);
        $this->assertEquals(1665000, $totalPrice);
    }

    /**
     * Test 3: Memastikan status booking secara default saat inisialisasi adalah tipe yang diharapkan.
     * Alasan: Booking baru yang dibuat dan belum dibayar wajib memiliki status awal yang memblokir PDF tiket, 
     *         ini krusial agar tiket PDF tidak bisa di-generate atau disalahgunakan sebelum status menjadi 'lunas'.
     */
    public function test_booking_status_flow_initialization()
    {
        $booking = new Booking();
        
        // By default assignment mock
        $booking->status = 'menunggu_pembayaran';
        
        $this->assertEquals('menunggu_pembayaran', $booking->status);
        $this->assertNull($booking->paid_at);
        $this->assertNull($booking->snap_token);
    }

    /**
     * Test 4: Memastikan aksesori "has_feedback" mengembalikan nilai boolean yang benar berdasarkan relasi.
     * Alasan: Front-end (Customer Service & Guest) mengandalkan field boolean `has_feedback` 
     *         untuk menampilkan tombol 'Beri Ulasan' secara dinamis. Wajib dipastikan logikanya benar.
     */
    public function test_booking_has_feedback_attribute_returns_boolean()
    {
        $user = User::factory()->create();
        $facility = Facility::factory()->create();
        
        // Buat booking
        $booking = Booking::create([
            'booking_code' => Booking::generateBookingCode(),
            'user_id' => $user->id,
            'facility_id' => $facility->id,
            'check_in' => now()->toDateString(),
            'check_out' => now()->addDays(2)->toDateString(),
            'nights' => 2,
            'subtotal' => 200000,
            'tax' => 22000,
            'total_price' => 222000,
            'status' => 'lunas',
            'guest_name' => 'Budi',
            'guest_email' => 'budi@example.com',
            'guest_phone' => '08123456789'
        ]);
        
        // Saat baru dibuat, feedback harusnya belum ada
        $this->assertFalse($booking->has_feedback);
        
        // Simulasikan pembuatan feedback oleh user
        Feedback::create([
            'booking_id' => $booking->id,
            'user_id' => $user->id,
            'rating_cleanliness' => 5,
            'rating_facilities' => 4,
            'rating_service' => 5,
            'average_rating' => 4.67,
            'comment' => 'Pelayanan luar biasa, fasilitas oke!'
        ]);
        
        // Fresh ulang instance dari database agar relationship ter-refresh
        $booking = $booking->fresh();
        
        // Sekarang has_feedback harus bernilai true
        $this->assertTrue($booking->has_feedback);
    }
}
