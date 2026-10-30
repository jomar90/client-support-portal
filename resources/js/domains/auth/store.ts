import { computed, ref } from 'vue';
import axios from 'axios';
import { getRequest, postRequest } from '../../services/http';
import { clearErrors } from '../../services/error';
import { ticketStore } from '../tickets/store';
import { categoryStore } from '../categories/store';
import { administratorStore } from '../administrators/store';
import { userStore } from '../users/store';
import type { User, ResetPasswordData, RegistrationData } from './types';

const user = ref<User | null>(null);
export const authStore = {
    getters: {
        user: computed(() => user.value),
        isAdmin: computed(() => user.value?.admin === true),
    },
    actions: {
        async getUser() {
            try {
                const { data } = await getRequest<User>('/api/user');
                user.value = data;
            } catch (error) {
                if (axios.isAxiosError(error) && error.response?.status === 401) {
                    // Not being logged in is expected when opening the login page.
                    user.value = null;
                    clearErrors();
                    return;
                }

                throw error;
            }
        },
        async login(email: string, password: string) {
            await getRequest('/sanctum/csrf-cookie');
            await postRequest('/login', { email, password });
            // The router loads the user before opening the ticket overview.
        },
        async logout() {
            await postRequest('/logout');
            user.value = null;
            ticketStore.setters.setAll([]);
            categoryStore.setters.setAll([]);
            administratorStore.setters.setAll([]);
            userStore.setters.setAll([]);
        },
        async register(form: RegistrationData) {
            await getRequest('/sanctum/csrf-cookie');
            const { data } = await postRequest<{ message: string }>('/register', form);
            return data.message;
        },
        async forgotPassword(email: string) {
            await getRequest('/sanctum/csrf-cookie');
            const { data } = await postRequest<{ message: string }>('/forgot-password', { email });
            return data.message;
        },
        async resetPassword(form: ResetPasswordData) {
            await getRequest('/sanctum/csrf-cookie');
            await postRequest('/reset-password', form);
        },
    },
};
