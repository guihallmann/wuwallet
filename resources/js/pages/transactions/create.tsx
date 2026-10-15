import { useState } from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import TransactionController from '@/actions/App/Http/Controllers/TransactionController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    create as createWallet,
    index as walletsIndex,
} from '@/routes/wallets';
import { create as createTransaction } from '@/routes/transactions';

type WalletOption = {
    id: string;
    name: string;
    assetIds: number[];
    sellableAssetIds: number[];
};

type AssetOption = {
    id: number;
    ticker: string;
    company: string;
};

type Props = {
    wallets: WalletOption[];
    assets: AssetOption[];
    today: string;
};

export default function TransactionsCreate({ wallets, assets, today }: Props) {
    const [walletId, setWalletId] = useState(wallets[0]?.id ?? '');
    const [assetId, setAssetId] = useState('');
    const [type, setType] = useState<'buy' | 'sell'>('buy');
    const selectedWallet = wallets.find((wallet) => wallet.id === walletId);
    const availableAssets =
        type === 'sell'
            ? assets.filter((asset) =>
                  selectedWallet?.sellableAssetIds.includes(asset.id),
              )
            : assets;
    const selectedAsset = assets.find((asset) => String(asset.id) === assetId);
    const isNewPosition =
        type === 'buy' &&
        selectedAsset !== undefined &&
        !selectedWallet?.assetIds.includes(selectedAsset.id);

    return (
        <>
            <Head title="Registrar movimentação" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="max-w-2xl">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Registrar movimentação
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Registre uma compra ou venda de ativo em uma carteira. A
                        cotação atual cadastrada para o ativo será salva com a
                        movimentação.
                    </p>
                </div>

                {wallets.length === 0 ? (
                    <div className="border-border bg-card flex max-w-2xl flex-col gap-4 rounded-xl border p-6">
                        <p className="text-muted-foreground text-sm">
                            Crie uma carteira antes de registrar uma
                            movimentação.
                        </p>
                        <Button asChild className="w-fit">
                            <Link href={createWallet()}>Criar carteira</Link>
                        </Button>
                    </div>
                ) : assets.length === 0 ? (
                    <p className="border-border text-muted-foreground max-w-2xl rounded-xl border p-6 text-sm">
                        Nenhum ativo está cadastrado para movimentação.
                    </p>
                ) : (
                    <div className="border-border bg-card max-w-2xl rounded-xl border p-6 shadow-sm">
                        <Form
                            {...TransactionController.store.form()}
                            className="flex flex-col gap-6"
                        >
                            {({ errors, processing }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="wallet_id">
                                            Carteira
                                        </Label>
                                        <select
                                            id="wallet_id"
                                            name="wallet_id"
                                            value={walletId}
                                            onChange={(event) => {
                                                setWalletId(event.target.value);
                                                setAssetId('');
                                            }}
                                            required
                                            className="border-input bg-background focus-visible:ring-ring h-9 w-full rounded-md border px-3 text-sm shadow-xs outline-none focus-visible:ring-2"
                                        >
                                            {wallets.map((wallet) => (
                                                <option
                                                    key={wallet.id}
                                                    value={wallet.id}
                                                >
                                                    {wallet.name}
                                                </option>
                                            ))}
                                        </select>
                                        <InputError
                                            message={errors.wallet_id}
                                        />
                                    </div>

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
                                                    {asset.company}
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

                                    {isNewPosition && (
                                        <div className="grid gap-2">
                                            <Label htmlFor="target_percentage">
                                                Meta de alocação (%)
                                            </Label>
                                            <Input
                                                id="target_percentage"
                                                name="target_percentage"
                                                type="number"
                                                min="0.01"
                                                max="100"
                                                step="0.01"
                                                required
                                            />
                                            <p className="text-muted-foreground text-sm">
                                                Defina a meta desejada para este
                                                ativo na carteira.
                                            </p>
                                            <InputError
                                                message={
                                                    errors.target_percentage
                                                }
                                            />
                                        </div>
                                    )}

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
                                            <Link href={walletsIndex()}>
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

TransactionsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Carteiras',
            href: walletsIndex(),
        },
        {
            title: 'Registrar movimentação',
            href: createTransaction(),
        },
    ],
};
