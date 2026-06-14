<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTickets;
use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketAssignmentHistory;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
use App\Models\TicketStatusHistory;

class TicketHistoryController extends Controller
{
    use AuthorizesTickets;

    public function index($ticketId)
    {
        $user = $this->resolveUser();
        $ticket = Ticket::with(['creator.role'])->find($ticketId);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        if (!$this->canViewTicket($user, $ticket)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $events = [];

        $events[] = [
            'id' => 'ticket-created-' . $ticket->TicketNumber,
            'type' => 'ticket_created',
            'action' => 'Ticket created',
            'message' => "Ticket {$ticket->TicketReferenceNumber} was created.",
            'old_value' => null,
            'new_value' => $ticket->status?->StatusName ?? 'Open',
            'user' => $this->userPayload($ticket->creator),
            'created_at' => $ticket->CreatedDate,
        ];

        TicketStatusHistory::with(['previousStatus', 'newStatus', 'changedBy.role'])
            ->where('TicketNumber', $ticket->TicketNumber)
            ->get()
            ->each(function (TicketStatusHistory $history) use (&$events) {
                $old = $history->previousStatus?->StatusName ?? 'Unspecified';
                $new = $history->newStatus?->StatusName ?? 'Unspecified';
                $events[] = [
                    'id' => 'status-' . $history->TicketStatusHistoryNumber,
                    'type' => 'status_changed',
                    'action' => 'Status changed',
                    'message' => "Status changed from {$old} to {$new}.",
                    'old_value' => $old,
                    'new_value' => $new,
                    'user' => $this->userPayload($history->changedBy),
                    'created_at' => $history->CreatedDate,
                ];
            });

        TicketAssignmentHistory::with(['previousAssignedTo.role', 'assignedTo.role', 'assignedBy.role'])
            ->where('TicketNumber', $ticket->TicketNumber)
            ->get()
            ->each(function (TicketAssignmentHistory $history) use (&$events) {
                $old = $history->previousAssignedTo?->FullName ?? 'Unassigned';
                $new = $history->assignedTo?->FullName ?? 'Unassigned';
                $events[] = [
                    'id' => 'assignment-' . $history->TicketAssignmentHistoryNumber,
                    'type' => 'assignment_changed',
                    'action' => $history->PreviousAssignedToUserNumber ? 'Ticket reassigned' : 'Ticket assigned',
                    'message' => "Assignment changed from {$old} to {$new}.",
                    'old_value' => $old,
                    'new_value' => $new,
                    'user' => $this->userPayload($history->assignedBy),
                    'created_at' => $history->CreatedDate,
                ];
            });

        $comments = TicketComment::with(['user.role'])
            ->where('TicketNumber', $ticket->TicketNumber)
            ->when(!$this->isItRole($user), fn ($query) => $query->where('IsInternalNote', false))
            ->get();

        foreach ($comments as $comment) {
            $events[] = [
                'id' => 'comment-' . $comment->TicketCommentNumber,
                'type' => $comment->IsInternalNote ? 'internal_note_added' : 'comment_added',
                'action' => $comment->IsInternalNote ? 'Internal note added' : 'Comment added',
                'message' => ($comment->IsInternalNote ? 'Internal note' : 'Comment') . ' added.',
                'old_value' => null,
                'new_value' => null,
                'user' => $this->userPayload($comment->user),
                'created_at' => $comment->CreatedDate,
            ];
        }

        TicketAttachment::with(['uploadedBy.role'])
            ->where('TicketNumber', $ticket->TicketNumber)
            ->get()
            ->each(function (TicketAttachment $attachment) use (&$events) {
                $events[] = [
                    'id' => 'attachment-' . $attachment->TicketAttachmentNumber,
                    'type' => 'attachment_added',
                    'action' => 'File attached',
                    'message' => "File {$attachment->FileName} attached.",
                    'old_value' => null,
                    'new_value' => $attachment->FileName,
                    'user' => $this->userPayload($attachment->uploadedBy),
                    'created_at' => $attachment->CreatedDate,
                ];
            });

        ActivityLog::with(['user.role'])
            ->where('EntityType', 'Ticket')
            ->where('EntityReferenceNumber', $ticket->TicketNumber)
            ->whereIn('ActionType', ['TicketUpdated', 'CategoryChanged', 'PriorityChanged', 'AttachmentDeleted', 'CommentDeleted'])
            ->get()
            ->each(function (ActivityLog $log) use (&$events) {
                $events[] = [
                    'id' => 'activity-' . $log->ActivityLogNumber,
                    'type' => strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $log->ActionType)),
                    'action' => trim(preg_replace('/(?<!^)[A-Z]/', ' $0', $log->ActionType)),
                    'message' => $log->ActionDescription,
                    'old_value' => $log->OldValue,
                    'new_value' => $log->NewValue,
                    'user' => $this->userPayload($log->user),
                    'created_at' => $log->CreatedDate,
                ];
            });

        usort($events, fn ($a, $b) => strcmp((string) $a['created_at'], (string) $b['created_at']));

        return response()->json(array_values($events));
    }

    private function userPayload($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->UserNumber,
            'name' => $user->FullName,
            'role' => $user->role?->RoleName,
        ];
    }
}
