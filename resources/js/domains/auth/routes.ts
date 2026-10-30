import Login from './pages/Login.vue';
import ForgotPassword from './pages/ForgotPassword.vue';
import ResetPassword from './pages/ResetPassword.vue';
import Register from './pages/Register.vue';

export const authRoutes = [
    {
        path: '/register',
        name: 'auth.register',
        component: Register,
        meta: { guest: true },
    },
    {
        path: '/login',
        name: 'auth.login',
        component: Login,
        meta: { guest: true },
    },
    {
        path: '/forgot-password',
        name: 'auth.forgot',
        component: ForgotPassword,
        meta: { guest: true },
    },
    {
        path: '/reset-password/:token',
        name: 'auth.reset',
        component: ResetPassword,
        meta: { guest: true },
    },
];
