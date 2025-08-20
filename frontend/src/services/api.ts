// src/api.ts
import axios from "axios";

// Use environment variable for backend URL, fallback to localhost for development
const getBaseURL = () => {
    if (process.env.NODE_ENV === 'production') {
        return process.env.REACT_APP_API_URL || window.location.origin;
    }
    return process.env.REACT_APP_API_URL || "http://localhost:8000";
};

export const api = axios.create({
    baseURL: getBaseURL(),
    headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
        "X-Requested-With": "XMLHttpRequest",
    },
});

// Add request interceptor to include Authorization header
api.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        return config;
    },
    (error) => Promise.reject(error)
);


/** AUTH (Sanctum token-based) **/
export const authApi = {
    register: async (data: {
        name: string;
        email: string;
        password: string;
        password_confirmation: string;
    }) => {
        return api.post("/api/register", data);
    },

    login: async (data: { email: string; password: string }) => {
        return api.post("/api/login", data);
    },

    logout: () => api.post("/api/logout"),
    
    getUser: () => api.get("/api/user"),
};

/** ClickUp endpoints (all under /api/*) **/
export const clickUpApi = {
    getAccounts: () => api.get("/api/clickup-accounts"),

    createAccount: (data: {
        name: string;
        access_token: string;
        account_type: string;
    }) => api.post("/api/clickup-accounts", data),

    deleteAccount: (id: number) => api.delete(`/api/clickup-accounts/${id}`),

    testConnection: (id: number) => api.post(`/api/clickup-accounts/${id}/test`),

    getWorkspaces: (accountId: number) =>
        api.get(`/api/clickup-accounts/${accountId}/workspaces`),

    getSpaces: (accountId: number, workspaceId: string) =>
        api.get(
            `/api/clickup-accounts/${accountId}/workspaces/${workspaceId}/spaces`
        ),

    getLists: (
        accountId: number,
        params: { space_id?: string; folder_id?: string }
    ) => api.get(`/api/clickup-accounts/${accountId}/lists`, { params }),

    getTasks: (accountId: number, listId: string, page: number = 0) =>
        api.get(`/api/clickup-accounts/${accountId}/lists/${listId}/tasks`, { params: { page } }),
};

/** Monday.com endpoints **/
export const mondayApi = {
    getAccounts: () => api.get("/api/monday-accounts"),

    createAccount: (data: {
        name: string;
        access_token: string;
        account_type: string;
    }) => api.post("/api/monday-accounts", data),

    deleteAccount: (id: number) => api.delete(`/api/monday-accounts/${id}`),

    testConnection: (id: number) => api.post(`/api/monday-accounts/${id}/test`),

    getBoards: (accountId: number) => api.get(`/api/monday-accounts/${accountId}/boards`),

    getGroups: (accountId: number, boardId: string) =>
        api.get(`/api/monday-accounts/${accountId}/boards/${boardId}/groups`),

    getItems: (accountId: number, boardId: string) =>
        api.get(`/api/monday-accounts/${accountId}/boards/${boardId}/items`),
};

/** Gantt Chart endpoints **/
export const ganttApi = {
    getGanttData: (data: {
        accounts: Array<{
            platform: string;
            account_id: number;
            list_ids: string[];
        }>;
    }) => api.post("/api/gantt/data", data),

    getAccountLists: (platform: string, accountId: number) =>
        api.get("/api/gantt/lists", { params: { platform, account_id: accountId } }),
};

/** Sync endpoints (all under /api/*) **/
export const syncApi = {
    getConfigurations: () => api.get("/api/sync-configurations"),

    createConfiguration: (data: any) => api.post("/api/sync-configurations", data),

    updateConfiguration: (id: number, data: any) =>
        api.put(`/api/sync-configurations/${id}`, data),

    deleteConfiguration: (id: number) =>
        api.delete(`/api/sync-configurations/${id}`),

    syncNow: (id: number) => api.post(`/api/sync-configurations/${id}/sync`),

    getSyncLogs: (id: number) => api.get(`/api/sync-configurations/${id}/logs`),

    testConnection: (id: number) => api.post(`/api/sync-configurations/${id}/test`),
};

export default api;
