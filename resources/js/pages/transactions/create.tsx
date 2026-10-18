import { useEffect, useState } from 'react';
import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index as walletsIndex } from '@/routes/wallets';
import {
    create as createWalletTransaction,
    index as walletTransactionsIndex,
    store as storeWalletTransaction,
} from '@/routes/wallets/transactions';

type WalletOption = {
    id: string;
    name: string;
    sellableAssetIds: number[];
};

type AssetOption = {
    id: number;
    ticker: string;
    name: string;
};

type Props = {
    wallet: WalletOption;
    assets: AssetOption[];
    today: string;
};

export default function TransactionsCreate({ wallet, assets, today }: Props) {
    const [assetId, setAssetId] = useState('');
    const [type, setType] = useState<'buy' | 'sell'>('buy');
    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: 'Carteiras', href: walletsIndex() },
                {
                    title: wallet.name,
                    href: walletTransactionsIndex(wallet.id),
                },
                {
                    title: 'Registrar movimentação',
                    href: createWalletTransaction(wallet.id),
                },
            ],
        });
    }, [wallet.id, wallet.name]);

    const availableAssets =
        type === 'sell'
            ? assets.filter((asset) =>
                  wallet.sellableAssetIds.includes(asset.id),
              )
            : assets;

    return (
        <>
            <Head title="Registrar movimentação" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="max-w-2xl">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Registrar movimentação
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Carteira:{' '}
                        <span className="font-medium">{wallet.name}</span>. A
                        cotação atual do ativo será salva com a movimentação.
                    </p>
                </div>

                {assets.length === 0 ? (
                    <p className="border-border text-muted-foreground max-w-2xl rounded-xl border p-6 text-sm">
                        Nenhum ativo está cadastrado nesta carteira. Edite a
                        carteira para adicionar ativos antes de registrar uma
                        movimentação.
                    </p>
                ) : (
                    <div className="border-border bg-card max-w-2xl rounded-xl border p-6 shadow-sm">
                        <Form
                            {...storeWalletTransaction.form(wallet.id)}
                            className="flex flex-col gap-6"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="transaction_type">
                                            Tipo
                                        </Label>
                                        <select
                                            id="transaction_type"
                                            name="transaction_type"
                                            value={type}
                                            onChange={(event) => {
                                                setType(
                                                    event.target.value as
                                                        | 'buy'
                                                        | 'sell',
                                                );
                                                setAssetId('');
                                            }}
                                            required
                                            className="border-input bg-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2"
                                        >
                                            <option value="buy">Compra</option>
                                            <option value="sell">Venda</option>
                                        </select>
                                        <InputError
                                            message={errors.transaction_type}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="asset_id">Ativo</Label>
                                        <select
                                            id="asset_id"
                                            name="asset_id"
                                            value={assetId}
                                            onChange={(event) =>
                                                setAssetId(event.target.value)
                                            }
                                            required
                                            className="border-input bg-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2"
                                        >
                                            <option value="">
                                                Selecione um ativo
                                            </option>
                                            {availableAssets.map((asset) => (
                                                <option
                                                    key={asset.id}
                                                    value={asset.id}
                                                >
                                                    {asset.ticker} —{' '}
                                                    {asset.name}
                                                </option>
                                            ))}
                                        </select>
                                        {type === 'sell' &&
                                            availableAssets.length === 0 && (
                                                <p className="text-muted-foreground text-sm">
                                                    Esta carteira ainda não
                                                    possui ativos para venda.
                                                </p>
                                            )}
                                        <InputError message={errors.asset_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="quantity">
                                            Quantidade
                                        </Label>
                                        <Input
                                            id="quantity"
                                            name="quantity"
                                            type="number"
                                            min="1"
                                            step="1"
                                            required
                                        />
                                        <InputError message={errors.quantity} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="transaction_date">
                                            Data
                                        </Label>
                                        <Input
                                            id="transaction_date"
                                            name="transaction_date"
                                            type="date"
                                            min={today}
                                            max={today}
                                            defaultValue={today}
                                            required
                                        />
                                        <InputError
                                            message={errors.transaction_date}
                                        />
                                    </div>

                                    <div className="flex flex-wrap items-center gap-3">
                                        <Button
                                            type="submit"
                                            disabled={
                                                processing ||
                                                (type === 'sell' &&
                                                    availableAssets.length ===
                                                        0)
                                            }
                                        >
                                            {processing && <Spinner />}
                                            Registrar movimentação
                                        </Button>
                                        <Button
                                            asChild
                                            type="button"
                                            variant="ghost"
                                        >
                                            <Link
                                                href={walletTransactionsIndex(
                                                    wallet.id,
                                                )}
                                            >
                                                Cancelar
                                            </Link>
                                        </Button>
                                    </div>
                                </>
                            )}
                        </Form>
                    </div>
                )}
            </div>
        </>
    );
}
