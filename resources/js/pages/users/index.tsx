import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import users from '@/routes/users';

export default function UsersIndex() {
    return (
        <>
            <Head title="Users" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Users"
                    description="Manage application users"
                />
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: users.index(),
        },
    ],
};