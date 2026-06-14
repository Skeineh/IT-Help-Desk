<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTickets;
use App\Models\Notification;

class NotificationController extends Controller
{
    use AuthorizesTickets;

    public function index()
    {
        $user = $this->resolveUser();

        $notifications = Notification::where('UserNumber', $user->UserNumber)
            ->orderBy('CreatedDate', 'desc')
            ->limit(100)
            ->get()
            ->map(fn (Notification $notification) => $this->notificationPayload($notification));

        return response()->json($notifications);
    }

    public function unreadCount()
    {
        $user = $this->resolveUser();

        return response()->json([
            'unread_count' => Notification::where('UserNumber', $user->UserNumber)
                ->where('IsRead', false)
                ->count(),
        ]);
    }

    public function markRead($notificationId)
    {
        $user = $this->resolveUser();
        $notification = Notification::where('UserNumber', $user->UserNumber)
            ->where('NotificationNumber', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notification->IsRead = true;
        $notification->ReadDate = now()->toDateTimeString();
        $notification->save();

        return response()->json($this->notificationPayload($notification));
    }

    public function markAllRead()
    {
        $user = $this->resolveUser();

        Notification::where('UserNumber', $user->UserNumber)
            ->where('IsRead', false)
            ->update([
                'IsRead' => true,
                'ReadDate' => now()->toDateTimeString(),
            ]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy($notificationId)
    {
        $user = $this->resolveUser();
        $notification = Notification::where('UserNumber', $user->UserNumber)
            ->where('NotificationNumber', $notificationId)
            ->first();

        if (!$notification) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notification->delete();

        return response()->json(null, 204);
    }

    private function notificationPayload(Notification $notification): array
    {
        return [
            'id' => $notification->NotificationNumber,
            'user_id' => $notification->UserNumber,
            'title' => $notification->Title,
            'message' => $notification->Message,
            'type' => $notification->NotificationType,
            'related_ticket_id' => $notification->TicketNumber,
            'is_read' => (bool) $notification->IsRead,
            'read_at' => $notification->ReadDate,
            'created_at' => $notification->CreatedDate,
        ];
    }
}
