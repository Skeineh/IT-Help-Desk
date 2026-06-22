<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TicketsExport implements FromCollection, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(private Collection $tickets) {}

    public function collection(): Collection
    {
        return $this->tickets->map(fn ($t) => [
            $t->TicketReferenceNumber,
            $t->Title,
            $t->category?->CategoryName ?? '—',
            $t->priority?->PriorityName ?? '—',
            $t->status?->StatusName ?? '—',
            $t->creator?->FullName ?? '—',
            $t->assignedTo?->FullName ?? 'Unassigned',
            $t->IsEscalated ? 'Yes' : 'No',
            $t->CreatedDate ?? '—',
            $t->ResolvedDate ?? '—',
        ]);
    }

    public function headings(): array
    {
        return [
            'Reference #',
            'Title',
            'Category',
            'Priority',
            'Status',
            'Created By',
            'Assigned To',
            'Escalated',
            'Created Date',
            'Resolved Date',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
