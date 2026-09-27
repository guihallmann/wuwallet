import { Head, Link } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/users';

type ManagedUser = {
    id: string;
    name: string;
    email: string;
    cpf: string | null;
active: boolean;
};

type Props = {
    users: ManagedUser[];
    managedRole: 'analyst' | 'client';
};

const copy = {
    analyst: {
        title: 'Analistas',
        description: 'Gerencie seus analistas.',
        newLabel: 'Novo analista',
        empty: 'Nenhum analista cadastrado.',
    },
    client: {
        title: 'Clientes',
        description: 'Gerencie seus clientes.',
        newLabel: 'Novo cliente',
        empty: 'Nenhum cliente cadastrado.',
    },
} as const;

export default function UsersIndex({ users, managedRole }: Props) {
    const text = copy[managedRole];

    return (
        <>
            <Head title={text.title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {text.title}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            {text.description}
                        </p>
                    </div>

                    {managedRole === 'analyst' && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                {text.newLabel}
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="border-border overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground">
                            <tr className="text-left">
                                <th className="px-4 py-3 font-medium">Nome</th>
                                <th className="px-4 py-3 font-medium">Email</th>
                                {managedRole === 'client' && (
                                    <th className="px-4 py-3 font-medium">
                                        CPF
                                    </th>
                                )}
                                <th className="px-4 py-3 font-medium">Status</th>
                                <th className="px-4 py-3 font-medium text-right">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {users.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={managedRole === 'client' ? 5 : 4}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        {text.empty}
                                    </td>
                                </tr>
                            ) : (
                                users.map((user) => (
                                    <tr key={user.id} className="hover:bg-muted/30">
                                        <td className="px-4 py-3 font-medium">
                                            {user.name}
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {user.email}
                                        </td>
                                        {managedRole === 'client' && (
                                            <td className="text-muted-foreground px-4 py-3">
                                                {user.cpf}
                                            </td>
                                        )}
                                        <td className="px-4 py-3">
                                            <Badge
                                                variant={
                                                    user.active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {user.active
                                                    ? 'Ativo'
                                                    : 'Desativado'}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                asChild
                                                variant="outline"
                                                size="sm"
                                            >
                                                <Link href={edit(user.id)}>
                                                    Editar
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Usuários',
            href: index(),
        },
    ],
};
