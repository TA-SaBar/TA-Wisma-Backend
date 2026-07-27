<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFacilityRequest;
use App\Http\Requests\UpdateFacilityRequest;
use App\Models\Facility;
use App\Services\FacilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FacilityController extends Controller
{
    protected FacilityService $facilityService;

    public function __construct(FacilityService $facilityService)
    {
        $this->facilityService = $facilityService;
    }

    /**
     * Display a listing of facilities with filters.
     *
     * GET /api/facilities
     *
     * Query Parameters:
     * - status: READY, OCCUPIED, CLEANING, MAINTENANCE
     * - type: kamar, ruang_rapat
     * - gedung: string
     * - area: string
     * - search: string (name search)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Facility::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('area')) {
            $query->where('area', $request->area);
        }

        if ($request->filled('search')) {
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
        $facility = $this->facilityService->storeFacility(
            $request->validated(), 
            $request->file('photo')
        );

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
     * Display the booked dates for a specific facility.
     *
     * GET /api/facilities/{id}/booked-dates
     */
    public function bookedDates(Facility $facility): JsonResponse
    {
        $bookings = $this->facilityService->getBookedDates($facility);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }

    /**
     * Update the specified facility.
     *
     * PUT /api/facilities/{id} (Admin only)
     */
    public function update(UpdateFacilityRequest $request, Facility $facility): JsonResponse
    {
        $facility = $this->facilityService->updateFacility(
            $facility, 
            $request->validated(), 
            $request->file('photo')
        );

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil diperbarui.',
            'data' => $facility,
        ]);
    }

    /**
     * Remove the specified facility.
     *
     * DELETE /api/facilities/{id} (Admin only)
     */
    public function destroy(Facility $facility): JsonResponse
    {
        $this->facilityService->deleteFacility($facility);

        return response()->json([
            'success' => true,
            'message' => 'Fasilitas berhasil dihapus.',
        ]);
    }
}
