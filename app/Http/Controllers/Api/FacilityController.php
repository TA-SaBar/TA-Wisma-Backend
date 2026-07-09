<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Models\Facility;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FacilityController extends Controller
{
    /**
     * Display a listing of facilities with filters.
     *
     * GET /api/facilities
     *
     * Query Parameters:
     * - status: READY, OCCUPIED, CLEANING, MAINTENANCE
     * - type: kamar, ruang_rapat
     * - gedung: string
     * - lantai: string
     * - search: string (name search)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Facility::query();

        // BUG ITERASI 1 SUDAH DIPERBAIKI:
        // Sebelumnya, blok filter status me-return lebih awal sehingga filter
        // type, gedung, lantai, dan search diabaikan ketika status diisi.
        // Sekarang semua filter dibangun secara berantai sebelum query dieksekusi.

        // Filter by status
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== '') {
            $query->where('type', $request->type);
        }

        // Filter by gedung
        if ($request->has('gedung') && $request->gedung !== '') {
            $query->where('gedung', $request->gedung);
        }

        // Filter by lantai
        if ($request->has('lantai') && $request->lantai !== '') {
            $query->where('lantai', $request->lantai);
        }

        // Search by name or description
        if ($request->has('search') && $request->search !== '') {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            });
        }

        $facilities = $query->orderBy('name')->get();

        return response()->json([
            'success' => true,
            'data'    => $facilities,
            'total'   => $facilities->count(),
        ]);
    }

    /**
     * Store a newly created facility.
     *
     * POST /api/facilities (Admin only)
     */
    public function store(StoreFacilityRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Handle file upload for photo
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('facilities', 'public');
            $data['photo'] = '/storage/' . $path;
        }

        $facility = Facility::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil ditambahkan.',
            'data' => $facility,
        ], 201);
    }

    /**
     * Display the specified facility.
     *
     * GET /api/facilities/{id}
     */
    public function show(Facility $facility): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $facility,
        ]);
    }

    /**
     * Update the specified facility.
     *
     * PUT /api/facilities/{id} (Admin only)
     */
    public function update(UpdateFacilityRequest $request, Facility $facility): JsonResponse
    {
        $data = $request->validated();

        // Handle file upload for photo
        if ($request->hasFile('photo')) {
            // Delete old photo if exists and is a local file
            if ($facility->photo && str_starts_with($facility->photo, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $facility->photo);
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('photo')->store('facilities', 'public');
            $data['photo'] = '/storage/' . $path;
        }

        $facility->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil diperbarui.',
            'data' => $facility->fresh(),
        ]);
    }

    /**
     * Remove the specified facility.
     *
     * DELETE /api/facilities/{id} (Admin only)
     */
    public function destroy(Facility $facility): JsonResponse
    {
        // Delete photo file if exists
        if ($facility->photo && str_starts_with($facility->photo, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $facility->photo);
            Storage::disk('public')->delete($oldPath);
        }

        $facility->delete();

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil dihapus.',
        ]);
    }
}
