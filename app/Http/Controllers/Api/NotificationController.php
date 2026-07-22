<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Ambil daftar notifikasi milik user yang sedang login.
     *
     * GET /api/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => 'NOTIF-' . str_pad($notif->id, 3, '0', STR_PAD_LEFT),
                    'title' => $notif->title,
                    'message' => $notif->message,
                    'time' => $notif->created_at->format('d M Y, H:i'),
                    'type' => $notif->type ?? 'system',
                    'read' => (bool) $notif->is_read,
                    'refId' => $notif->related_id,
                ];
            });

        $unreadCount = Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success'      => true,
            'data'         => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * Tandai semua notifikasi sebagai sudah dibaca.
     *
     * PUT /api/notifications/read-all
     */
    
    /**
     * Tandai satu notifikasi sebagai telah dibaca.
     *
     * PUT /api/notifications/{id}/read
     */
    public function markRead(Request $request, $id): JsonResponse
    {
        $realId = (int) str_replace('NOTIF-', '', $id);
        $notif = Notification::where('user_id', $request->user()->id)->find($realId);

        if (!$notif) {
            return response()->json([
                'success' => false,
                'message' => 'Notifikasi tidak ditemukan'
            ], 404);
        }

        $notif->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi ditandai telah dibaca'
        ]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        Notification::where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Semua notifikasi telah ditandai sebagai dibaca.',
        ]);
    }
    /**
     * Hapus notifikasi.
     *
     * DELETE /api/notifications/{id}
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $realId = (int) str_replace('NOTIF-', '', $id);
        $notification = Notification::where('user_id', $request->user()->id)->where('id', $realId)->first();
        if ($notification) {
            $notification->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notifikasi berhasil dihapus.',
        ]);
    }
}
