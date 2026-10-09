import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { IMaskInput } from 'react-imask';
import { AddressFields, emptyAddress } from '@/components/address-fields';
import type { AddressValue } from '@/components/address-fields';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';

interface ClientRegisterForm {
    nome: string;
    recovery_email: string;
    password: string;
    password_confirmation: string;
}

type Props = {
    analystId: string;
    email: string;
    query: string;
};

export default function ClientRegister({ analystId, email, query }: Props) {
    const form = useForm<ClientRegisterForm>({
        nome: '',
        recovery_email: '',
        password: '',
        password_confirmation: '',
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        form.post(`/register/invite/${analystId}?${query}`, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Complete your registration" />

            <form onSubmit={submit} className="flex flex-col gap-6">
                <div className="grid gap-6">
                    <div className="grid gap-2">
                        <Label htmlFor="email">E-mail do convite</Label>
                        <Input
                            id="email"
                            type="email"
                            value={email}
                            readOnly
                            className="bg-muted/60"
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">E-mail de recuperação</Label>
                        <Input
                            id="recovery_email"
                            type="email"
                            value={form.data.recovery_email}
                            onChange={(event) =>
                                form.setData('recovery_email', event.target.value)
                            }
                            placeholder="E-mail de recuperação"
                            autoComplete="recovery_email"
                            autoFocus
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="nome">Nome completo</Label>
                        <Input
                            id="nome"
                            name="nome"
                            value={form.data.nome}
                            onChange={(event) =>
                                form.setData('nome', event.target.value)
                            }
                            placeholder="Seu nome completo"
                            autoComplete="name"
                            autoFocus
                        />
                        <InputError message={form.errors.nome} />
                    </div>


                    <div className="grid gap-2">
                        <Label htmlFor="password">Senha</Label>
                        <PasswordInput
                            id="password"
                            name="password"
                            value={form.data.password}
                            onChange={(event) =>
                                form.setData('password', event.target.value)
                            }
                            placeholder="Digite sua senha"
                            autoComplete="new-password"
                        />
                        <InputError message={form.errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            Confirmar senha
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            name="password_confirmation"
                            value={form.data.password_confirmation}
                            onChange={(event) =>
                                form.setData(
                                    'password_confirmation',
                                    event.target.value,
                                )
                            }
                            placeholder="Confirme sua senha"
                            autoComplete="new-password"
                        />
                        <InputError
                            message={form.errors.password_confirmation}
                        />
                    </div>

                    <Button
                        type="submit"
                        className="w-full"
                        disabled={form.processing}
                    >
                        {form.processing && <Spinner />}
                        Finalizar cadastro
                    </Button>
                </div>

                <div className="text-muted-foreground text-center text-sm">
                    Já possui conta?{' '}
                    <a
                        href={login().url}
                        className="underline underline-offset-4"
                    >
                        Fazer login
                    </a>
                </div>
            </form>
        </>
    );
}

ClientRegister.layout = {
    title: 'Complete seu cadastro',
    description: 'Cadastre sua conta usando o link de convite.',
};
