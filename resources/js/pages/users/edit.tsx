import { Form, Head, Link } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
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
    tax_id: string;
    active: boolean;
};

type Props = {
    user: ManagedUser;
};

export default function UsersEdit({ user }: Props) {
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

                                <div className="grid gap-2">
                                    <Label htmlFor="tax_id">CPF</Label>
                                    <Input
                                        id="tax_id"
                                        name="tax_id"
                                        defaultValue={user.tax_id}
                                        inputMode="numeric"
                                        maxLength={11}
                                        required
                                    />
                                    <InputError message={errors.tax_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password">
                                        Nova senha
                                    </Label>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        autoComplete="new-password"
                                        placeholder="Leave blank to keep current"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirmar nova senha
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autoComplete="new-password"
                                    />
                                </div>

                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="active"
                                        name="active"
                                        value="1"
                                        defaultChecked={user.active}
                                    />
                                    <Label htmlFor="active">
                                        Conta ativa
                                    </Label>
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
