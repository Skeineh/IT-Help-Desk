<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Ticket;
use App\Models\TicketAssignmentHistory;
use App\Models\TicketStatusHistory;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class TicketController extends Controller
{
    private function resolveUser()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $user->loadMissing('role');
        return $user;
    }

    private function logActivity(int $userNumber, string $actionType, int $ticketNumber, string $desc = null): void
    {
        ActivityLog::create([
            'UserNumber'            => $userNumber,
            'ActionType'            => $actionType,
            'EntityType'            => 'Ticket',
            'EntityReferenceNumber' => $ticketNumber,
            'ActionDescription'     => $desc,
            'IpAddress'             => request()->ip(),
            'CreatedDate'           => now()->toDateTimeString(),
        ]);
    }

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

        if ($user->role->RoleName === 'Employee' && $ticket->CreatedByUserNumber !== $user->UserNumber) {
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

        $ticket = Ticket::find($id);
        if (!$ticket) {
            return response()->json(['message' => 'Ticket not found.'], 404);
        }

        $originalStatus   = $ticket->StatusNumber;
        $originalAssigned = $ticket->AssignedToUserNumber;

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
                'AssignedToUserNumber' => 'sometimes|nullable|integer|exists:User,UserNumber',
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
            // Set ResolvedDate when ticket is marked Resolved (StatusNumber = 4)
            if ((int)$validated['StatusNumber'] === 4) {
                $validated['ResolvedDate'] = now()->toDateTimeString();
            }
        }

        // Track assignment change
        if (isset($validated['AssignedToUserNumber']) && $validated['AssignedToUserNumber'] !== $originalAssigned) {
            TicketAssignmentHistory::create([
                'TicketNumber'         => $ticket->TicketNumber,
                'AssignedToUserNumber' => $validated['AssignedToUserNumber'],
                'AssignedByUserNumber' => $user->UserNumber,
                'CreatedDate'          => now()->toDateTimeString(),
            ]);
        }

        $ticket->fill($validated)->save();

        $this->logActivity($user->UserNumber, 'TicketUpdated', $ticket->TicketNumber, "Ticket {$ticket->TicketReferenceNumber} updated.");

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
