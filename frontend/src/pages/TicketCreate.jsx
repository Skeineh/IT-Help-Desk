import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import { ArrowLeft, AlertCircle } from 'lucide-react';
import NavBar from '../components/NavBar';
import * as svc from '../services/ticketService';

const PAGE_BG = { backgroundColor: '#0A1929' };

export default function TicketCreate() {
  const navigate = useNavigate();

  const [title,          setTitle]          = useState('');
  const [description,    setDescription]    = useState('');
  const [categoryNumber, setCategoryNumber] = useState('');
  const [priorityNumber, setPriorityNumber] = useState('');

  const [categories, setCategories] = useState([]);
  const [priorities, setPriorities] = useState([]);

  const [errors,  setErrors]  = useState({});
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    Promise.all([svc.getCategories(), svc.getPriorities()])
      .then(([c, p]) => { setCategories(c.data); setPriorities(p.data); })
      .catch(() => {});
  }, []);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setErrors({});
    setLoading(true);
    try {
      const { data } = await svc.createTicket({
        Title:          title,
        Description:    description,
        CategoryNumber: parseInt(categoryNumber),
        PriorityNumber: parseInt(priorityNumber),
      });
      navigate(`/tickets/${data.TicketNumber}`);
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

  const fieldCls = 'w-full px-3 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent placeholder-gray-400 transition';
  const labelCls = 'block text-xs font-bold text-gray-700 mb-1.5';

  return (
    <div className="min-h-screen" style={PAGE_BG}>
      <NavBar />

      <main className="max-w-2xl mx-auto px-4 sm:px-6 py-8">
        <button
          onClick={() => navigate('/tickets')}
          className="flex items-center gap-2 text-slate-400 hover:text-white text-sm mb-6 transition-colors"
        >
          <ArrowLeft className="w-4 h-4" />
          Back to Tickets
        </button>

        <div className="bg-white rounded-2xl shadow-2xl p-8">
          <h1 className="text-2xl font-bold text-gray-900 mb-1">New Ticket</h1>
          <p className="text-gray-500 text-sm mb-7">Describe your issue and we'll get it to the right team.</p>

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
                placeholder="Brief summary of the issue"
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
                placeholder="Provide detailed information about your issue — steps to reproduce, error messages, affected systems…"
                className={`${fieldCls} resize-none`}
              />
              {errors.Description && <p className="text-red-500 text-xs mt-1">{errors.Description[0]}</p>}
            </div>

            {/* Category + Priority */}
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className={labelCls}>Category <span className="text-red-500">*</span></label>
                <select
                  value={categoryNumber}
                  onChange={(e) => setCategoryNumber(e.target.value)}
                  className={fieldCls}
                >
                  <option value="">Select category</option>
                  {categories.map(c => (
                    <option key={c.CategoryNumber} value={c.CategoryNumber}>{c.CategoryName}</option>
                  ))}
                </select>
                {errors.CategoryNumber && <p className="text-red-500 text-xs mt-1">{errors.CategoryNumber[0]}</p>}
              </div>
              <div>
                <label className={labelCls}>Priority <span className="text-red-500">*</span></label>
                <select
                  value={priorityNumber}
                  onChange={(e) => setPriorityNumber(e.target.value)}
                  className={fieldCls}
                >
                  <option value="">Select priority</option>
                  {priorities.map(p => (
                    <option key={p.PriorityNumber} value={p.PriorityNumber}>{p.PriorityName}</option>
                  ))}
                </select>
                {errors.PriorityNumber && <p className="text-red-500 text-xs mt-1">{errors.PriorityNumber[0]}</p>}
              </div>
            </div>

            {/* Actions */}
            <div className="flex gap-3 pt-2">
              <button
                type="button"
                onClick={() => navigate('/tickets')}
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
                    Creating…
                  </>
                ) : 'Create Ticket'}
              </button>
            </div>
          </form>
        </div>
      </main>
    </div>
  );
}
