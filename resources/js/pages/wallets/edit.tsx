import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import WalletController from '@/actions/App/Http/Controllers/WalletController';
import AssetAllocationField, {
    isAllocationComplete,
} from '@/components/asset-allocation-field';
import type { AllocationRow } from '@/components/asset-allocation-field';
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

// Ativo já salvo na carteira (formato previsto no plano: asset_id, ticker, name, target_percentage)
type WalletAsset = {
    asset_id: number;
    ticker: string;
    name: string;
    target_percentage: string | number;
    current_quantity: number;
};

type Wallet = {
    id: string;
    name: string;
    objective_text: string | null;
    // opcional por enquanto: enquanto o backend não mandar, a lista começa vazia
    assets?: WalletAsset[];
};

type Props = {
    wallet: Wallet;
    sectors?: string[];
};

export default function WalletsEdit({ wallet, sectors = [] }: Props) {
    // Começa com os ativos que a carteira já tem (o backend manda em wallet.assets)
    const [rows, setRows] = useState<AllocationRow[]>(() =>
        (wallet.assets ?? []).map((asset) => ({
            asset_id: asset.asset_id,
            ticker: asset.ticker,
            name: asset.name,
            target_percentage: Number(asset.target_percentage).toFixed(2),
            current_quantity: asset.current_quantity,
        })),
    );

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

                                <AssetAllocationField
                                    rows={rows}
                                    onChange={setRows}
                                    sectors={sectors}
                                    errors={errors}
                                />

                                <div className="flex items-center gap-3">
                                    <Button
                                        type="submit"
                                        disabled={
                                            processing ||
                                            !isAllocationComplete(rows)
                                        }
                                    >
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
