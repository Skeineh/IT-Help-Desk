<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTickets;
use App\Models\Category;
use App\Models\Priority;
use App\Models\Status;
use App\Models\Ticket;
use App\Models\TicketAssignmentHistory;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    use AuthorizesTickets;

    // GET /api/tickets
    public function index(Request $request)
    {
        $user     = $this->resolveUser();
        $roleName = $user->role->RoleName;

        $query = Ticket::with(['category', 'priority', 'status', 'creator', 'assignedTo']);

        // Employees only see their own tickets
        if ($roleName === 'Employee') {
            $query->where('CreatedByUserNumber', $user->UserNumber);
        }

        // Agents only see tickets assigned to them
        if ($roleName === 'Agent') {
            $query->where('AssignedToUserNumber', $user->UserNumber);
        }

        // Search filter
        if ($s = $request->search) {
            $query->where(function ($q) use ($s) {
                $q->where('Title', 'like', "%{$s}%")
                  ->orWhere('Description', 'like', "%{$s}%")
                  ->orWhere('TicketReferenceNumber', 'like', "%{$s}%");
            });
        }

        if ($v = $request->status)   { $query->where('StatusNumber', $v); }
        if ($v = $request->category) { $query->where('CategoryNumber', $v); }
        if ($v = $request->priority) { $query->where('PriorityNumber', $v); }

        return response()->json(
            $query->orderBy('CreatedDate', 'desc')->paginate(15)
        );
    }

    // GET /api/tickets/{id}
    public function show($id)
    {
        $user   = $this->resolveUser();
        $ticket = Ticket::with(['category', 'priority', 'status', 'creator', 'assignedTo'])->find($id);

        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        if (!$this->canViewTicket($user, $ticket)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json($ticket);
    }

    // POST /api/tickets
    public function store(Request $request)
    {
        $user = $this->resolveUser();

        $validated = $request->validate([
            'Title'          => 'required|string|max:200',
            'Description'    => 'required|string',
            'CategoryNumber' => 'required|integer|exists:Category,CategoryNumber',
            'PriorityNumber' => 'required|integer|exists:Priority,PriorityNumber',
        ]);

        // Generate TicketReferenceNumber: TKT-YYYY-NNNNN
        $year    = date('Y');
        $last    = Ticket::where('TicketReferenceNumber', 'like', "TKT-{$year}-%")
                         ->orderBy('TicketNumber', 'desc')
                         ->first();
        $nextNum = $last ? ((int) explode('-', $last->TicketReferenceNumber)[2]) + 1 : 1;
        $refNum  = 'TKT-' . $year . '-' . str_pad($nextNum, 5, '0', STR_PAD_LEFT);

        $ticket = Ticket::create([
            'TicketReferenceNumber' => $refNum,
            'Title'                 => $validated['Title'],
            'Description'           => $validated['Description'],
            'CategoryNumber'        => $validated['CategoryNumber'],
            'PriorityNumber'        => $validated['PriorityNumber'],
            'StatusNumber'          => 1,
            'CreatedByUserNumber'   => $user->UserNumber,
            'IsEscalated'           => false,
            'CreatedDate'           => now()->toDateTimeString(),
        ]);

        $this->logActivity($user->UserNumber, 'TicketCreated', $ticket->TicketNumber, "Ticket {$refNum} created.");

        $adminManagerIds = User::whereHas('role', fn ($q) => $q->whereIn('RoleName', ['Admin', 'Manager']))
            ->pluck('UserNumber')
            ->all();

        $this->notifyMany(
            $adminManagerIds,
            $user,
            'ticket_created',
            'New ticket created',
            "{$user->FullName} created {$refNum}: {$ticket->Title}.",
            $ticket->TicketNumber
        );

        return response()->json(
            $ticket->load(['category', 'priority', 'status', 'creator', 'assignedTo']),
            201
        );
    }

    // PUT /api/tickets/{id}
    public function update(Request $request, $id)
    {
        $user     = $this->resolveUser();
        $roleName = $user->role->RoleName;

        $ticket = Ticket::with(['category', 'priority', 'status', 'assignedTo', 'creator'])->find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        $originalStatus   = $ticket->StatusNumber;
        $originalAssigned = $ticket->AssignedToUserNumber;
        $originalCategory = $ticket->CategoryNumber;
        $originalPriority = $ticket->PriorityNumber;

        if ($roleName === 'Employee') {
            if ($ticket->CreatedByUserNumber !== $user->UserNumber) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            $validated = $request->validate([
                'Title'       => 'sometimes|string|max:200',
                'Description' => 'sometimes|string',
            ]);
        } elseif ($roleName === 'Agent') {
            if ($ticket->AssignedToUserNumber !== $user->UserNumber) {
                return response()->json(['message' => 'Forbidden.'], 403);
            }
            $validated = $request->validate([
                'Title'                => 'sometimes|string|max:200',
                'Description'          => 'sometimes|string',
                'CategoryNumber'       => 'sometimes|integer|exists:Category,CategoryNumber',
                'PriorityNumber'       => 'sometimes|integer|exists:Priority,PriorityNumber',
                'StatusNumber'         => 'sometimes|integer|exists:Status,StatusNumber',
            ]);
        } else {
            // Manager or Admin
            $validated = $request->validate([
                'Title'                => 'sometimes|string|max:200',
                'Description'          => 'sometimes|string',
                'CategoryNumber'       => 'sometimes|integer|exists:Category,CategoryNumber',
                'PriorityNumber'       => 'sometimes|integer|exists:Priority,PriorityNumber',
                'StatusNumber'         => 'sometimes|integer|exists:Status,StatusNumber',
                'AssignedToUserNumber' => 'sometimes|nullable|integer|exists:User,UserNumber',
                'IsEscalated'          => 'sometimes|boolean',
                'ResolutionNotes'      => 'sometimes|nullable|string',
            ]);
        }

        // Track status change
        if (isset($validated['StatusNumber']) && (int)$validated['StatusNumber'] !== (int)$originalStatus) {
            TicketStatusHistory::create([
                'TicketNumber'         => $ticket->TicketNumber,
                'PreviousStatusNumber' => $originalStatus,
                'NewStatusNumber'      => $validated['StatusNumber'],
                'ChangedByUserNumber'  => $user->UserNumber,
                'CreatedDate'          => now()->toDateTimeString(),
            ]);

            $oldStatus = $ticket->status?->StatusName ?? 'Unspecified';
            $newStatus = Status::find($validated['StatusNumber'])?->StatusName ?? 'Unspecified';

            $this->logActivity(
                $user->UserNumber,
                'StatusChanged',
                $ticket->TicketNumber,
                "Status changed from {$oldStatus} to {$newStatus}.",
                $oldStatus,
                $newStatus
            );

            $notificationType = in_array($newStatus, ['Resolved', 'Closed'], true)
                ? 'ticket_resolved'
                : 'ticket_status_updated';

            if ($roleName === 'Employee') {
                $this->notifyUser(
                    $ticket->AssignedToUserNumber,
                    $user,
                    $notificationType,
                    'Ticket status updated',
                    "{$user->FullName} changed {$ticket->TicketReferenceNumber} to {$newStatus}.",
                    $ticket->TicketNumber
                );
            } else {
                $this->notifyUser(
                    $ticket->CreatedByUserNumber,
                    $user,
                    $notificationType,
                    'Ticket status updated',
                    "{$ticket->TicketReferenceNumber} status is now {$newStatus}.",
                    $ticket->TicketNumber
                );
            }

            // Set ResolvedDate when ticket is marked Resolved (StatusNumber = 4)
            if ((int)$validated['StatusNumber'] === 4) {
                $validated['ResolvedDate'] = now()->toDateTimeString();
            }
        }

        // Track assignment change
        if (isset($validated['AssignedToUserNumber']) && $validated['AssignedToUserNumber'] !== $originalAssigned) {
            TicketAssignmentHistory::create([
                'TicketNumber'         => $ticket->TicketNumber,
                'PreviousAssignedToUserNumber' => $originalAssigned,
                'AssignedToUserNumber' => $validated['AssignedToUserNumber'],
                'AssignedByUserNumber' => $user->UserNumber,
                'CreatedDate'          => now()->toDateTimeString(),
            ]);

            $oldAssignee = $ticket->assignedTo?->FullName ?? 'Unassigned';
            $newAssignee = $validated['AssignedToUserNumber']
                ? (User::find($validated['AssignedToUserNumber'])?->FullName ?? 'Unassigned')
                : 'Unassigned';

            $this->logActivity(
                $user->UserNumber,
                $originalAssigned ? 'TicketReassigned' : 'TicketAssigned',
                $ticket->TicketNumber,
                "Assignment changed from {$oldAssignee} to {$newAssignee}.",
                $oldAssignee,
                $newAssignee
            );

            $this->notifyUser(
                $validated['AssignedToUserNumber'],
                $user,
                'ticket_assigned',
                'Ticket assigned',
                "{$ticket->TicketReferenceNumber} was assigned to you.",
                $ticket->TicketNumber
            );
        }

        if (isset($validated['CategoryNumber']) && (int) $validated['CategoryNumber'] !== (int) $originalCategory) {
            $oldCategory = $ticket->category?->CategoryName ?? 'Unspecified';
            $newCategory = Category::find($validated['CategoryNumber'])?->CategoryName ?? 'Unspecified';
            $this->logActivity(
                $user->UserNumber,
                'CategoryChanged',
                $ticket->TicketNumber,
                "Category changed from {$oldCategory} to {$newCategory}.",
                $oldCategory,
                $newCategory
            );
        }

        if (isset($validated['PriorityNumber']) && (int) $validated['PriorityNumber'] !== (int) $originalPriority) {
            $oldPriority = $ticket->priority?->PriorityName ?? 'Unspecified';
            $newPriority = Priority::find($validated['PriorityNumber'])?->PriorityName ?? 'Unspecified';
            $this->logActivity(
                $user->UserNumber,
                'PriorityChanged',
                $ticket->TicketNumber,
                "Priority changed from {$oldPriority} to {$newPriority}.",
                $oldPriority,
                $newPriority
            );
            $this->notifyMany(
                [$ticket->CreatedByUserNumber, $ticket->AssignedToUserNumber],
                $user,
                'ticket_priority_updated',
                'Ticket priority updated',
                "{$ticket->TicketReferenceNumber} priority is now {$newPriority}.",
                $ticket->TicketNumber
            );
        }

        foreach ([
            'Title' => 'Title updated',
            'Description' => 'Description updated',
            'IsEscalated' => 'Escalation flag updated',
            'ResolutionNotes' => 'Resolution notes updated',
        ] as $field => $message) {
            if (array_key_exists($field, $validated) && (string) $ticket->{$field} !== (string) $validated[$field]) {
                $this->logActivity(
                    $user->UserNumber,
                    'TicketUpdated',
                    $ticket->TicketNumber,
                    $message,
                    $field === 'Description' || $field === 'ResolutionNotes' ? null : (string) $ticket->{$field},
                    $field === 'Description' || $field === 'ResolutionNotes' ? null : (string) $validated[$field]
                );
            }
        }

        $ticket->fill($validated)->save();

        return response()->json(
            $ticket->load(['category', 'priority', 'status', 'creator', 'assignedTo'])
        );
    }

    // DELETE /api/tickets/{id}
    public function destroy($id)
    {
        $user = $this->resolveUser();

        if ($user->role->RoleName !== 'Admin') {
            return response()->json(['message' => 'Forbidden. Admin access required.'], 403);
        }

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        $ticketNumber = $ticket->TicketNumber;
        $ticketRef    = $ticket->TicketReferenceNumber;

        $ticket->delete();

        $this->logActivity($user->UserNumber, 'TicketDeleted', $ticketNumber, "Ticket {$ticketRef} deleted.");

        return response()->json(null, 204);
    }
}
