import axios from 'axios';

const API_BASE = '/api';

const api = axios.create({
    baseURL: API_BASE,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Attach auth token if available
api.interceptors.request.use((config) => {
    const token = localStorage.getItem('auth_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export const estimationsApi = {
    list: (params = {}) => api.get('/estimations', { params }),
    show: (id) => api.get(`/estimations/${id}`),
    create: (data) => api.post('/estimations', data),
    update: (id, data) => api.put(`/estimations/${id}`, data),
    delete: (id) => api.delete(`/estimations/${id}`),
    approve: (id) => api.post(`/estimations/${id}/approve`),
    cancel: (id) => api.post(`/estimations/${id}/cancel`),
};

export const customersApi = {
    list: (params = {}) => api.get('/customers', { params }),
};

export const projectsApi = {
    list: (params = {}) => api.get('/projects', { params }),
};

export const productApi = {
    list: (params = {}) => api.get('/products', { params }),
    create: (data) => api.post('/products', data),
};

export default api;
