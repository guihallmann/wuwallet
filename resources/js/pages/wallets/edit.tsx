import { Form, Head, Link } from '@inertiajs/react';
import WalletController from '@/actions/App/Http/Controllers/WalletController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/wallets';

type Wallet = {
    id: string;
    name: string;
    objective_text: string | null;
};

type Props = {
    wallet: Wallet;
};

export default function WalletsEdit({ wallet }: Props) {
    return (
        <>
            <Head title={`Editar ${wallet.name}`} />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="max-w-2xl">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Editar carteira
                    </h1>
                </div>

                <div className="border-border bg-card max-w-2xl rounded-xl border p-6 shadow-sm">
                    <Form
                        {...WalletController.update.form(wallet.id)}
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
                                        defaultValue={wallet.name}
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="objective_text">
                                        Objetivo
                                    </Label>
                                    <Textarea
                                        id="objective_text"
                                        name="objective_text"
                                        defaultValue={
                                            wallet.objective_text ?? ''
                                        }
                                    />
                                    <InputError
                                        message={errors.objective_text}
                                    />
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

                <div className="max-w-2xl">
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="destructive">
                                Excluir carteira
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogTitle>
                                Excluir &quot;{wallet.name}&quot;?
                            </DialogTitle>
                            <DialogDescription>
                                Esta ação não pode ser desfeita. Os ativos e
                                movimentações desta carteira também serão
                                excluídos.
                            </DialogDescription>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">
                                        Cancelar
                                    </Button>
                                </DialogClose>

                                <Form
                                    {...WalletController.destroy.form(
                                        wallet.id,
                                    )}
                                >
                                    {({ processing }) => (
                                        <Button
                                            type="submit"
                                            variant="destructive"
                                            disabled={processing}
                                        >
                                            {processing && <Spinner />}
                                            Excluir carteira
                                        </Button>
                                    )}
                                </Form>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>
        </>
    );
}

WalletsEdit.layout = {
    breadcrumbs: [
        {
            title: 'Carteiras',
            href: index(),
        },
    ],
};
