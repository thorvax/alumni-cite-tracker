import type { Auth } from '@/types/auth';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            /** Set when a graduate is logged in with the alumni login. */
            alumni: { last_name: string | null } | null;
            [key: string]: unknown;
        };
    }
}
