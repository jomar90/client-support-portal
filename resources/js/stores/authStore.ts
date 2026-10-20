import axios from 'axios';
import { computed } from 'vue';

import http from '../services/http';
import { storeModuleFactory } from './storeModuleFactory';

export interface AuthUser {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone_number: string | null;
    admin: boolean;
}

export interface LoginCredentials {
    email: string;
    password: string;
}

interface AuthState {
    user: AuthUser | null;
    initialized: boolean;
}

const { state, actions } = storeModuleFactory<AuthState, {
    fetchUser: () => Promise<AuthUser | null>;
    login: (credentials: LoginCredentials) => Promise<AuthUser | null>;
    logout: () => Promise<void>;
    forgotPassword: (email: string) => Promise<string>;
}>(
    {
        user: null,
        initialized: false,
    },
    (state) => {
        const fetchUser = async (): Promise<AuthUser | null> => {
            try {
                const response = await http.get<AuthUser>('/api/user');

                state.user = response.data;
                state.initialized = true;

                return state.user;
            } catch (error) {
                if (
                    axios.isAxiosError(error)
                    && error.response?.status === 401
                ) {
                    state.user = null;
                    state.initialized = true;

                    return null;
                }

                throw error;
            }
        };

        const login = async (
            credentials: LoginCredentials,
        ): Promise<AuthUser | null> => {
            await http.get('/sanctum/csrf-cookie');

            await http.post('/login', credentials);

            return fetchUser();
        };

        const logout = async (): Promise<void> => {
            await http.post('/logout');

            state.user = null;
            state.initialized = true;
        };

        const forgotPassword = async (email: string): Promise<string> => {
            await http.get('/sanctum/csrf-cookie');

            const response = await http.post<{ message: string }>(
                '/forgot-password',
                { email },
            );

            return response.data.message;
        };

        return {
            fetchUser,
            login,
            logout,
            forgotPassword,
        };
    },
);

const getters = {
    isAuthenticated: computed(() => state.user !== null),
};

export const authStore = {
    state,
    getters,
    actions,
};


