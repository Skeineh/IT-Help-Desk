import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Pencil, Trash2 } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import NavBar from '../components/NavBar';
import StatusBadge from '../components/StatusBadge';
import PriorityBadge from '../components/PriorityBadge';
import * as svc from '../services/ticketService';

const PAGE_BG = { backgroundColor: '#0A1929' };

function formatDateTime(str) {
  if (!str) return '—';
  return new Date(str).toLocaleString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit',
  });
}

function DetailRow({ label, value }) {
  return (
    <div>
      <p className="text-xs text-gray-400 mb-0.5">{label}</p>
      <p className="text-sm font-medium text-gray-800">{value ?? '—'}</p>
    </div>
  );
}

export default function TicketDetails() {
  const { id }   = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [ticket,        setTicket]        = useState(null);
  const [loading,       setLoading]       = useState(true);
  const [error,         setError]         = useState('');
  const [deleteConfirm, setDeleteConfirm] = useState(false);
  const [deleting,      setDeleting]      = useState(false);

  const isAdmin    = user?.RoleName === 'Admin';
  const isEmployee = user?.RoleName === 'Employee';

  const canEdit = ticket && (
    isAdmin ||
    user?.RoleName === 'Manager' ||
    (user?.RoleName === 'Agent'   && ticket.AssignedToUserNumber === user?.UserNumber) ||
    (isEmployee && ticket.CreatedByUserNumber === user?.UserNumber)
  );

  useEffect(() => {
    setLoading(true);
    svc.getTicket(id)
      .then(({ data }) => setTicket(data))
      .catch((err) => {
        if (err.response?.status === 403) setError('You do not have permission to view this ticket.');
        else if (err.response?.status === 404) setError('Ticket not found.');
        else setError('Failed to load ticket.');
      })
      .finally(() => setLoading(false));
  }, [id]);

  const handleDelete = async () => {
    setDeleting(true);
    try {
      await svc.deleteTicket(id);
      navigate('/tickets');
    } catch {
      setError('Failed to delete ticket.');
      setDeleteConfirm(false);
    } finally {
      setDeleting(false);
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen" style={PAGE_BG}>
        <NavBar />
        <div className="flex items-center justify-center py-32">
          <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>
      </div>
    );
  }

  if (error && !ticket) {
    return (
      <div className="min-h-screen" style={PAGE_BG}>
        <NavBar />
        <main className="max-w-2xl mx-auto px-4 sm:px-6 py-8">
          <button onClick={() => navigate('/tickets')} className="flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-6 transition-colors">
            <ArrowLeft className="w-4 h-4" /> Back to Tickets
          </button>
          <div className="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 text-sm">{error}</div>
        </main>
      </div>
    );
  }

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-3xl mx-auto px-4 sm:px-6 py-8">
        {/* Top bar */}
        <div className="flex items-center justify-between mb-6">
          <button
            onClick={() => navigate('/tickets')}
            className="flex items-center gap-2 text-slate-400 hover:text-white text-sm transition-colors"
          >
            <ArrowLeft className="w-4 h-4" />
            Back to Tickets
          </button>
          <div className="flex gap-2">
            {canEdit && (
              <button
                onClick={() => navigate(`/tickets/${ticket.TicketNumber}/edit`)}
                className="flex items-center gap-1.5 text-sm font-medium px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
              >
                <Pencil className="w-3.5 h-3.5" />
                Edit
              </button>
            )}
            {isAdmin && (
              <button
                onClick={() => setDeleteConfirm(true)}
                className="flex items-center gap-1.5 text-sm font-medium px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors"
              >
                <Trash2 className="w-3.5 h-3.5" />
                Delete
              </button>
            )}
          </div>
        </div>

        {error && (
          <div className="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 mb-4 text-sm">{error}</div>
        )}

        {ticket && (
          <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
            {/* Header gradient banner */}
            <div className="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-6">
              <div className="flex items-start justify-between gap-4 flex-wrap">
                <div>
                  <p className="text-blue-200 text-xs font-mono tracking-wider mb-1">
                    {ticket.TicketReferenceNumber}
                  </p>
                  <h1 className="text-xl font-bold text-white leading-snug">{ticket.Title}</h1>
                </div>
                <div className="flex gap-2 flex-shrink-0 flex-wrap">
                  <StatusBadge status={ticket.status?.StatusName} />
                  <PriorityBadge priority={ticket.priority?.PriorityName} />
                  {ticket.IsEscalated && (
                    <span className="inline-flex items-center text-xs font-semibold px-2.5 py-0.5 rounded-full border bg-red-100 text-red-700 border-red-200">
                      Escalated
                    </span>
                  )}
                </div>
              </div>
            </div>

            {/* Body */}
            <div className="p-8 space-y-6">
              {/* Description */}
              <div>
                <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Description</h3>
                <p className="text-gray-700 text-sm whitespace-pre-wrap leading-relaxed">{ticket.Description}</p>
              </div>

              {/* Details grid */}
              <div className="grid grid-cols-2 sm:grid-cols-3 gap-5 pt-5 border-t border-gray-100">
                <DetailRow label="Category"   value={ticket.category?.CategoryName} />
                <DetailRow label="Created By" value={ticket.creator?.FullName} />
                <DetailRow label="Assigned To" value={ticket.assigned_to?.FullName ?? 'Unassigned'} />
                <DetailRow label="Department"  value={ticket.creator?.Department} />
                <DetailRow label="Created"     value={formatDateTime(ticket.CreatedDate)} />
                {ticket.ResolvedDate && (
                  <DetailRow label="Resolved"  value={formatDateTime(ticket.ResolvedDate)} />
                )}
              </div>

              {/* Resolution Notes */}
              {ticket.ResolutionNotes && (
                <div className="pt-5 border-t border-gray-100">
                  <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Resolution Notes</h3>
                  <div className="bg-green-50 border border-green-200 rounded-lg p-4">
                    <p className="text-gray-700 text-sm whitespace-pre-wrap leading-relaxed">{ticket.ResolutionNotes}</p>
                  </div>
                </div>
              )}
            </div>
          </div>
        )}
      </main>

      {/* Delete modal */}
      {deleteConfirm && (
        <div className="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center p-4 z-50">
          <div className="bg-white rounded-2xl p-6 max-w-sm w-full shadow-2xl">
            <h3 className="text-lg font-bold text-gray-900 mb-2">Delete Ticket</h3>
            <p className="text-gray-500 text-sm mb-6">
              Are you sure you want to delete <strong>{ticket?.TicketReferenceNumber}</strong>? This action cannot be undone.
            </p>
            <div className="flex gap-3">
              <button
                onClick={() => setDeleteConfirm(false)}
                disabled={deleting}
                className="flex-1 px-4 py-2.5 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-50"
              >
                Cancel
              </button>
              <button
                onClick={handleDelete}
                disabled={deleting}
                className="flex-1 px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
              >
                {deleting ? <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" /> : 'Delete'}
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
