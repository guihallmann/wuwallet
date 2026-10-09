import { Form, Head, usePage } from '@inertiajs/react';
import { Mail, Send } from 'lucide-react';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/analyst/invites';

export default function CreateInvite() {
    const { flash } = usePage().props;

    return (
        <>
            <Head title="Invite a client" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="max-w-2xl">
                    <h1 className="mt-2 text-3xl font-semibold tracking-tight">
                        Convide um novo cliente
                    </h1>
                    <p className="text-muted-foreground mt-2">
                        Envie um link de cadastro seguro para seus clientes.
                    </p>
                </div>

                <div className="border-border bg-card max-w-2xl rounded-xl border p-6 shadow-sm">
                    <Form
                        {...store.form()}
                        resetOnSuccess
                        className="flex flex-col gap-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                {flash.status && (
                                    <Alert>
                                        <Mail />
                                        <AlertDescription>
                                            {flash.status}
                                        </AlertDescription>
                                    </Alert>
                                )}

                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        Email do cliente
                                    </Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        placeholder="client@example.com"
                                        autoComplete="email"
                                        autoFocus
                                        required
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <Button
                                    type="submit"
                                    className="w-fit"
                                    disabled={processing}
                                >
                                    {processing ? <Spinner /> : <Send />}
                                    Convidar
                                </Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}

CreateInvite.layout = {
    breadcrumbs: [
        {
            title: 'Convidar cliente',
            href: store(),
        },
    ],
};
