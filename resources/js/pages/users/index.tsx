import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { formatCurrency } from '@/lib/utils';
import { index as clientWalletsIndex } from '@/routes/clients/wallets';
import { create, edit, index } from '@/routes/users';

type ManagedUser = {
    id: string;
    name: string;
    email: string;
    active: boolean;
};

type Summary = {
    totalPatrimony: number;
    totalClients: number;
    activeClients: number;
};

type Props = {
    users: ManagedUser[];
    managedRole: 'analyst' | 'client';
    summary: Summary | null;
    filters: { search: string };
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

export default function UsersIndex({
    users,
    managedRole,
    summary,
    filters,
}: Props) {
    const text = copy[managedRole];
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        const timeout = setTimeout(() => {
            if (search !== filters.search) {
                router.get(index(), search ? { search } : {}, {
                    preserveState: true,
                    replace: true,
                });
            }
        }, 300);

        return () => clearTimeout(timeout);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

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

                {summary && (
                    <div className="grid gap-4 sm:grid-cols-3">
                        <Card>
                            <CardContent className="flex flex-col gap-1">
                                <CardDescription>
                                    Patrimônio total
                                </CardDescription>
                                <CardTitle className="text-2xl">
                                    {formatCurrency(summary.totalPatrimony)}
                                </CardTitle>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="flex flex-col gap-1">
                                <CardDescription>
                                    Total de clientes
                                </CardDescription>
                                <CardTitle className="text-2xl">
                                    {summary.totalClients}
                                </CardTitle>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="flex flex-col gap-1">
                                <CardDescription>
                                    Clientes ativos
                                </CardDescription>
                                <CardTitle className="text-2xl">
                                    {summary.activeClients}
                                </CardTitle>
                            </CardContent>
                        </Card>
                    </div>
                )}

                <Input
                    value={search}
                    onChange={(event) => setSearch(event.target.value)}
                    placeholder={`Buscar ${text.title.toLowerCase()} por nome...`}
                    className="max-w-sm"
                />

                <div className="border-border overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground">
                            <tr className="text-left">
                                <th className="px-4 py-3 font-medium">Nome</th>
                                <th className="px-4 py-3 font-medium">Email</th>
                                <th className="px-4 py-3 font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Ações
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {users.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={
                                            managedRole === 'client' ? 5 : 4
                                        }
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        {text.empty}
                                    </td>
                                </tr>
                            ) : (
                                users.map((user) => (
                                    <tr
                                        key={user.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {user.name}
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {user.email}
                                        </td>
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
                                            <div className="flex justify-end gap-2">
                                                {managedRole === 'client' && (
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        <Link
                                                            href={clientWalletsIndex(
                                                                user.id,
                                                            )}
                                                        >
                                                            Ver carteiras
                                                        </Link>
                                                    </Button>
                                                )}
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link href={edit(user.id)}>
                                                        Editar
                                                    </Link>
                                                </Button>
                                            </div>
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
