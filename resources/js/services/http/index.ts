import axios from 'axios';
import { clearErrors, setErrors } from '../error';

// API URLs use /api; session and CSRF URLs use Laravel's web routes.
const http = axios.create({
    headers: { Accept: 'application/json' },
    withCredentials: true, // Send the Laravel session cookie.
    withXSRFToken: true, // Send Laravel's CSRF token with form requests.
});
http.interceptors.request.use(config => {
    clearErrors();
    return config;
});
http.interceptors.response.use(response => response, error => {
    const status = error.response?.status;
    const data = error.response?.data;
    let message = data?.message || 'The request failed. Please try again.';
    if (!error.response) {
        message = 'Cannot reach the server. Check your connection.';
    }
    if (status === 401) {
        message = 'Please log in to continue. Your session may have expired.';
    }
    if (status === 403) {
        message = 'You do not have permission to do this.';
    }
    if (status === 404) {
        message = 'The requested item could not be found.';
    }
    if (status === 419) {
        message = 'Your session expired. Reload the page and try again.';
    }
    if (status >= 500) {
        message = 'The server could not complete the request. Please try again.';
    }
    setErrors(message, data?.errors || {});
    return Promise.reject(error);
});
export const getRequest = <T>(url: string) => http.get<T>(url);
export const postRequest = <T = unknown>(url: string, data: object = {}) => http.post<T>(url, data);
export const putRequest = <T = unknown>(url: string, data: object) => http.put<T>(url, data);
export const patchRequest = <T = unknown>(url: string, data: object) => http.patch<T>(url, data);
export const deleteRequest = (url: string) => http.delete(url);
