import api from './api';

export const getRoles = () => api.get('/admin/roles');
export const createUser = (data) => api.post('/admin/users', data);
