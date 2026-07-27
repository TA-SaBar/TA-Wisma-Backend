<?php

namespace App\Services;

use App\Models\Facility;
use App\Models\Booking;
use Illuminate\Support\Facades\Storage;

class FacilityService
{
    /**
     * Store a new facility with uploaded photo.
     *
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|null $photo
     * @return Facility
     */
    public function storeFacility(array $data, $photo): Facility
    {
        if ($photo) {
            $path = $photo->store('facilities', 'public');
            $data['photo'] = '/storage/' . $path;
        }

        return Facility::create($data);
    }

    /**
     * Update an existing facility and handle photo replacement.
     *
     * @param Facility $facility
     * @param array $data
     * @param \Illuminate\Http\UploadedFile|null $photo
     * @return Facility
     */
    public function updateFacility(Facility $facility, array $data, $photo): Facility
    {
        if ($photo) {
            $this->deletePhotoPhysical($facility->photo);
            $path = $photo->store('facilities', 'public');
            $data['photo'] = '/storage/' . $path;
        }

        $facility->update($data);

        return $facility->fresh();
    }

    /**
     * Delete a facility and its associated photo.
     *
     * @param Facility $facility
     * @return void
     */
    public function deleteFacility(Facility $facility): void
    {
        $this->deletePhotoPhysical($facility->photo);
        $facility->delete();
    }

    /**
     * Helper to physically remove a photo from storage.
     *
     * @param string|null $photoPath
     * @return void
     */
    private function deletePhotoPhysical(?string $photoPath): void
    {
        if ($photoPath && str_starts_with($photoPath, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $photoPath);
            Storage::disk('public')->delete($oldPath);
        }
    }

    /**
     * Get booked dates for a specific facility.
     *
     * @param Facility $facility
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getBookedDates(Facility $facility)
    {
        return Booking::where('facility_id', $facility->id)
            ->where(function ($q) {
                $q->whereIn('status', ['lunas', 'check_in'])
                  ->orWhere(function ($q2) {
                      $q2->where('status', 'pending')
                         ->where('created_at', '>=', now()->subMinutes(60));
                  });
            })
            ->get(['check_in', 'check_out']);
    }
}
