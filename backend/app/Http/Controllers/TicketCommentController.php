<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTickets;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Http\Request;

class TicketCommentController extends Controller
{
    use AuthorizesTickets;

    public function index($ticketId)
    {
        $user = $this->resolveUser();
        $ticket = Ticket::find($ticketId);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        if (!$this->canViewTicket($user, $ticket)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $query = TicketComment::with(['user.role'])
            ->where('TicketNumber', $ticket->TicketNumber)
            ->orderBy('CreatedDate');

        if (!$this->isItRole($user)) {
            $query->where('IsInternalNote', false);
        }

        return response()->json($query->get()->map(fn (TicketComment $comment) => $this->commentPayload($comment)));
    }

    public function store(Request $request, $ticketId)
    {
        $user = $this->resolveUser();
        $ticket = Ticket::with(['creator', 'assignedTo'])->find($ticketId);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        if (!$this->canViewTicket($user, $ticket)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $request->merge([
            'body' => $request->input('body', $request->input('CommentText', $request->input('comment'))),
            'is_internal' => $request->boolean('is_internal', $request->boolean('IsInternalNote')),
        ]);

        $validated = $request->validate([
            'body' => 'required|string|min:1|max:5000',
            'is_internal' => 'sometimes|boolean',
        ]);

        $isInternal = (bool) ($validated['is_internal'] ?? false);
        if ($isInternal && !$this->isItRole($user)) {
            return response()->json(['message' => 'Employees cannot create internal notes.'], 403);
        }

        $comment = TicketComment::create([
            'TicketNumber' => $ticket->TicketNumber,
            'UserNumber' => $user->UserNumber,
            'CommentText' => $validated['body'],
            'IsInternalNote' => $isInternal,
            'CreatedDate' => now()->toDateTimeString(),
        ])->load(['user.role']);

        $action = $isInternal ? 'InternalNoteAdded' : 'CommentAdded';
        $this->logActivity(
            $user->UserNumber,
            $action,
            $ticket->TicketNumber,
            ($isInternal ? 'Internal note' : 'Comment') . " added to {$ticket->TicketReferenceNumber}."
        );

        $title = $isInternal ? 'Internal note added' : 'New ticket comment';
        $message = "{$user->FullName} added " . ($isInternal ? 'an internal note' : 'a comment') . " on {$ticket->TicketReferenceNumber}.";

        if ($isInternal) {
            $itUsers = User::whereHas('role', fn ($q) => $q->whereIn('RoleName', ['Admin', 'Manager']))
                ->pluck('UserNumber')
                ->all();
            $this->notifyMany(
                array_merge($itUsers, [$ticket->AssignedToUserNumber]),
                $user,
                'ticket_commented',
                $title,
                $message,
                $ticket->TicketNumber
            );
        } elseif ($this->roleName($user) === 'Employee') {
            $this->notifyUser(
                $ticket->AssignedToUserNumber,
                $user,
                'ticket_commented',
                $title,
                $message,
                $ticket->TicketNumber
            );
        } else {
            $this->notifyUser(
                $ticket->CreatedByUserNumber,
                $user,
                'ticket_commented',
                $title,
                $message,
                $ticket->TicketNumber
            );
        }

        return response()->json($this->commentPayload($comment), 201);
    }

    public function destroy($commentId)
    {
        $user = $this->resolveUser();

        if ($this->roleName($user) !== 'Admin') {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        $comment = TicketComment::find($commentId);
        if (!$comment) {
            return response()->json(['message' => 'Comment not found.'], 404);
        }

        $ticketNumber = $comment->TicketNumber;
        $comment->delete();

        $this->logActivity($user->UserNumber, 'CommentDeleted', $ticketNumber, 'A ticket comment was deleted.');

        return response()->json(null, 204);
    }

    private function commentPayload(TicketComment $comment): array
    {
        return [
            'id' => $comment->TicketCommentNumber,
            'ticket_id' => $comment->TicketNumber,
            'user_id' => $comment->UserNumber,
            'user' => [
                'id' => $comment->user?->UserNumber,
                'name' => $comment->user?->FullName,
                'role' => $comment->user?->role?->RoleName,
            ],
            'body' => $comment->CommentText,
            'comment' => $comment->CommentText,
            'is_internal' => (bool) $comment->IsInternalNote,
            'created_at' => $comment->CreatedDate,
            'updated_at' => $comment->UpdatedDate ?? $comment->CreatedDate,
        ];
    }
}
