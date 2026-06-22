<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\AuthorizesTickets;
use App\Exports\TicketsExport;
use App\Models\Ticket;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    use AuthorizesTickets;

    // GET /api/reports/tickets/pdf
    public function exportPdf(Request $request)
    {
        $tickets = $this->filteredTickets($request);

        $pdf = Pdf::loadView('reports.tickets-pdf', [
            'tickets'     => $tickets,
            'filters'     => $this->describeFilters($request),
            'generatedAt' => now()->format('Y-m-d H:i:s'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('tickets-report-' . now()->format('Ymd-His') . '.pdf');
    }

    // GET /api/reports/tickets/excel
    public function exportExcel(Request $request)
    {
        $tickets = $this->filteredTickets($request);
        $filename = 'tickets-report-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(new TicketsExport($tickets), $filename);
    }

    private function filteredTickets(Request $request)
    {
        $user     = $this->resolveUser();
        $roleName = $user->role->RoleName;

        $query = Ticket::with(['category', 'priority', 'status', 'creator', 'assignedTo']);

        if ($roleName === 'Employee') {
            $query->where('CreatedByUserNumber', $user->UserNumber);
        }

        if ($roleName === 'Agent') {
            $query->where('AssignedToUserNumber', $user->UserNumber);
        }

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

        return $query->orderBy('CreatedDate', 'desc')->get();
    }

    private function describeFilters(Request $request): string
    {
        $parts = [];
        if ($request->search)   { $parts[] = "Search: \"{$request->search}\""; }
        if ($request->status)   { $parts[] = "Status ID: {$request->status}"; }
        if ($request->category) { $parts[] = "Category ID: {$request->category}"; }
        if ($request->priority) { $parts[] = "Priority ID: {$request->priority}"; }
        return $parts ? implode(' | ', $parts) : 'All tickets';
    }
}
