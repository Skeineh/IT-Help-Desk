<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTickets;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use AuthorizesTickets;

    public function stats()
    {
        $user = $this->resolveUser();
        $base = $this->scopedTicketQuery($user);

        $statusRows = (clone $base)
            ->join('Status', 'Ticket.StatusNumber', '=', 'Status.StatusNumber')
            ->select('Status.StatusName as label', DB::raw('COUNT(*) as value'))
            ->groupBy('Status.StatusName')
            ->orderBy('Status.StatusName')
            ->get();

        $statusCounts = $statusRows->pluck('value', 'label');

        $categoryRows = (clone $base)
            ->join('Category', 'Ticket.CategoryNumber', '=', 'Category.CategoryNumber')
            ->select('Category.CategoryName as label', DB::raw('COUNT(*) as value'))
            ->groupBy('Category.CategoryName')
            ->orderBy('Category.CategoryName')
            ->get();

        $priorityRows = (clone $base)
            ->join('Priority', 'Ticket.PriorityNumber', '=', 'Priority.PriorityNumber')
            ->select('Priority.PriorityName as label', DB::raw('COUNT(*) as value'), 'Priority.PriorityLevel')
            ->groupBy('Priority.PriorityName', 'Priority.PriorityLevel')
            ->orderBy('Priority.PriorityLevel')
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->value]);

        $agentRows = (clone $base)
            ->join('User', 'Ticket.AssignedToUserNumber', '=', 'User.UserNumber')
            ->select('User.FullName as label', DB::raw('COUNT(*) as value'))
            ->whereNotNull('Ticket.AssignedToUserNumber')
            ->groupBy('User.FullName')
            ->orderByDesc('value')
            ->limit(10)
            ->get()
            ->map(fn ($row) => ['label' => $row->label, 'value' => (int) $row->value]);

        $total = (clone $base)->count();
        $resolved = (int) ($statusCounts['Resolved'] ?? 0) + (int) ($statusCounts['Closed'] ?? 0);

        $recentTickets = (clone $base)
            ->with(['category', 'priority', 'status', 'creator', 'assignedTo'])
            ->orderBy('CreatedDate', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'total_tickets'       => $total,
            'open_tickets'        => (int) ($statusCounts['Open'] ?? 0),
            'in_progress_tickets' => (int) ($statusCounts['InProgress'] ?? 0),
            'pending_tickets'     => (int) ($statusCounts['Pending'] ?? 0),
            'resolved_tickets'    => (int) ($statusCounts['Resolved'] ?? 0),
            'closed_tickets'      => (int) ($statusCounts['Closed'] ?? 0),
            'resolution_rate'     => $total > 0 ? round($resolved / $total * 100) : 0,
            'tickets_by_status'   => $statusRows->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])->values(),
            'tickets_by_category' => $categoryRows->map(fn ($row) => [
                'label' => $row->label,
                'value' => (int) $row->value,
            ])->values(),
            'tickets_by_priority' => $priorityRows->values(),
            'tickets_by_agent'    => $agentRows->values(),
            'recent_tickets'      => $recentTickets,
        ]);
    }

    private function scopedTicketQuery($user)
    {
        $query = Ticket::query();

        if ($this->roleName($user) === 'Employee') {
            $query->where('CreatedByUserNumber', $user->UserNumber);
        }

        if ($this->roleName($user) === 'Agent') {
            $query->where('AssignedToUserNumber', $user->UserNumber);
        }

        return $query;
    }
}
