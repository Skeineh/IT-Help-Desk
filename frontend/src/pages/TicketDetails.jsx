import { useCallback, useEffect, useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { ArrowLeft, Download, FileText, MessageSquare, Paperclip, Pencil, Trash2 } from 'lucide-react';
import { useAuth } from '../contexts/AuthContext';
import NavBar from '../components/NavBar';
import StatusBadge from '../components/StatusBadge';
import PriorityBadge from '../components/PriorityBadge';
import * as svc from '../services/ticketService';

const PAGE_BG = { backgroundColor: '#0A1929' };

function formatDateTime(str) {
  if (!str) return '-';
  return new Date(str).toLocaleString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

function formatFileSize(bytes) {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
  return `${(bytes / (1024 ** index)).toFixed(index === 0 ? 0 : 1)} ${units[index]}`;
}

function DetailRow({ label, value }) {
  return (
    <div>
      <p className="text-xs text-gray-400 mb-0.5">{label}</p>
      <p className="text-sm font-medium text-gray-800">{value ?? '-'}</p>
    </div>
  );
}

function Section({ title, icon: Icon, children }) {
  return (
    <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
      <div className="flex items-center gap-2 px-6 py-4 border-b border-gray-100">
        <Icon className="w-4 h-4 text-blue-600" />
        <h2 className="text-base font-bold text-gray-900">{title}</h2>
      </div>
      <div className="p-6">{children}</div>
    </div>
  );
}

export default function TicketDetails() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();

  const [ticket, setTicket] = useState(null);
  const [comments, setComments] = useState([]);
  const [attachments, setAttachments] = useState([]);
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const [deleteConfirm, setDeleteConfirm] = useState(false);
  const [deleting, setDeleting] = useState(false);
  const [commentBody, setCommentBody] = useState('');
  const [isInternal, setIsInternal] = useState(false);
  const [postingComment, setPostingComment] = useState(false);
  const [commentError, setCommentError] = useState('');
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploading, setUploading] = useState(false);
  const [attachmentError, setAttachmentError] = useState('');

  const isAdmin = user?.RoleName === 'Admin';
  const isEmployee = user?.RoleName === 'Employee';
  const canUseInternalNotes = ['Admin', 'Manager', 'Agent'].includes(user?.RoleName);

  const canEdit = ticket && (
    isAdmin ||
    user?.RoleName === 'Manager' ||
    (user?.RoleName === 'Agent' && ticket.AssignedToUserNumber === user?.UserNumber) ||
    (isEmployee && ticket.CreatedByUserNumber === user?.UserNumber)
  );

  const loadRelated = useCallback(async () => {
    const [commentsRes, attachmentsRes, historyRes] = await Promise.all([
      svc.getComments(id),
      svc.getAttachments(id),
      svc.getTicketHistory(id),
    ]);
    setComments(commentsRes.data);
    setAttachments(attachmentsRes.data);
    setHistory(historyRes.data);
  }, [id]);

  const loadAll = useCallback(async () => {
    setLoading(true);
    setError('');
    try {
      const ticketRes = await svc.getTicket(id);
      setTicket(ticketRes.data);
      await loadRelated();
    } catch (err) {
      if (err.response?.status === 403) setError('You do not have permission to view this ticket.');
      else if (err.response?.status === 404) setError('Ticket not found.');
      else setError('Failed to load ticket.');
    } finally {
      setLoading(false);
    }
  }, [id, loadRelated]);

  useEffect(() => {
    loadAll();
  }, [loadAll]);

  const handleDeleteTicket = async () => {
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

  const handleCommentSubmit = async (e) => {
    e.preventDefault();
    setCommentError('');
    setPostingComment(true);
    try {
      await svc.createComment(id, {
        body: commentBody,
        is_internal: canUseInternalNotes ? isInternal : false,
      });
      setCommentBody('');
      setIsInternal(false);
      await loadRelated();
    } catch (err) {
      setCommentError(err.response?.data?.message || 'Failed to add comment.');
    } finally {
      setPostingComment(false);
    }
  };

  const handleDeleteComment = async (commentId) => {
    await svc.deleteComment(commentId);
    await loadRelated();
  };

  const handleUpload = async (e) => {
    e.preventDefault();
    if (!selectedFile) return;
    setAttachmentError('');
    setUploading(true);
    try {
      await svc.uploadAttachment(id, selectedFile);
      setSelectedFile(null);
      e.target.reset();
      await loadRelated();
    } catch (err) {
      const validation = err.response?.data?.errors?.file?.[0];
      setAttachmentError(validation || err.response?.data?.message || 'Failed to upload attachment.');
    } finally {
      setUploading(false);
    }
  };

  const handleDownload = async (attachment) => {
    const { data } = await svc.downloadAttachment(attachment.id);
    const url = window.URL.createObjectURL(data);
    const link = document.createElement('a');
    link.href = url;
    link.download = attachment.original_filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.URL.revokeObjectURL(url);
  };

  const handleDeleteAttachment = async (attachmentId) => {
    await svc.deleteAttachment(attachmentId);
    await loadRelated();
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

      <main className="max-w-5xl mx-auto px-4 sm:px-6 py-8 space-y-6">
        <div className="flex items-center justify-between">
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
          <div className="bg-red-500/10 border border-red-500/30 text-red-400 rounded-lg px-4 py-3 text-sm">{error}</div>
        )}

        <div className="bg-white rounded-2xl shadow-2xl overflow-hidden">
          <div className="bg-gradient-to-r from-blue-600 to-indigo-700 px-8 py-6">
            <div className="flex items-start justify-between gap-4 flex-wrap">
              <div>
                <p className="text-blue-200 text-xs font-mono tracking-wider mb-1">{ticket.TicketReferenceNumber}</p>
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

          <div className="p-8 space-y-6">
            <div>
              <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Description</h3>
              <p className="text-gray-700 text-sm whitespace-pre-wrap leading-relaxed">{ticket.Description}</p>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-3 gap-5 pt-5 border-t border-gray-100">
              <DetailRow label="Category" value={ticket.category?.CategoryName} />
              <DetailRow label="Created By" value={ticket.creator?.FullName} />
              <DetailRow label="Assigned To" value={ticket.assigned_to?.FullName ?? 'Unassigned'} />
              <DetailRow label="Department" value={ticket.creator?.Department} />
              <DetailRow label="Created" value={formatDateTime(ticket.CreatedDate)} />
              {ticket.ResolvedDate && <DetailRow label="Resolved" value={formatDateTime(ticket.ResolvedDate)} />}
            </div>

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

        <Section title="Comments" icon={MessageSquare}>
          {comments.length === 0 ? (
            <div className="text-center py-8 text-gray-400 text-sm">No comments yet.</div>
          ) : (
            <div className="space-y-3 mb-6">
              {comments.map((comment) => (
                <div
                  key={comment.id}
                  className={`rounded-xl border p-4 ${
                    comment.is_internal ? 'bg-amber-50 border-amber-200' : 'bg-gray-50 border-gray-100'
                  }`}
                >
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <div className="flex items-center gap-2 flex-wrap">
                        <p className="text-sm font-bold text-gray-900">{comment.user?.name ?? 'Unknown user'}</p>
                        <span className="text-xs text-gray-400">{comment.user?.role}</span>
                        {comment.is_internal && (
                          <span className="text-xs font-bold text-amber-700 bg-amber-100 border border-amber-200 rounded-full px-2 py-0.5">
                            Internal Note
                          </span>
                        )}
                      </div>
                      <p className="text-xs text-gray-400 mt-0.5">{formatDateTime(comment.created_at)}</p>
                    </div>
                    {isAdmin && (
                      <button
                        type="button"
                        onClick={() => handleDeleteComment(comment.id)}
                        className="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                        title="Delete comment"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                  <p className="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed mt-3">{comment.body}</p>
                </div>
              ))}
            </div>
          )}

          {commentError && (
            <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">{commentError}</div>
          )}

          <form onSubmit={handleCommentSubmit} className="space-y-3">
            <textarea
              value={commentBody}
              onChange={(e) => setCommentBody(e.target.value)}
              rows={4}
              required
              maxLength={5000}
              placeholder="Write a reply..."
              className="w-full px-3 py-3 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
            />
            <div className="flex items-center justify-between gap-3">
              {canUseInternalNotes ? (
                <label className="flex items-center gap-2 text-sm text-gray-600 select-none">
                  <input
                    type="checkbox"
                    checked={isInternal}
                    onChange={(e) => setIsInternal(e.target.checked)}
                    className="w-4 h-4"
                  />
                  Internal note
                </label>
              ) : <span />}
              <button
                type="submit"
                disabled={postingComment || !commentBody.trim()}
                className="bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold px-4 py-2 rounded-lg text-sm transition flex items-center gap-2"
              >
                {postingComment && <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />}
                Add Comment
              </button>
            </div>
          </form>
        </Section>

        <Section title="Attachments" icon={Paperclip}>
          {attachments.length === 0 ? (
            <div className="text-center py-8 text-gray-400 text-sm">No attachments yet.</div>
          ) : (
            <div className="space-y-3 mb-6">
              {attachments.map((attachment) => (
                <div key={attachment.id} className="flex items-center justify-between gap-4 rounded-xl border border-gray-100 bg-gray-50 p-4">
                  <div className="flex items-center gap-3 min-w-0">
                    <FileText className="w-5 h-5 text-blue-600 shrink-0" />
                    <div className="min-w-0">
                      <p className="text-sm font-bold text-gray-900 truncate">{attachment.original_filename}</p>
                      <p className="text-xs text-gray-400">
                        {formatFileSize(attachment.file_size)} - {attachment.uploaded_by?.name ?? 'Unknown'} - {formatDateTime(attachment.created_at)}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center gap-1 shrink-0">
                    <button
                      type="button"
                      onClick={() => handleDownload(attachment)}
                      className="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                      title="Download attachment"
                    >
                      <Download className="w-4 h-4" />
                    </button>
                    {attachment.can_delete && (
                      <button
                        type="button"
                        onClick={() => handleDeleteAttachment(attachment.id)}
                        className="p-2 text-gray-500 hover:text-red-600 hover:bg-red-50 rounded-lg transition"
                        title="Delete attachment"
                      >
                        <Trash2 className="w-4 h-4" />
                      </button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}

          {attachmentError && (
            <div className="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 mb-4 text-sm">{attachmentError}</div>
          )}

          <form onSubmit={handleUpload} className="flex flex-col sm:flex-row gap-3">
            <input
              type="file"
              onChange={(e) => setSelectedFile(e.target.files?.[0] ?? null)}
              className="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2"
            />
            <button
              type="submit"
              disabled={uploading || !selectedFile}
              className="bg-blue-600 hover:bg-blue-700 disabled:opacity-60 disabled:cursor-not-allowed text-white font-semibold px-4 py-2 rounded-lg text-sm transition flex items-center justify-center gap-2"
            >
              {uploading && <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />}
              Upload
            </button>
          </form>
        </Section>

        <Section title="Ticket History" icon={FileText}>
          {history.length === 0 ? (
            <div className="text-center py-8 text-gray-400 text-sm">No history yet.</div>
          ) : (
            <div className="space-y-4">
              {history.map((event) => (
                <div key={event.id} className="flex gap-3">
                  <div className="mt-1 w-2.5 h-2.5 rounded-full bg-blue-600 shrink-0" />
                  <div className="pb-4 border-b border-gray-100 flex-1">
                    <div className="flex items-center justify-between gap-3">
                      <p className="text-sm font-bold text-gray-900">{event.action}</p>
                      <p className="text-xs text-gray-400 whitespace-nowrap">{formatDateTime(event.created_at)}</p>
                    </div>
                    <p className="text-sm text-gray-600 mt-1">{event.message}</p>
                    <p className="text-xs text-gray-400 mt-1">
                      {event.user?.name ?? 'System'}{event.user?.role ? ` - ${event.user.role}` : ''}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Section>
      </main>

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
                onClick={handleDeleteTicket}
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
