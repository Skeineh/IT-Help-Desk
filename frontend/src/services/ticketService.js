import api from './api';

export const listTickets  = (params) => api.get('/tickets', { params });
export const getTicket    = (id)     => api.get(`/tickets/${id}`);
export const createTicket = (data)   => api.post('/tickets', data);
export const updateTicket = (id, data) => api.put(`/tickets/${id}`, data);
export const deleteTicket = (id)     => api.delete(`/tickets/${id}`);
export const getCategories = ()      => api.get('/categories');
export const getPriorities = ()      => api.get('/priorities');
export const getStatuses  = ()       => api.get('/statuses');
export const getAgents    = ()       => api.get('/agents');

export const getDashboardStats = () => api.get('/dashboard/stats');

export const getTicketHistory = (id) => api.get(`/tickets/${id}/history`);

export const getComments = (ticketId) => api.get(`/tickets/${ticketId}/comments`);
export const createComment = (ticketId, data) => api.post(`/tickets/${ticketId}/comments`, data);
export const deleteComment = (commentId) => api.delete(`/comments/${commentId}`);

export const getAttachments = (ticketId) => api.get(`/tickets/${ticketId}/attachments`);
export const uploadAttachment = (ticketId, file) => {
  const formData = new FormData();
  formData.append('file', file);
  return api.post(`/tickets/${ticketId}/attachments`, formData, {
    headers: { 'Content-Type': 'multipart/form-data' },
  });
};
export const downloadAttachment = (attachmentId) => api.get(`/attachments/${attachmentId}/download`, {
  responseType: 'blob',
});
export const deleteAttachment = (attachmentId) => api.delete(`/attachments/${attachmentId}`);
