import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, AlertCircle } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import NavBar from '../components/NavBar';
import * as svc from '../services/ticketService';

const PAGE_BG = { backgroundColor: '#0A1929' };

export default function TicketEdit() {
  const { id }   = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const isEmployee = user?.RoleName === 'Employee';
  const isAdmin    = user?.RoleName === 'Admin';
  const canAdvanced = !isEmployee;                         // Agent, Manager, Admin
  const canAssign   = isAdmin || user?.RoleName === 'Manager';

  // Form fields
  const [title,              setTitle]              = useState('');
  const [description,        setDescription]        = useState('');
  const [categoryNumber,     setCategoryNumber]      = useState('');
  const [priorityNumber,     setPriorityNumber]      = useState('');
  const [statusNumber,       setStatusNumber]        = useState('');
  const [assignedToUser,     setAssignedToUser]      = useState('');
  const [isEscalated,        setIsEscalated]         = useState(false);
  const [resolutionNotes,    setResolutionNotes]     = useState('');

  // Lookup data
  const [categories, setCategories] = useState([]);
  const [priorities, setPriorities] = useState([]);
  const [statuses,   setStatuses]   = useState([]);
  const [agents,     setAgents]     = useState([]);

  // UI state
  const [fetchLoading, setFetchLoading] = useState(true);
  const [fetchError,   setFetchError]   = useState('');
  const [errors,       setErrors]       = useState({});
  const [loading,      setLoading]      = useState(false);

  useEffect(() => {
    const lookupCalls = [
      svc.getCategories(),
      svc.getPriorities(),
      canAdvanced ? svc.getStatuses() : Promise.resolve({ data: [] }),
      canAssign   ? svc.getAgents().catch(() => ({ data: [] })) : Promise.resolve({ data: [] }),
    ];

    Promise.all([svc.getTicket(id), ...lookupCalls])
      .then(([ticketRes, catRes, priRes, statRes, agentRes]) => {
        const t = ticketRes.data;
        setTitle(t.Title ?? '');
        setDescription(t.Description ?? '');
        setCategoryNumber(String(t.CategoryNumber ?? ''));
        setPriorityNumber(String(t.PriorityNumber ?? ''));
        setStatusNumber(String(t.StatusNumber ?? ''));
        setAssignedToUser(t.AssignedToUserNumber ? String(t.AssignedToUserNumber) : '');
        setIsEscalated(Boolean(t.IsEscalated));
        setResolutionNotes(t.ResolutionNotes ?? '');

        setCategories(catRes.data);
        setPriorities(priRes.data);
        setStatuses(statRes.data);
        setAgents(agentRes.data);
      })
      .catch((err) => {
        if (err.response?.status === 403) setFetchError('You do not have permission to edit this ticket.');
        else if (err.response?.status === 404) setFetchError('Ticket not found.');
        else setFetchError('Failed to load ticket.');
      })
      .finally(() => setFetchLoading(false));
  }, [id]);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setLoading(true);

    const payload = {
      Title:       title,
      Description: description,
    };

    if (!isEmployee) {
      if (categoryNumber) payload.CategoryNumber = parseInt(categoryNumber);
      if (priorityNumber) payload.PriorityNumber = parseInt(priorityNumber);
      if (statusNumber)   payload.StatusNumber   = parseInt(statusNumber);
      if (canAssign) {
        payload.AssignedToUserNumber = assignedToUser ? parseInt(assignedToUser) : null;
      }
    }

    if (isAdmin) {
      payload.IsEscalated    = isEscalated;
      payload.ResolutionNotes = resolutionNotes || null;
    }

    try {
      await svc.updateTicket(id, payload);
      navigate(`/tickets/${id}`);
    } catch (err) {
      if (err.response?.status === 422) {
        setErrors(err.response.data.errors ?? {});
      } else {
        setErrors({ general: err.response?.data?.message || 'An error occurred. Please try again.' });
      }
    } finally {
      setLoading(false);
    }
  };

  const fieldCls    = 'w-full px-3 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400 transition';
  const disabledCls = 'w-full px-3 py-3 border border-gray-100 rounded-lg text-sm bg-gray-50 text-gray-500 cursor-not-allowed';
  const labelCls    = 'block text-xs font-bold text-gray-700 mb-1.5';

  if (fetchLoading) {
    return (
      <div className="min-h-screen" style={PAGE_BG}>
        <NavBar />
        <div className="flex items-center justify-center py-32">
          <div className="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin" />
        </div>
      </div>
    );
  }

  if (fetchError) {
    return (
      <div className="min-h-screen" style={PAGE_BG}>
        <NavBar />
        <main className="max-w-2xl mx-auto px-4 sm:px-6 py-8">
          <button onClick={() => navigate('/tickets')} className="flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-6 transition-colors">
            <ArrowLeft className="w-4 h-4" /> Back to Tickets
          </button>
          <div className="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 text-sm">{fetchError}</div>
        </main>
      </div>
    );
  }

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-2xl mx-auto px-4 sm:px-6 py-8">
        <button
          onClick={() => navigate(`/tickets/${id}`)}
          className="flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-6 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Ticket
        </button>

        <div className="bg-white rounded-2xl shadow-2xl p-8">
          <h1 className="text-2xl font-bold text-gray-900 mb-1">Edit Ticket</h1>
          <p className="text-gray-500 text-sm mb-7">Update the ticket details below.</p>

          {errors.general && (
            <div className="flex items-start gap-2.5 bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-5 text-sm">
              <AlertCircle className="w-4 h-4 mt-0.5 shrink-0" />
              <span>{errors.general}</span>
            </div>
          )}

          <form onSubmit={handleSubmit} className="space-y-5">
            {/* Title */}
            <div>
              <label className={labelCls}>Title <span className="text-red-500">*</span></label>
              <input
                type="text"
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                maxLength={200}
                className={fieldCls}
              />
              {errors.Title && <p className="text-red-500 text-xs mt-1">{errors.Title[0]}</p>}
            </div>

            {/* Description */}
            <div>
              <label className={labelCls}>Description <span className="text-red-500">*</span></label>
              <textarea
                value={description}
                onChange={(e) => setDescription(e.target.value)}
                rows={5}
                className={`${fieldCls} resize-none`}
              />
              {errors.Description && <p className="text-red-500 text-xs mt-1">{errors.Description[0]}</p>}
            </div>

            {/* Category + Priority — editable for Agent/Manager/Admin, disabled for Employee */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className={labelCls}>Category</label>
                {canAdvanced ? (
                  <select value={categoryNumber} onChange={(e) => setCategoryNumber(e.target.value)} className={fieldCls}>
                    <option value="">Select category</option>
                    {categories.map(c => <option key={c.CategoryNumber} value={c.CategoryNumber}>{c.CategoryName}</option>)}
                  </select>
                ) : (
                  <select value={categoryNumber} disabled className={disabledCls}>
                    <option value="">—</option>
                    {categories.map(c => <option key={c.CategoryNumber} value={c.CategoryNumber}>{c.CategoryName}</option>)}
                  </select>
                )}
                {errors.CategoryNumber && <p className="text-red-500 text-xs mt-1">{errors.CategoryNumber[0]}</p>}
              </div>
              <div>
                <label className={labelCls}>Priority</label>
                {canAdvanced ? (
                  <select value={priorityNumber} onChange={(e) => setPriorityNumber(e.target.value)} className={fieldCls}>
                    <option value="">Select priority</option>
                    {priorities.map(p => <option key={p.PriorityNumber} value={p.PriorityNumber}>{p.PriorityName}</option>)}
                  </select>
                ) : (
                  <select value={priorityNumber} disabled className={disabledCls}>
                    <option value="">—</option>
                    {priorities.map(p => <option key={p.PriorityNumber} value={p.PriorityNumber}>{p.PriorityName}</option>)}
                  </select>
                )}
                {errors.PriorityNumber && <p className="text-red-500 text-xs mt-1">{errors.PriorityNumber[0]}</p>}
              </div>
            </div>

            {/* Status + Assignee — Agent/Manager/Admin only */}
            {canAdvanced && (
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className={labelCls}>Status</label>
                  <select value={statusNumber} onChange={(e) => setStatusNumber(e.target.value)} className={fieldCls}>
                    <option value="">Select status</option>
                    {statuses.map(s => <option key={s.StatusNumber} value={s.StatusNumber}>{s.StatusName}</option>)}
                  </select>
                  {errors.StatusNumber && <p className="text-red-500 text-xs mt-1">{errors.StatusNumber[0]}</p>}
                </div>

                {canAssign && agents.length > 0 && (
                  <div>
                    <label className={labelCls}>Assigned To</label>
                    <select value={assignedToUser} onChange={(e) => setAssignedToUser(e.target.value)} className={fieldCls}>
                      <option value="">Unassigned</option>
                      {agents.map(a => <option key={a.UserNumber} value={a.UserNumber}>{a.FullName}</option>)}
                    </select>
                    {errors.AssignedToUserNumber && <p className="text-red-500 text-xs mt-1">{errors.AssignedToUserNumber[0]}</p>}
                  </div>
                )}
              </div>
            )}

            {/* Admin-only fields */}
            {isAdmin && (
              <>
                <div className="flex items-center gap-3 py-2">
                  <button
                    type="button"
                    onClick={() => setIsEscalated(v => !v)}
                    className={`relative w-11 h-6 rounded-full transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1 ${
                      isEscalated ? 'bg-red-500' : 'bg-gray-200'
                    }`}
                  >
                    <span className={`absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full shadow transition-transform ${isEscalated ? 'translate-x-5' : 'translate-x-0'}`} />
                  </button>
                  <label className="text-sm font-medium text-gray-700 cursor-pointer select-none" onClick={() => setIsEscalated(v => !v)}>
                    Mark as Escalated
                  </label>
                </div>

                <div>
                  <label className={labelCls}>Resolution Notes</label>
                  <textarea
                    value={resolutionNotes}
                    onChange={(e) => setResolutionNotes(e.target.value)}
                    rows={4}
                    placeholder="Describe how the issue was resolved…"
                    className={`${fieldCls} resize-none`}
                  />
                </div>
              </>
            )}

            {/* Actions */}
            <div className="flex gap-3 pt-2">
              <button
                type="button"
                onClick={() => navigate(`/tickets/${id}`)}
                className="flex-1 px-4 py-3 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
              >
                Cancel
              </button>
              <button
                type="submit"
                disabled={loading}
                className="flex-1 bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold py-3 rounded-lg text-sm transition-colors flex items-center justify-center gap-2"
              >
                {loading ? (
                  <>
                    <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    Saving…
                  </>
                ) : 'Update Ticket'}
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
  );
}
