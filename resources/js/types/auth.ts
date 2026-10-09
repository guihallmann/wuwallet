export type User = {
    id: string;
    name: string;
    email: string;
    role: 'manager' | 'analyst' | 'client';
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
