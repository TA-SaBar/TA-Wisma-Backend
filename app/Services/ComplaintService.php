<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\Notification;
use App\Models\User;

class ComplaintService
{
    /**
     * Create a new complaint from a guest.
     *
     * @param User $user
     * @param array $data
     * @return Complaint
     */
    public function createComplaint(User $user, array $data): Complaint
    {
        $complaint = Complaint::create([
            'complaint_code' => Complaint::generateComplaintCode(),
            'user_id'        => $user->id,
            'title'          => $data['title'],
            'category'       => $data['category'],
            'location'       => $data['location'],
            'description'    => $data['description'] ?? null,
            'status'         => 'pending',
        ]);

        // Send notification to Customer Service
        $csUsers = User::where('role', 'customer_service')->get();
        foreach ($csUsers as $cs) {
            Notification::create([
                'user_id' => $cs->id,
                'type'    => 'complaint',
                'title'   => 'Keluhan Baru',
                'message' => "Tamu {$user->name} mengajukan keluhan baru: {$complaint->title} di {$complaint->location}.",
                'is_read' => false,
            ]);
        }

        return $complaint;
    }

    /**
     * Mark complaint as being processed.
     *
     * @param Complaint $complaint
     * @return void
     */
    public function processComplaint(Complaint $complaint): void
    {
        $complaint->update(['status' => 'processed']);

        if ($complaint->user_id) {
            Notification::create([
                'user_id' => $complaint->user_id,
                'type'    => 'complaint',
                'title'   => 'Keluhan Sedang Diproses',
                'message' => "Keluhan Anda ({$complaint->complaint_code}) sedang ditangani oleh tim Customer Service kami.",
                'is_read' => false,
            ]);
        }
    }

    /**
     * Mark complaint as resolved by CS.
     *
     * @param Complaint $complaint
     * @param User $csUser
     * @return void
     */
    public function resolveComplaint(Complaint $complaint, User $csUser): void
    {
        $complaint->update([
            'status'      => 'resolved',
            'resolved_by' => $csUser->name,
            'resolved_at' => now(),
        ]);

        Notification::create([
            'user_id'      => $complaint->user_id,
            'type'         => 'info',
            'title'        => 'Konfirmasi Penyelesaian Keluhan',
            'message'      => "Keluhan Anda ({$complaint->complaint_code}) di {$complaint->location} telah diselesaikan oleh tim teknis. Mohon konfirmasi apakah masalah telah benar-benar teratasi.",
            'related_id'   => $complaint->id,
            'related_type' => 'complaint_confirmation'
        ]);
    }

    /**
     * Confirm complaint resolution by guest.
     *
     * @param Complaint $complaint
     * @return void
     */
    public function confirmComplaint(Complaint $complaint): void
    {
        $complaint->update([
            'is_guest_confirmed' => true,
        ]);
    }

    /**
     * Manually create a complaint by CS/Koordinator.
     *
     * @param array $data
     * @return Complaint
     */
    public function createManualComplaint(array $data): Complaint
    {
        $complaint = Complaint::create([
            'complaint_code' => Complaint::generateComplaintCode(),
            'user_id'        => $data['user_id'],
            'title'          => $data['title'],
            'category'       => $data['category'],
            'location'       => $data['location'],
            'description'    => $data['description'] ?? null,
            'status'         => 'pending',
            'resolved_by'    => null,
        ]);
        
        $complaint->load('user');

        return $complaint;
    }
}
