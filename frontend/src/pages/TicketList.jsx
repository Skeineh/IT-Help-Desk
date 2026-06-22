import { useState, useEffect } from 'react';
import { useNavigate, Link } from 'react-router-dom';
import { Plus, Search, Eye, Pencil, Trash2, ChevronLeft, ChevronRight, AlertCircle, FileText, FileSpreadsheet } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import NavBar from '../components/NavBar';
import StatusBadge from '../components/StatusBadge';
import PriorityBadge from '../components/PriorityBadge';
import * as svc from '../services/ticketService';

const PAGE_BG = { backgroundColor: '#0A1929' };

function formatDate(str) {
  if (!str) return '—';
  return new Date(str).toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: 'numeric', minute: '2-digit',
  });
}

export default function TicketList() {
  const { user }   = useAuth();
  const navigate   = useNavigate();

  const [tickets,       setTickets]       = useState([]);
  const [meta,          setMeta]          = useState({ current_page: 1, last_page: 1, total: 0 });
  const [page,          setPage]          = useState(1);
  const [searchInput,   setSearchInput]   = useState('');
  const [activeSearch,  setActiveSearch]  = useState('');
  const [statusFilter,  setStatusFilter]  = useState('');
  const [catFilter,     setCatFilter]     = useState('');
  const [priFilter,     setPriFilter]     = useState('');
  const [categories,    setCategories]    = useState([]);
  const [statuses,      setStatuses]      = useState([]);
  const [priorities,    setPriorities]    = useState([]);
  const [loading,       setLoading]       = useState(true);
  const [error,         setError]         = useState('');
  const [deleteConfirm, setDeleteConfirm] = useState(null);
  const [refreshKey,    setRefreshKey]    = useState(0);
  const [exporting,     setExporting]     = useState('');

  const isAdmin    = user?.RoleName === 'Admin';
  const isEmployee = user?.RoleName === 'Employee';

  // Load lookup data once
  useEffect(() => {
    Promise.all([svc.getCategories(), svc.getStatuses(), svc.getPriorities()])
      .then(([c, s, p]) => {
        setCategories(c.data);
        setStatuses(s.data);
        setPriorities(p.data);
      })
      .catch(() => {});
  }, []);

  // Load tickets whenever filters / page / refresh change
  useEffect(() => {
    setLoading(true);
    setError('');
    const params = { page };
    if (activeSearch) params.search   = activeSearch;
    if (statusFilter) params.status   = statusFilter;
    if (catFilter)    params.category = catFilter;
    if (priFilter)    params.priority = priFilter;

    svc.listTickets(params)
      .then(({ data }) => {
        setTickets(data.data);
        setMeta({ current_page: data.current_page, last_page: data.last_page, total: data.total });
      })
      .catch(() => setError('Failed to load tickets. Please try again.'))
      .finally(() => setLoading(false));
  }, [page, activeSearch, statusFilter, catFilter, priFilter, refreshKey]);

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    setActiveSearch(searchInput);
    setPage(1);
  };

  const handleFilterChange = (setter) => (e) => {
    setter(e.target.value);
    setPage(1);
  };

  const activeFilters = () => {
    const p = {};
    if (activeSearch) p.search   = activeSearch;
    if (statusFilter) p.status   = statusFilter;
    if (catFilter)    p.category = catFilter;
    if (priFilter)    p.priority = priFilter;
    return p;
  };

  const handleExport = async (type) => {
    setExporting(type);
    try {
      const fn   = type === 'pdf' ? svc.exportTicketsPdf : svc.exportTicketsExcel;
      const ext  = type === 'pdf' ? 'pdf' : 'xlsx';
      const mime = type === 'pdf' ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      const { data } = await fn(activeFilters());
      const url  = URL.createObjectURL(new Blob([data], { type: mime }));
      const a    = document.createElement('a');
      a.href     = url;
      a.download = `tickets-report-${new Date().toISOString().slice(0,10)}.${ext}`;
      a.click();
      URL.revokeObjectURL(url);
    } catch {
      setError('Export failed. Please try again.');
    } finally {
      setExporting('');
    }
  };

  const handleDelete = async () => {
    try {
      await svc.deleteTicket(deleteConfirm);
      setDeleteConfirm(null);
      setRefreshKey(k => k + 1);
    } catch {
      setError('Failed to delete ticket.');
      setDeleteConfirm(null);
    }
  };

  const canEdit = (ticket) => {
    if (isAdmin || user?.RoleName === 'Manager') return true;
    if (user?.RoleName === 'Agent')    return ticket.AssignedToUserNumber === user?.UserNumber;
    if (isEmployee) return ticket.CreatedByUserNumber === user?.UserNumber;
    return false;
  };

  const selectCls = 'px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent';

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        {/* Header */}
        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-2xl font-bold text-white">Tickets</h1>
            <p className="text-slate-400 text-sm mt-0.5">{meta.total} ticket{meta.total !== 1 ? 's' : ''}</p>
          </div>
          <div className="flex items-center gap-2">
            <button
              onClick={() => handleExport('pdf')}
              disabled={!!exporting}
              title="Export PDF"
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
              title="Export Excel"
              className="flex items-center gap-2 bg-green-600 hover:bg-green-700 disabled:opacity-60 text-white text-sm font-semibold px-3 py-2 rounded-lg transition-colors"
            >
              {exporting === 'excel'
                ? <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                : <FileSpreadsheet className="w-4 h-4" />}
              Excel
            </button>
            <Link
              to="/tickets/new"
              className="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"
            >
              <Plus className="w-4 h-4" />
              New Ticket
            </Link>
          </div>
        </div>

        {/* Filters */}
        <div className="rounded-xl border border-slate-700 p-4 mb-6" style={{ backgroundColor: '#0d2137' }}>
          <form onSubmit={handleSearchSubmit} className="flex flex-wrap gap-3">
            <div className="flex-1 min-w-48 relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" />
              <input
                type="text"
                value={searchInput}
                onChange={(e) => setSearchInput(e.target.value)}
                placeholder="Search reference, title, description…"
                className="w-full pl-9 pr-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-sm text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <select value={statusFilter} onChange={handleFilterChange(setStatusFilter)} className={selectCls}>
              <option value="">All Statuses</option>
              {statuses.map(s => <option key={s.StatusNumber} value={s.StatusNumber}>{s.StatusName}</option>)}
            </select>
            <select value={catFilter} onChange={handleFilterChange(setCatFilter)} className={selectCls}>
              <option value="">All Categories</option>
              {categories.map(c => <option key={c.CategoryNumber} value={c.CategoryNumber}>{c.CategoryName}</option>)}
            </select>
            <select value={priFilter} onChange={handleFilterChange(setPriFilter)} className={selectCls}>
              <option value="">All Priorities</option>
              {priorities.map(p => <option key={p.PriorityNumber} value={p.PriorityNumber}>{p.PriorityName}</option>)}
            </select>
            <button
              type="submit"
              className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors"
            >
              Search
            </button>
          </form>
        </div>

        {/* Error */}
        {error && (
          <div className="flex items-center gap-2 bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 mb-4 text-sm">
            <AlertCircle className="w-4 h-4 flex-shrink-0" />
            {error}
          </div>
        )}

        {/* Table card */}
        <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
          {loading ? (
            <div className="flex items-center justify-center py-24">
              <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
            </div>
          ) : tickets.length === 0 ? (
            <div className="text-center py-24 text-gray-400">
              <p className="text-lg font-medium mb-1">No tickets found</p>
              <p className="text-sm">Try adjusting your filters or create a new ticket.</p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-gray-200 bg-gray-50">
                    {['Reference', 'Title', 'Category', 'Priority', 'Status', 'Created By', 'Assigned To', 'Date', ''].map(h => (
                      <th key={h} className="text-left text-xs font-semibold text-gray-500 uppercase tracking-wide px-4 py-3 whitespace-nowrap">
                        {h}
                      </th>
                    ))}
                  </tr>
                </thead>
                <tbody className="divide-y divide-gray-100">
                  {tickets.map(ticket => (
                    <tr key={ticket.TicketNumber} className="hover:bg-gray-50 transition-colors">
                      <td className="px-4 py-3 font-mono text-xs text-blue-600 whitespace-nowrap">
                        {ticket.TicketReferenceNumber}
                      </td>
                      <td className="px-4 py-3 text-gray-900 font-medium max-w-xs">
                        <span className="line-clamp-1">{ticket.Title}</span>
                      </td>
                      <td className="px-4 py-3 text-gray-500 whitespace-nowrap">
                        {ticket.category?.CategoryName ?? '—'}
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap">
                        <PriorityBadge priority={ticket.priority?.PriorityName} />
                      </td>
                      <td className="px-4 py-3 whitespace-nowrap">
                        <StatusBadge status={ticket.status?.StatusName} />
                      </td>
                      <td className="px-4 py-3 text-gray-500 whitespace-nowrap">
                        {ticket.creator?.FullName ?? '—'}
                      </td>
                      <td className="px-4 py-3 text-gray-500 whitespace-nowrap">
                        {ticket.assigned_to?.FullName ?? <span className="text-gray-300 italic">Unassigned</span>}
                      </td>
                      <td className="px-4 py-3 text-gray-400 text-xs whitespace-nowrap">
                        {formatDate(ticket.CreatedDate)}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex items-center justify-end gap-1">
                          <button
                            onClick={() => navigate(`/tickets/${ticket.TicketNumber}`)}
                            title="View"
                            className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
                          >
                            <Eye className="w-4 h-4" />
                          </button>
                          {canEdit(ticket) && (
                            <button
                              onClick={() => navigate(`/tickets/${ticket.TicketNumber}/edit`)}
                              title="Edit"
                              className="p-1.5 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors"
                            >
                              <Pencil className="w-4 h-4" />
                            </button>
                          )}
                          {isAdmin && (
                            <button
                              onClick={() => setDeleteConfirm(ticket.TicketNumber)}
                              title="Delete"
                              className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                            >
                              <Trash2 className="w-4 h-4" />
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}

          {/* Pagination */}
          {!loading && meta.last_page > 1 && (
            <div className="flex items-center justify-between border-t border-gray-100 px-4 py-3">
              <span className="text-sm text-gray-500">
                Page {meta.current_page} of {meta.last_page}
              </span>
              <div className="flex gap-1">
                <button
                  onClick={() => setPage(p => p - 1)}
                  disabled={meta.current_page === 1}
                  className="p-1.5 text-gray-400 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <ChevronLeft className="w-5 h-5" />
                </button>
                <button
                  onClick={() => setPage(p => p + 1)}
                  disabled={meta.current_page === meta.last_page}
                  className="p-1.5 text-gray-400 hover:text-gray-700 disabled:opacity-30 disabled:cursor-not-allowed rounded-lg hover:bg-gray-100 transition-colors"
                >
                  <ChevronRight className="w-5 h-5" />
                </button>
              </div>
            </div>
          )}
        </div>
      </main>

      {/* Delete confirmation modal */}
      {deleteConfirm && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl">
            <h3 className="text-lg font-bold text-gray-900 mb-2">Delete Ticket</h3>
            <p className="text-gray-500 text-sm mb-6">
              Are you sure you want to delete this ticket? This action cannot be undone.
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setDeleteConfirm(null)}
                className="flex-1 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                onClick={handleDelete}
                className="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors"
              >
                Delete
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
