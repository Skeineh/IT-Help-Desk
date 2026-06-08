const STYLES = {
  Open:       'bg-blue-100 text-blue-700 border-blue-200',
  InProgress: 'bg-amber-100 text-amber-700 border-amber-200',
  Pending:    'bg-orange-100 text-orange-700 border-orange-200',
  Resolved:   'bg-green-100 text-green-700 border-green-200',
  Closed:     'bg-gray-100 text-gray-500 border-gray-200',
};

export default function StatusBadge({ status }) {
  const cls = STYLES[status] ?? 'bg-gray-100 text-gray-500 border-gray-200';
  return (
    <span className={`inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full border ${cls}`}>
      {status ?? '—'}
    </span>
  );
}
