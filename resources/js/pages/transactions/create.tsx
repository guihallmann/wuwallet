import { useEffect, useRef, useState } from 'react';
import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { formatCurrency } from '@/lib/utils';
import { index as walletsIndex, show as walletsShow } from '@/routes/wallets';
import {
    create as createWalletTransaction,
    index as walletTransactionsIndex,
    price as walletTransactionPrice,
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
    current_price: number;
    current_quantity: number;
};

type Props = {
    wallet: WalletOption;
    assets: AssetOption[];
    prefill: { asset_id: number; quantity: number } | null;
    today: string;
};

export default function TransactionsCreate({
    wallet,
    assets,
    prefill,
    today,
}: Props) {
    const [assetId, setAssetId] = useState(
        prefill ? String(prefill.asset_id) : '',
    );
    const [type, setType] = useState<'buy' | 'sell'>('buy');
    const [quantity, setQuantity] = useState(
        prefill ? String(prefill.quantity) : '',
    );
    const [date, setDate] = useState(today);
    const [unitPrice, setUnitPrice] = useState('');
    const manualPrice = useRef(false);
    const [priceStatus, setPriceStatus] = useState<
        'idle' | 'loading' | 'current' | 'historical' | 'unavailable'
    >('idle');
    useEffect(() => {
        setLayoutProps({
            breadcrumbs: [
                { title: 'Carteiras', href: walletsIndex() },
                {
                    title: wallet.name,
                    href: walletsShow(wallet.id),
                },
                {
                    title: 'Movimentações',
                    href: walletTransactionsIndex(wallet.id),
                },
                {
                    title: 'Registrar movimentação',
                    href: createWalletTransaction(wallet.id),
                },
            ],
        });
    }, [wallet.id, wallet.name]);

    const selectedAsset = assets.find((asset) => asset.id === Number(assetId));
    const selectedAssetId = selectedAsset?.id;
    const currentPrice = selectedAsset?.current_price;

    useEffect(() => {
        if (selectedAssetId === undefined || currentPrice === undefined) {
            manualPrice.current = false;
            setUnitPrice('');
            setPriceStatus('idle');

            return;
        }

        if (date === today) {
            manualPrice.current = false;
            setUnitPrice(currentPrice.toFixed(2));
            setPriceStatus('current');

            return;
        }

        const controller = new AbortController();
        manualPrice.current = false;
        setUnitPrice('');
        setPriceStatus('loading');

        const loadPrice = async () => {
            try {
                const response = await fetch(
                    walletTransactionPrice.url(wallet.id, {
                        query: { asset_id: selectedAssetId, date },
                    }),
                    {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    throw new Error('Historical quote unavailable');
                }

                const body: unknown = await response.json();
                const value =
                    body !== null &&
                    typeof body === 'object' &&
                    'unit_price' in body
                        ? body.unit_price
                        : null;

                if (controller.signal.aborted) {
                    return;
                }

                if (manualPrice.current) {
                    return;
                }

                if (
                    typeof value === 'number' &&
                    Number.isFinite(value) &&
                    value > 0
                ) {
                    setUnitPrice(value.toFixed(2));
                    setPriceStatus('historical');
                } else {
                    setPriceStatus('unavailable');
                }
            } catch {
                if (!controller.signal.aborted) {
                    setPriceStatus('unavailable');
                }
            }
        };

        void loadPrice();

        return () => controller.abort();
    }, [currentPrice, date, selectedAssetId, today, wallet.id]);

    const availableAssets =
        type === 'sell'
            ? assets.filter((asset) =>
                  wallet.sellableAssetIds.includes(asset.id),
              )
            : assets;
    const sellableQuantity = selectedAsset
        ? Math.floor(selectedAsset.current_quantity / 100) * 100
        : 0;
    const estimatedValue = Number(quantity) * Number(unitPrice);

    return (
        <>
            <Head title="Registrar movimentação" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="max-w-2xl">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Registrar movimentação
                    </h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Informe a data e o preço unitário praticado. Carteira:{' '}
                        <span className="font-medium">{wallet.name}</span>.
                    </p>
                </div>

                {assets.length === 0 ? (
                    <p className="border-border text-muted-foreground max-w-2xl rounded-xl border p-6 text-sm">
                        Nenhum ativo disponível para movimentação está
                        cadastrado nesta carteira. Ativos inativos e tickers
                        fracionários não podem ser movimentados.
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
                                                setQuantity('');
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
                                            onChange={(event) => {
                                                setAssetId(event.target.value);
                                                setQuantity('');
                                                setUnitPrice('');
                                            }}
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
                                                    Esta carteira não possui
                                                    lotes de 100 ações
                                                    disponíveis para venda.
                                                </p>
                                            )}
                                        <InputError message={errors.asset_id} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="unit_price">
                                            Preço unitário da operação
                                        </Label>
                                        <Input
                                            id="unit_price"
                                            name="unit_price"
                                            type="number"
                                            min="0.01"
                                            step="0.01"
                                            value={unitPrice}
                                            onChange={(event) => {
                                                manualPrice.current = true;
                                                setUnitPrice(
                                                    event.target.value,
                                                );
                                            }}
                                            required
                                        />
                                        {priceStatus === 'loading' && (
                                            <p className="text-muted-foreground text-sm">
                                                Consultando cotação histórica...
                                            </p>
                                        )}
                                        {priceStatus === 'current' && (
                                            <p className="text-muted-foreground text-sm">
                                                Cotação atual sugerida. Ajuste
                                                para o preço efetivamente
                                                praticado.
                                            </p>
                                        )}
                                        {priceStatus === 'historical' && (
                                            <p className="text-muted-foreground text-sm">
                                                Fechamento histórico sugerido.
                                                Ajuste para o preço efetivamente
                                                praticado.
                                            </p>
                                        )}
                                        {priceStatus === 'unavailable' && (
                                            <p className="text-muted-foreground text-sm">
                                                Cotação histórica indisponível.
                                                Informe o preço unitário da
                                                operação.
                                            </p>
                                        )}
                                        <InputError
                                            message={errors.unit_price}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <div className="flex items-center justify-between gap-2">
                                            <Label htmlFor="quantity">
                                                Quantidade
                                            </Label>
                                            {type === 'sell' &&
                                                selectedAsset && (
                                                    <span className="text-muted-foreground text-sm">
                                                        Saldo em custódia:{' '}
                                                        {
                                                            selectedAsset.current_quantity
                                                        }
                                                    </span>
                                                )}
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                id="quantity"
                                                name="quantity"
                                                type="number"
                                                min="100"
                                                max={
                                                    type === 'sell'
                                                        ? sellableQuantity
                                                        : undefined
                                                }
                                                step="100"
                                                value={quantity}
                                                onChange={(event) =>
                                                    setQuantity(
                                                        event.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            {type === 'sell' &&
                                                selectedAsset && (
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        onClick={() =>
                                                            setQuantity(
                                                                String(
                                                                    sellableQuantity,
                                                                ),
                                                            )
                                                        }
                                                    >
                                                        Adicionar tudo
                                                    </Button>
                                                )}
                                        </div>
                                        {type === 'sell' && selectedAsset && (
                                            <p className="text-muted-foreground text-sm">
                                                Disponível para venda em lotes
                                                de 100: {sellableQuantity}
                                            </p>
                                        )}
                                        {selectedAsset &&
                                            Number.isFinite(estimatedValue) &&
                                            estimatedValue > 0 && (
                                                <p className="text-muted-foreground text-sm">
                                                    Valor estimado:{' '}
                                                    {formatCurrency(
                                                        estimatedValue,
                                                    )}
                                                </p>
                                            )}
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
                                            max={today}
                                            value={date}
                                            onChange={(event) => {
                                                setDate(event.target.value);
                                                setUnitPrice('');
                                            }}
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
