import { Form, Head, Link } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import InputError from '@/components/input-error';
import { MaskedInput } from '@/components/masked-input';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index } from '@/routes/users';

type Props = {
    managedRole: 'analyst' | 'client';
};

const copy = {
    analyst: { title: 'Novo analista', submit: 'Cadastrar analista' },
    client: { title: 'Novo cliente', submit: 'Cadastrar cliente' },
} as const;

export default function UsersCreate({ managedRole }: Props) {
    const text = copy[managedRole];

    return (
        <>
            <Head title={text.title} />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="max-w-2xl">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        {text.title}
                    </h1>
                </div>

                <div className="border-border bg-card max-w-2xl rounded-xl border p-6 shadow-sm">
                    <Form
                        {...UserController.store.form()}
                        className="flex flex-col gap-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nome</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        autoComplete="name"
                                        autoFocus
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
                                        autoComplete="email"
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                {managedRole === 'client' && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="cpf">CPF</Label>
                                        <MaskedInput
                                            id="cpf"
                                            name="cpf"
                                            mask="000.000.000-00"
                                            inputMode="numeric"
                                            placeholder="000.000.000-00"
                                            required
                                        />
                                        <InputError message={errors.cpf} />
                                    </div>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="password">Senha</Label>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        autoComplete="new-password"
                                        required
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="password_confirmation">
                                        Confirmar senha
                                    </Label>
                                    <PasswordInput
                                        id="password_confirmation"
                                        name="password_confirmation"
                                        autoComplete="new-password"
                                        required
                                    />
                                </div>

                                <div className="flex items-center gap-3">
                                    <Checkbox
                                        id="active"
                                        name="active"
                                        value="1"
                                        defaultChecked
                                    />
                                    <Label htmlFor="active">
                                        Conta ativa
                                    </Label>
                                </div>

                                <div className="flex items-center gap-3">
                                    <Button type="submit" disabled={processing}>
                                        {processing && <Spinner />}
                                        {text.submit}
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

UsersCreate.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
    ],
};
