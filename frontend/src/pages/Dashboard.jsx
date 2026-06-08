import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { Ticket, Clock, CheckCircle, AlertCircle, ArrowRight } from 'lucide-react';
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
      <p className={`text-3xl font-bold ${color}`}>{value ?? '—'}</p>
    </div>
  );
}

function formatDate(str) {
  if (!str) return '—';
  return new Date(str).toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: 'numeric', minute: '2-digit',
  });
}

export default function Dashboard() {
  const { user } = useAuth();

  const [stats,         setStats]         = useState(null);
  const [recentTickets, setRecentTickets] = useState([]);
  const [loading,       setLoading]       = useState(true);

  useEffect(() => {
    setLoading(true);
    Promise.all([
      svc.listTickets({ page: 1 }),
      svc.listTickets({ status: 1, page: 1 }),
      svc.listTickets({ status: 2, page: 1 }),
      svc.listTickets({ status: 4, page: 1 }),
    ])
      .then(([allRes, openRes, progressRes, resolvedRes]) => {
        setStats({
          total:      allRes.data.total,
          open:       openRes.data.total,
          inProgress: progressRes.data.total,
          resolved:   resolvedRes.data.total,
        });
        setRecentTickets(allRes.data.data.slice(0, 5));
      })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, []);

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {/* Welcome banner */}
        <div className="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-7 text-white shadow-xl shadow-blue-900/40 mb-8">
          <p className="text-blue-200 text-sm font-medium mb-1">Welcome back</p>
          <h1 className="text-2xl font-bold mb-1">{user?.FullName}</h1>
          <p className="text-blue-200 text-sm">
            {user?.RoleName}{user?.Department ? ` · ${user.Department}` : ''}
          </p>
        </div>

        {/* Stat cards */}
        <div className="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
          <StatCard icon={Ticket}       label="Total Tickets"  value={loading ? '…' : stats?.total}      color="text-blue-600"   bg="bg-blue-50" />
          <StatCard icon={AlertCircle}  label="Open"           value={loading ? '…' : stats?.open}       color="text-amber-600"  bg="bg-amber-50" />
          <StatCard icon={Clock}        label="In Progress"    value={loading ? '…' : stats?.inProgress} color="text-indigo-600" bg="bg-indigo-50" />
          <StatCard icon={CheckCircle}  label="Resolved"       value={loading ? '…' : stats?.resolved}   color="text-green-600"  bg="bg-green-50" />
        </div>

        {/* Recent tickets */}
        <div className="bg-white rounded-2xl shadow-xl overflow-hidden">
          <div className="flex items-center justify-between px-6 py-4 border-b border-gray-100">
            <h2 className="text-base font-bold text-gray-900">Recent Tickets</h2>
            <Link
              to="/tickets"
              className="flex items-center gap-1 text-sm text-blue-600 hover:text-blue-700 font-medium transition-colors"
            >
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
                    {['Reference', 'Title', 'Priority', 'Status', 'Date'].map(h => (
                      <th key={h} className="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-5 py-3 whitespace-nowrap">{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-50">
                  {recentTickets.map(t => (
                    <tr key={t.TicketNumber} className="hover:bg-gray-50 transition-colors">
                      <td className="px-5 py-3 font-mono text-xs text-blue-600 whitespace-nowrap">
                        <Link to={`/tickets/${t.TicketNumber}`} className="hover:underline">
                          {t.TicketReferenceNumber}
                        </Link>
                      </td>
                      <td className="px-5 py-3 text-gray-800 font-medium max-w-xs">
                        <span className="line-clamp-1">{t.Title}</span>
                      </td>
                      <td className="px-5 py-3 whitespace-nowrap">
                        <PriorityBadge priority={t.priority?.PriorityName} />
                      </td>
                      <td className="px-5 py-3 whitespace-nowrap">
                        <StatusBadge status={t.status?.StatusName} />
                      </td>
                      <td className="px-5 py-3 text-gray-400 text-xs whitespace-nowrap">
                        {formatDate(t.CreatedDate)}
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
