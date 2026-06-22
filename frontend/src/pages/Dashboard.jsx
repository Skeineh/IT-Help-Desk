import { useEffect, useMemo, useState } from 'react';
import { Link } from 'react-router-dom';
import { AlertCircle, ArrowRight, CheckCircle, Clock, Inbox, Ticket } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import NavBar from '../components/NavBar';
import StatusBadge from '../components/StatusBadge';
import PriorityBadge from '../components/PriorityBadge';
import * as svc from '../services/ticketService';

const PAGE_BG = { backgroundColor: '#0A1929' };

function StatCard({ icon: Icon, label, value, color, bg }) {
  return (
    <div className="bg-white rounded-xl border border-gray-200 p-6 shadow-sm">
      <div className="flex items-center justify-between mb-3">
        <p className="text-sm font-medium text-gray-500">{label}</p>
        <div className={`${bg} p-2 rounded-lg`}>
          <Icon className={`w-5 h-5 ${color}`} />
        </div>
      </div>
      <p className={`text-3xl font-bold ${color}`}>{value ?? '-'}</p>
    </div>
  );
}

function formatDate(str) {
  if (!str) return '-';
  return new Date(str).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: 'numeric',
    minute: '2-digit',
  });
}

function MiniChart({ title, data, accent = 'bg-blue-600' }) {
  const max = useMemo(() => Math.max(1, ...data.map((item) => item.value || 0)), [data]);

  return (
    <div className="bg-white rounded-2xl shadow-xl p-6">
      <h2 className="text-base font-bold text-gray-900 mb-4">{title}</h2>
      {data.length === 0 ? (
        <div className="py-10 text-center text-sm text-gray-400">No data yet.</div>
      ) : (
        <div className="space-y-3">
          {data.map((item) => (
            <div key={item.label}>
              <div className="flex items-center justify-between text-xs mb-1">
                <span className="font-semibold text-gray-600">{item.label}</span>
                <span className="text-gray-400">{item.value}</span>
              </div>
              <div className="h-2.5 bg-gray-100 rounded-full overflow-hidden">
                <div
                  className={`h-full ${accent} rounded-full`}
                  style={{ width: `${Math.max(8, (item.value / max) * 100)}%` }}
                />
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  );
}

export default function Dashboard() {
  const { user } = useAuth();
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    setLoading(true);
    setError('');
    svc.getDashboardStats()
      .then(({ data }) => setStats(data))
      .catch(() => setError('Failed to load dashboard analytics.'))
      .finally(() => setLoading(false));
  }, []);

  const recentTickets = stats?.recent_tickets ?? [];

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-7 text-white shadow-xl shadow-blue-900/40 mb-8">
          <p className="text-blue-200 text-sm font-medium mb-1">Welcome back</p>
          <h1 className="text-2xl font-bold mb-1">{user?.FullName}</h1>
          <p className="text-blue-200 text-sm">
            {user?.RoleName}{user?.Department ? ` - ${user.Department}` : ''}
          </p>
        </div>

        {error && (
          <div className="flex items-center gap-2 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 mb-6 text-sm">
            <AlertCircle className="w-4 h-4" />
            {error}
          </div>
        )}

        <div className="grid grid-cols-2 lg:grid-cols-6 gap-4 mb-8">
          <StatCard icon={Ticket} label="Total" value={loading ? '...' : stats?.total_tickets} color="text-blue-600" bg="bg-blue-50" />
          <StatCard icon={Inbox} label="Open" value={loading ? '...' : stats?.open_tickets} color="text-amber-600" bg="bg-amber-50" />
          <StatCard icon={Clock} label="In Progress" value={loading ? '...' : stats?.in_progress_tickets} color="text-indigo-600" bg="bg-indigo-50" />
          <StatCard icon={AlertCircle} label="Pending" value={loading ? '...' : stats?.pending_tickets} color="text-orange-600" bg="bg-orange-50" />
          <StatCard icon={CheckCircle} label="Resolved" value={loading ? '...' : stats?.resolved_tickets} color="text-green-600" bg="bg-green-50" />
          <StatCard icon={CheckCircle} label="Closed" value={loading ? '...' : stats?.closed_tickets} color="text-gray-600" bg="bg-gray-100" />
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
          <MiniChart title="Tickets by Status" data={stats?.tickets_by_status ?? []} accent="bg-blue-600" />
          <MiniChart title="Tickets by Category" data={stats?.tickets_by_category ?? []} accent="bg-emerald-600" />
          <MiniChart title="Tickets by Priority" data={stats?.tickets_by_priority ?? []} accent="bg-rose-600" />
        </div>

        <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
          <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 className="text-base font-bold text-gray-900">Recent Tickets</h2>
            <Link to="/tickets" className="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors">
              View all <ArrowRight className="w-4 h-4" />
            </Link>
          </div>

          {loading ? (
            <div className="flex items-center justify-center py-16">
              <div className="w-7 h-7 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
            </div>
          ) : recentTickets.length === 0 ? (
            <div className="text-center py-16 text-gray-400 text-sm">No tickets yet.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="bg-gray-50 border-b border-gray-100">
                    {['Reference', 'Title', 'Priority', 'Status', 'Date'].map((heading) => (
                      <th key={heading} className="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3 whitespace-nowrap">{heading}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {recentTickets.map((ticket) => (
                    <tr key={ticket.TicketNumber} className="hover:bg-gray-50 transition-colors">
                      <td className="px-5 py-3 font-mono text-xs text-blue-600 whitespace-nowrap">
                        <Link to={`/tickets/${ticket.TicketNumber}`} className="hover:underline">
                          {ticket.TicketReferenceNumber}
                        </Link>
                      </td>
                      <td className="px-5 py-3 text-gray-800 font-medium max-w-xs">
                        <span className="line-clamp-1">{ticket.Title}</span>
                      </td>
                      <td className="px-5 py-3 whitespace-nowrap">
                        <PriorityBadge priority={ticket.priority?.PriorityName} />
                      </td>
                      <td className="px-5 py-3 whitespace-nowrap">
                        <StatusBadge status={ticket.status?.StatusName} />
                      </td>
                      <td className="px-5 py-3 text-gray-400 text-xs whitespace-nowrap">
                        {formatDate(ticket.CreatedDate)}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </main>
    </div>
  );
}
