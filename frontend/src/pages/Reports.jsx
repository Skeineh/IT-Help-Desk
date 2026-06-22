import { useEffect, useMemo, useState } from 'react';
import { AlertCircle, CheckCircle, Clock, FileSpreadsheet, FileText, Inbox, Ticket, TrendingUp, Users } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import NavBar from '../components/NavBar';
import { getDashboardStats, exportTicketsPdf, exportTicketsExcel } from '../services/ticketService';

const PAGE_BG = { backgroundColor: '#0A1929' };

function StatCard({ icon: Icon, label, value, color, bg, suffix = '' }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200 p-5 shadow-sm">
      <div className="flex items-center justify-between mb-3">
        <p className="text-xs font-semibold text-gray-500 uppercase tracking-wide">{label}</p>
        <div className={`${bg} p-2 rounded-lg`}>
          <Icon className={`w-4 h-4 ${color}`} />
        </div>
      </div>
      <p className={`text-3xl font-bold ${color}`}>{value ?? '-'}{suffix}</p>
    </div>
  );
}

function BarChart({ title, data, colorClass }) {
  const max = useMemo(() => Math.max(1, ...data.map(d => d.value || 0)), [data]);
  const total = useMemo(() => data.reduce((s, d) => s + (d.value || 0), 0), [data]);

  return (
    <div className="bg-white rounded-2xl shadow-xl p-6 flex flex-col">
      <div className="flex items-center justify-between mb-5">
        <h2 className="text-sm font-bold text-gray-900">{title}</h2>
        <span className="text-xs text-gray-400">{total} total</span>
      </div>
      {data.length === 0 ? (
        <div className="flex-1 flex items-center justify-center text-sm text-gray-400 py-8">No data yet.</div>
      ) : (
        <div className="space-y-3">
          {data.map(item => {
            const pct = Math.round((item.value / total) * 100);
            return (
              <div key={item.label}>
                <div className="flex items-center justify-between text-xs mb-1.5">
                  <span className="font-medium text-gray-700 truncate max-w-[60%]">{item.label}</span>
                  <span className="text-gray-400 ml-2 shrink-0">{item.value} <span className="text-gray-300">({pct}%)</span></span>
                </div>
                <div className="h-3 bg-gray-100 rounded-full overflow-hidden">
                  <div
                    className={`h-full ${colorClass} rounded-full transition-all duration-500`}
                    style={{ width: `${Math.max(4, (item.value / max) * 100)}%` }}
                  />
                </div>
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}

export default function Reports() {
  const { user } = useAuth();
  const [stats,     setStats]     = useState(null);
  const [loading,   setLoading]   = useState(true);
  const [error,     setError]     = useState('');
  const [exporting, setExporting] = useState('');

  useEffect(() => {
    setLoading(true);
    getDashboardStats()
      .then(({ data }) => setStats(data))
      .catch(() => setError('Failed to load report data.'))
      .finally(() => setLoading(false));
  }, []);

  const isAdminOrManager = ['Admin', 'Manager'].includes(user?.RoleName);

  const handleExport = async (type) => {
    setExporting(type);
    try {
      const fn   = type === 'pdf' ? exportTicketsPdf : exportTicketsExcel;
      const ext  = type === 'pdf' ? 'pdf' : 'xlsx';
      const mime = type === 'pdf'
        ? 'application/pdf'
        : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      const { data } = await fn({});
      const url = URL.createObjectURL(new Blob([data], { type: mime }));
      const a = document.createElement('a');
      a.href = url;
      a.download = `tickets-report-${new Date().toISOString().slice(0, 10)}.${ext}`;
      a.click();
      URL.revokeObjectURL(url);
    } catch {
      setError('Export failed. Please try again.');
    } finally {
      setExporting('');
    }
  };

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {/* Header */}
        <div className="flex items-center justify-between mb-8">
          <div>
            <h1 className="text-2xl font-bold text-white">Reports</h1>
            <p className="text-slate-400 text-sm mt-1">
              {isAdminOrManager ? 'System-wide ticket analytics' : 'Your ticket analytics'}
            </p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => handleExport('pdf')}
              disabled={!!exporting}
              className="flex items-center gap-2 bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-sm font-semibold px-3 py-2 rounded-lg transition-colors"
            >
              {exporting === 'pdf'
                ? <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                : <FileText className="w-4 h-4" />}
              PDF
            </button>
            <button
              onClick={() => handleExport('excel')}
              disabled={!!exporting}
              className="flex items-center gap-2 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white text-sm font-semibold px-3 py-2 rounded-lg transition-colors"
            >
              {exporting === 'excel'
                ? <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                : <FileSpreadsheet className="w-4 h-4" />}
              Excel
            </button>
          </div>
        </div>

        {error && (
          <div className="flex items-center gap-2 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 mb-6 text-sm">
            <AlertCircle className="w-4 h-4" />
            {error}
          </div>
        )}

        {/* Summary stat cards */}
        <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
          <StatCard icon={Ticket}       label="Total"       value={loading ? '…' : stats?.total_tickets}       color="text-blue-600"   bg="bg-blue-50" />
          <StatCard icon={Inbox}        label="Open"        value={loading ? '…' : stats?.open_tickets}        color="text-amber-600"  bg="bg-amber-50" />
          <StatCard icon={Clock}        label="In Progress" value={loading ? '…' : stats?.in_progress_tickets} color="text-indigo-600" bg="bg-indigo-50" />
          <StatCard icon={AlertCircle}  label="Pending"     value={loading ? '…' : stats?.pending_tickets}     color="text-orange-600" bg="bg-orange-50" />
          <StatCard icon={CheckCircle}  label="Resolved"    value={loading ? '…' : stats?.resolved_tickets}    color="text-green-600"  bg="bg-green-50" />
          <StatCard icon={TrendingUp}   label="Resolution%" value={loading ? '…' : stats?.resolution_rate}     color="text-teal-600"   bg="bg-teal-50"  suffix="%" />
        </div>

        {/* Charts row 1 */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
          <BarChart
            title="Tickets by Status"
            data={stats?.tickets_by_status ?? []}
            colorClass="bg-blue-500"
          />
          <BarChart
            title="Tickets by Category"
            data={stats?.tickets_by_category ?? []}
            colorClass="bg-emerald-500"
          />
          <BarChart
            title="Tickets by Priority"
            data={stats?.tickets_by_priority ?? []}
            colorClass="bg-rose-500"
          />
        </div>

        {/* Agent chart — full width */}
        {isAdminOrManager && (
          <div className="mb-6">
            <BarChart
              title="Tickets by Agent (top 10)"
              data={stats?.tickets_by_agent ?? []}
              colorClass="bg-violet-500"
            />
          </div>
        )}

        {!loading && stats && (
          <p className="text-center text-slate-500 text-xs mt-8">
            Showing data for {isAdminOrManager ? 'all tickets' : 'your tickets'} — refreshed on page load
          </p>
        )}
      </main>
    </div>
  );
}
