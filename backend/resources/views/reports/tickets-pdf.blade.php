<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
  .header { background: #1e40af; color: white; padding: 14px 20px; margin-bottom: 16px; }
  .header h1 { font-size: 18px; font-weight: bold; }
  .header p { font-size: 9px; opacity: 0.85; margin-top: 3px; }
  .meta { padding: 0 20px 10px; color: #475569; font-size: 9px; }
  table { width: calc(100% - 40px); margin: 0 20px; border-collapse: collapse; }
  thead tr { background: #1e3a5f; color: white; }
  thead th { padding: 7px 8px; text-align: left; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
  tbody tr:nth-child(even) { background: #f1f5f9; }
  tbody tr:nth-child(odd)  { background: #ffffff; }
  tbody td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; font-size: 9px; vertical-align: top; }
  .badge { display: inline-block; padding: 2px 6px; border-radius: 9999px; font-size: 8px; font-weight: bold; }
  .badge-open        { background:#dbeafe; color:#1d4ed8; }
  .badge-inprogress  { background:#fef9c3; color:#854d0e; }
  .badge-pending     { background:#f3e8ff; color:#6b21a8; }
  .badge-resolved    { background:#dcfce7; color:#166534; }
  .badge-closed      { background:#f1f5f9; color:#475569; }
  .badge-critical    { background:#fee2e2; color:#991b1b; }
  .badge-high        { background:#fed7aa; color:#9a3412; }
  .badge-medium      { background:#fef9c3; color:#854d0e; }
  .badge-low         { background:#dcfce7; color:#166534; }
  .escalated         { color: #dc2626; font-weight: bold; }
  .footer { margin-top: 14px; padding: 8px 20px; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 8px; text-align: right; }
  .title-cell { max-width: 160px; word-break: break-word; }
</style>
</head>
<body>

<div class="header">
  <h1>IT Help Desk — Ticket Report</h1>
  <p>Generated: {{ $generatedAt }}</p>
</div>

<div class="meta">
  <strong>Filters:</strong> {{ $filters }} &nbsp;|&nbsp; <strong>Total:</strong> {{ count($tickets) }} ticket(s)
</div>

<table>
  <thead>
    <tr>
      <th>Reference</th>
      <th>Title</th>
      <th>Category</th>
      <th>Priority</th>
      <th>Status</th>
      <th>Created By</th>
      <th>Assigned To</th>
      <th>Escalated</th>
      <th>Created Date</th>
      <th>Resolved Date</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($tickets as $ticket)
      @php
        $statusKey = strtolower(str_replace(' ', '', $ticket->status?->StatusName ?? ''));
        $priKey    = strtolower($ticket->priority?->PriorityName ?? '');
      @endphp
      <tr>
        <td><strong>{{ $ticket->TicketReferenceNumber }}</strong></td>
        <td class="title-cell">{{ $ticket->Title }}</td>
        <td>{{ $ticket->category?->CategoryName ?? '—' }}</td>
        <td><span class="badge badge-{{ $priKey }}">{{ $ticket->priority?->PriorityName ?? '—' }}</span></td>
        <td><span class="badge badge-{{ $statusKey }}">{{ $ticket->status?->StatusName ?? '—' }}</span></td>
        <td>{{ $ticket->creator?->FullName ?? '—' }}</td>
        <td>{{ $ticket->assignedTo?->FullName ?? 'Unassigned' }}</td>
        <td>@if($ticket->IsEscalated)<span class="escalated">YES</span>@else No @endif</td>
        <td>{{ $ticket->CreatedDate ? \Carbon\Carbon::parse($ticket->CreatedDate)->format('Y-m-d H:i') : '—' }}</td>
        <td>{{ $ticket->ResolvedDate ? \Carbon\Carbon::parse($ticket->ResolvedDate)->format('Y-m-d H:i') : '—' }}</td>
      </tr>
    @empty
      <tr><td colspan="10" style="text-align:center; padding:20px; color:#94a3b8;">No tickets found for the selected filters.</td></tr>
    @endforelse
  </tbody>
</table>

<div class="footer">IT HelpDesk System &mdash; Confidential Report</div>

</body>
</html>
