<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComplaintRequest;
use App\Models\Complaint;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    /**
     * Tampilkan daftar keluhan.
     * - Guest: hanya milik sendiri
     * - Customer Service & Koordinator: semua keluhan
     *
     * GET /api/complaints
     */
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Complaint::with('user')->latest();

        if ($user->role === 'guest') {
            $query->where('user_id', $user->id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search by title or complaint_code
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', '%' . $search . '%')
                  ->orWhere('complaint_code', 'like', '%' . $search . '%');
            });
        }

        $complaints = $query->get();

        return response()->json([
            'success' => true,
            'data'    => $complaints,
            'total'   => $complaints->count(),
        ]);
    }

    /**
     * Buat keluhan baru (hanya guest).
     *
     * POST /api/complaints
     */
    public function store(StoreComplaintRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $complaint = Complaint::create([
            'complaint_code' => Complaint::generateComplaintCode(),
            'user_id'        => $user->id,
            'title'          => $data['title'],
            'category'       => $data['category'],
            'location'       => $data['location'],
            'description'    => $data['description'] ?? null,
            'status'         => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Keluhan berhasil dikirim. Kami akan segera menindaklanjuti.',
            'data'    => $complaint,
        ], 201);
    }

    /**
     * Tandai keluhan sedang diproses (hanya Customer Service).
     *
     * PUT /api/complaints/{complaint}/process
     */
    public function process(Request $request, Complaint $complaint): JsonResponse
    {
        if ($complaint->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya keluhan berstatus pending yang dapat diproses.',
            ], 422);
        }

        $complaint->update(['status' => 'processed']);

        // Kirim notifikasi ke pelapor
        if ($complaint->user_id) {
            Notification::create([
                'user_id' => $complaint->user_id,
                'type'    => 'complaint',
                'title'   => 'Keluhan Sedang Diproses',
                'message' => "Keluhan Anda ({$complaint->complaint_code}) sedang ditangani oleh tim Customer Service kami.",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Keluhan {$complaint->complaint_code} berhasil ditandai sedang diproses.",
            'data'    => $complaint->fresh('user'),
        ]);
    }

    /**
     * Tandai keluhan telah selesai/diselesaikan (hanya Customer Service).
     *
     * PUT /api/complaints/{complaint}/resolve
     */
    public function resolve(Request $request, Complaint $complaint): JsonResponse
    {
        if ($complaint->status !== 'processed') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya keluhan berstatus diproses yang dapat diselesaikan.',
            ], 422);
        }

        $complaint->update([
            'status'      => 'resolved',
            'resolved_by' => $request->user()->name,
            'resolved_at' => now(),
        ]);

        // Kirim notifikasi ke pelapor
        if ($complaint->user_id) {
            Notification::create([
                'user_id' => $complaint->user_id,
                'type'    => 'complaint',
                'title'   => 'Keluhan Telah Diselesaikan',
                'message' => "Keluhan Anda ({$complaint->complaint_code}) telah berhasil diselesaikan. Terima kasih atas laporan Anda.",
                'is_read' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Keluhan {$complaint->complaint_code} telah berhasil diselesaikan.",
            'data'    => $complaint->fresh('user'),
        ]);
    }

    /**
     * Buat keluhan secara manual oleh Customer Service / Koordinator Wisma.
     * Digunakan ketika tamu melapor secara offline (via telepon / tatap muka).
     *
     * POST /api/complaints/manual
     */
    public function storeManual(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'       => ['required', 'string', 'max:255'],
            'category'    => ['required', 'in:fasilitas,layanan,kebersihan,keamanan,lainnya'],
            'location'    => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'guest_name'  => ['required', 'string', 'max:255'],
        ]);

        $complaint = Complaint::create([
            'complaint_code' => Complaint::generateComplaintCode(),
            'user_id'        => null, // tidak terikat akun karena dilaporkan offline
            'title'          => $data['title'],
            'category'       => $data['category'],
            'location'       => $data['location'],
            'description'    => $data['description'] ?? null,
            'status'         => 'pending',
            'resolved_by'    => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Tiket keluhan manual ({$complaint->complaint_code}) berhasil dibuat atas nama {$data['guest_name']}.",
            'data'    => $complaint,
        ], 201);
    }
}
