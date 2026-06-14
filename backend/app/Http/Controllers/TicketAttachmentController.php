<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTickets;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    use AuthorizesTickets;

    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'log', 'zip', 'xlsx'];

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

        $attachments = TicketAttachment::with(['uploadedBy.role'])
            ->where('TicketNumber', $ticket->TicketNumber)
            ->orderBy('CreatedDate')
            ->get()
            ->map(fn (TicketAttachment $attachment) => $this->attachmentPayload($attachment, $user));

        return response()->json($attachments);
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

        $validated = $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $validated['file'];
        $extension = strtolower($file->getClientOriginalExtension());

        if (!in_array($extension, $this->allowedExtensions, true)) {
            return response()->json([
                'message' => 'Unsupported file type.',
                'errors' => [
                    'file' => ['Allowed file types: ' . implode(', ', $this->allowedExtensions) . '.'],
                ],
            ], 422);
        }

        $storedName = uniqid('ticket_' . $ticket->TicketNumber . '_', true) . '.' . $extension;
        $path = $file->storeAs("ticket-attachments/{$ticket->TicketNumber}", $storedName, 'local');

        $attachment = TicketAttachment::create([
            'TicketNumber' => $ticket->TicketNumber,
            'UploadedByUserNumber' => $user->UserNumber,
            'FileName' => $file->getClientOriginalName(),
            'FilePath' => $path,
            'MimeType' => $file->getMimeType(),
            'FileSize' => $file->getSize(),
            'CreatedDate' => now()->toDateTimeString(),
        ])->load(['uploadedBy.role']);

        $this->logActivity(
            $user->UserNumber,
            'AttachmentAdded',
            $ticket->TicketNumber,
            "File {$attachment->FileName} attached to {$ticket->TicketReferenceNumber}.",
            null,
            $attachment->FileName
        );

        $message = "{$user->FullName} uploaded {$attachment->FileName} on {$ticket->TicketReferenceNumber}.";
        if ($this->roleName($user) === 'Employee') {
            $this->notifyUser(
                $ticket->AssignedToUserNumber,
                $user,
                'ticket_attachment_added',
                'Ticket attachment added',
                $message,
                $ticket->TicketNumber
            );
        } else {
            $this->notifyUser(
                $ticket->CreatedByUserNumber,
                $user,
                'ticket_attachment_added',
                'Ticket attachment added',
                $message,
                $ticket->TicketNumber
            );
        }

        return response()->json($this->attachmentPayload($attachment, $user), 201);
    }

    public function download($attachmentId)
    {
        $user = $this->resolveUser();
        $attachment = TicketAttachment::with('ticket')->find($attachmentId);

        if (!$attachment) {
            return response()->json(['message' => 'Attachment not found.'], 404);
        }

        if (!$this->canViewTicket($user, $attachment->ticket)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (!Storage::disk('local')->exists($attachment->FilePath)) {
            return response()->json(['message' => 'The file no longer exists on the server.'], 404);
        }

        return Storage::disk('local')->download(
            $attachment->FilePath,
            $attachment->FileName,
            ['Content-Type' => $attachment->MimeType ?: 'application/octet-stream']
        );
    }

    public function destroy($attachmentId)
    {
        $user = $this->resolveUser();
        $attachment = TicketAttachment::with('ticket')->find($attachmentId);

        if (!$attachment) {
            return response()->json(['message' => 'Attachment not found.'], 404);
        }

        if (!$this->canViewTicket($user, $attachment->ticket)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $canDelete = in_array($this->roleName($user), ['Admin', 'Manager'], true)
            || (int) $attachment->UploadedByUserNumber === (int) $user->UserNumber;

        if (!$canDelete) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        Storage::disk('local')->delete($attachment->FilePath);
        $ticketNumber = $attachment->TicketNumber;
        $fileName = $attachment->FileName;
        $attachment->delete();

        $this->logActivity($user->UserNumber, 'AttachmentDeleted', $ticketNumber, "File {$fileName} was deleted.");

        return response()->json(null, 204);
    }

    private function attachmentPayload(TicketAttachment $attachment, $viewer): array
    {
        return [
            'id' => $attachment->TicketAttachmentNumber,
            'ticket_id' => $attachment->TicketNumber,
            'uploaded_by_user_id' => $attachment->UploadedByUserNumber,
            'uploaded_by' => [
                'id' => $attachment->uploadedBy?->UserNumber,
                'name' => $attachment->uploadedBy?->FullName,
                'role' => $attachment->uploadedBy?->role?->RoleName,
            ],
            'original_filename' => $attachment->FileName,
            'mime_type' => $attachment->MimeType,
            'file_size' => $attachment->FileSize,
            'download_url' => url("/api/attachments/{$attachment->TicketAttachmentNumber}/download"),
            'can_delete' => in_array($this->roleName($viewer), ['Admin', 'Manager'], true)
                || (int) $attachment->UploadedByUserNumber === (int) $viewer->UserNumber,
            'created_at' => $attachment->CreatedDate,
        ];
    }
}
