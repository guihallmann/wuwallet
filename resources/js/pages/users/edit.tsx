import { Form, Head, Link } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/users';

type ManagedUser = {
    id: string;
    name: string;
    email: string;
    recovery_email: string;
    active: boolean;
};

type Props = {
    user: ManagedUser;
    managedRole: 'analyst' | 'client';
};

export default function UsersEdit({ user, managedRole }: Props) {
    return (
        <>
            <Head title={`Edit ${user.name}`} />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="max-w-2xl">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Editar usuário
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Atualize informações ou ative/desative esta conta.
                    </p>
                </div>

                <div className="border-border bg-card max-w-2xl rounded-xl border p-6 shadow-sm">
                    <Form
                        {...UserController.update.form(user.id)}
                        options={{ preserveScroll: true }}
                        className="flex flex-col gap-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nome</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        defaultValue={user.name}
                                        autoComplete="name"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={user.email}
                                        autoComplete="email"
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                {managedRole === 'client' && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            Email de recuperação
                                        </Label>
                                        <Input
                                            id="recovery_email"
                                            name="recovery_email"
                                            type="recovery_email"
                                            defaultValue={user.recovery_email}
                                            autoComplete="recovery_email"
                                        />
                                        <InputError
                                            message={errors.recovery_email}
                                        />
                                    </div>
                                )}

                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="active"
                                        name="active"
                                        value="1"
                                        defaultChecked={user.active}
                                    />
                                    <Label htmlFor="active">Conta ativa</Label>
                                </div>

                                <div className="flex items-center gap-3">
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        Salvar
                                    </Button>
                                    <Button
                                        asChild
                                        type="button"
                                        variant="ghost"
                                    >
                                        <Link href={index()}>Cancelar</Link>
                                    </Button>
                                </div>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

UsersEdit.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
    ],
};
