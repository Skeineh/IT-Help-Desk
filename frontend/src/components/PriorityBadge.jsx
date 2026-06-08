const STYLES = {
  Low:      'bg-green-100 text-green-700 border-green-200',
  Medium:   'bg-yellow-100 text-yellow-700 border-yellow-200',
  High:     'bg-orange-100 text-orange-700 border-orange-200',
  Critical: 'bg-red-100 text-red-700 border-red-200',
};

export default function PriorityBadge({ priority }) {
  const cls = STYLES[priority] ?? 'bg-gray-100 text-gray-500 border-gray-200';
  return (
    <span className={`inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full border ${cls}`}>
      {priority ?? '—'}
    </span>
  );
}
