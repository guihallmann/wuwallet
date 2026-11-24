import { Link, useForm } from '@inertiajs/react';
import { Calculator, ChevronDown, Info } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import { IMaskInput } from 'react-imask';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { cn, formatCurrency, formatPercentage } from '@/lib/utils';
import { show } from '@/routes/wallets';
import { create as createWalletTransaction } from '@/routes/wallets/transactions';

type SuggestionStatus =
    | 'buy'
    | 'above_target'
    | 'lot_too_expensive'
    | 'lower_priority'
    | 'unavailable';

export type BuySuggestionItem = {
    asset_id: number;
    ticker: string;
    name: string;
    status: SuggestionStatus;
    current_price: number;
    current_quantity: number;
    lots: number;
    quantity: number;
    cost: number;
    lot_cost: number;
    current_percentage: number;
    after_percentage: number;
    target_percentage: number;
};

export type BuySuggestion = {
    summary: {
        amount: number;
        spent: number;
        leftover: number;
        total_before: number;
        total_after: number;
        lot_size: number;
        cheapest_lot: { ticker: string; cost: number } | null;
        prices_updated_at: string | null;
    };
    items: BuySuggestionItem[];
};

type Props = {
    walletId: string;
    suggestion?: BuySuggestion | null;
    disabled?: boolean;
};

const skipReasons: Record<Exclude<SuggestionStatus, 'buy'>, string> = {
    above_target: 'Acima do objetivo',
    lot_too_expensive: 'Lote acima do valor',
    lower_priority: 'Menor prioridade',
    unavailable: 'Indisponível',
};

const quantityFormatter = new Intl.NumberFormat('pt-BR');

export default function BuySuggestionDialog({
    walletId,
    suggestion,
    disabled = false,
}: Props) {
    const [open, setOpen] = useState(false);
    const form = useForm<{ amount: string }>({ amount: '' });

    function submit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        form.get(show.url(walletId), {
            only: ['suggestion'],
            preserveState: true,
            preserveScroll: true,
            preserveUrl: true,
        });
    }

    function handleOpenChange(value: boolean) {
        setOpen(value);

        if (!value) {
            form.clearErrors();
        }
    }

    const buys =
        suggestion?.items.filter((item) => item.status === 'buy') ?? [];
    const skipped =
        suggestion?.items.filter((item) => item.status !== 'buy') ?? [];

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogTrigger asChild>
                <Button disabled={disabled}>
                    <Calculator />
                    Sugerir compra
                </Button>
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Sugestão de compra</DialogTitle>
                    <DialogDescription>
                        Informe quanto deseja investir. Calculamos quantos lotes
                        de 100 ações comprar de cada ativo para deixar a
                        carteira o mais próxima possível dos percentuais
                        definidos como objetivo.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={submit} className="grid gap-2">
                    <Label htmlFor="amount">Valor do aporte</Label>
                    <div className="flex gap-2">
                        <div className="relative flex-1">
                            <span className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-sm">
                                R$
                            </span>
                            <IMaskInput
                                id="amount"
                                mask={Number}
                                scale={2}
                                radix=","
                                mapToRadix={['.']}
                                thousandsSeparator="."
                                padFractionalZeros
                                normalizeZeros
                                min={0}
                                max={100000000}
                                unmask
                                value={form.data.amount}
                                onAccept={(value: string) =>
                                    form.setData('amount', value)
                                }
                                inputMode="decimal"
                                placeholder="0,00"
                                autoFocus
                                aria-invalid={Boolean(form.errors.amount)}
                                className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:border-destructive flex h-9 w-full rounded-md border bg-transparent py-1 pr-3 pl-10 text-base shadow-xs outline-none focus-visible:ring-[3px] md:text-sm"
                            />
                        </div>
                        <Button
                            type="submit"
                            disabled={
                                form.processing || form.data.amount === ''
                            }
                        >
                            {form.processing ? <Spinner /> : <Calculator />}
                            Calcular
                        </Button>
                    </div>
                    <InputError message={form.errors.amount} />
                </form>

                {suggestion && (
                    <div
                        aria-busy={form.processing}
                        className={cn(
                            'grid gap-5 transition-opacity',
                            form.processing && 'opacity-60',
                        )}
                    >
                        <dl className="grid gap-3 sm:grid-cols-3">
                            <SummaryTile
                                label="Total sugerido"
                                value={formatCurrency(suggestion.summary.spent)}
                                hint={`de ${formatCurrency(suggestion.summary.amount)} informados`}
                            />
                            <SummaryTile
                                label="Sobra"
                                value={formatCurrency(
                                    suggestion.summary.leftover,
                                )}
                                hint="não compra mais nenhum lote"
                            />
                            <SummaryTile
                                label="Patrimônio após aporte"
                                value={formatCurrency(
                                    suggestion.summary.total_after,
                                )}
                                hint={`hoje: ${formatCurrency(suggestion.summary.total_before)}`}
                            />
                        </dl>

                        {buys.length === 0 ? (
                            <Alert>
                                <Info />
                                <AlertTitle>
                                    Nenhum lote cabe neste valor
                                </AlertTitle>
                                <AlertDescription>
                                    {suggestion.summary.cheapest_lot
                                        ? `O lote mais barato desta carteira é ${suggestion.summary.cheapest_lot.ticker}: ${formatCurrency(suggestion.summary.cheapest_lot.cost)} por ${suggestion.summary.lot_size} ações.`
                                        : 'Nenhum ativo desta carteira está disponível para negociação no momento.'}
                                </AlertDescription>
                            </Alert>
                        ) : (
                            <section className="grid gap-3">
                                <h3 className="text-sm font-medium">
                                    O que comprar
                                </h3>
                                <ol className="grid gap-3">
                                    {buys.map((item) => (
                                        <BuyRow
                                            key={item.asset_id}
                                            item={item}
                                            walletId={walletId}
                                        />
                                    ))}
                                </ol>
                            </section>
                        )}

                        {skipped.length > 0 && (
                            <Collapsible>
                                <CollapsibleTrigger className="text-muted-foreground hover:text-foreground group flex items-center gap-1 text-sm font-medium">
                                    <ChevronDown className="size-4 transition-transform group-data-[state=open]:rotate-180" />
                                    Sem compra sugerida ({skipped.length})
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <ul className="divide-border mt-2 divide-y rounded-lg border">
                                        {skipped.map((item) => (
                                            <li
                                                key={item.asset_id}
                                                className="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm"
                                            >
                                                <div className="min-w-0">
                                                    <span className="font-medium">
                                                        {item.ticker}
                                                    </span>
                                                    <span className="text-muted-foreground ml-2">
                                                        Atual{' '}
                                                        {formatPercentage(
                                                            item.current_percentage,
                                                        )}{' '}
                                                        · Objetivo{' '}
                                                        {formatPercentage(
                                                            item.target_percentage,
                                                        )}
                                                    </span>
                                                </div>
                                                <Badge
                                                    variant={
                                                        item.status ===
                                                        'unavailable'
                                                            ? 'outline'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {
                                                        skipReasons[
                                                            item.status as Exclude<
                                                                SuggestionStatus,
                                                                'buy'
                                                            >
                                                        ]
                                                    }
                                                </Badge>
                                            </li>
                                        ))}
                                    </ul>
                                </CollapsibleContent>
                            </Collapsible>
                        )}

                        <p className="text-muted-foreground text-xs">
                            Compras em lotes padrão de{' '}
                            {suggestion.summary.lot_size} ações, pela última
                            cotação
                            {suggestion.summary.prices_updated_at &&
                                ` (atualizada em ${new Date(suggestion.summary.prices_updated_at).toLocaleString('pt-BR')})`}
                            . É apenas uma sugestão: nada é registrado até você
                            confirmar a movimentação.
                        </p>
                    </div>
                )}
            </DialogContent>
        </Dialog>
    );
}

function SummaryTile({
    label,
    value,
    hint,
}: {
    label: string;
    value: string;
    hint: string;
}) {
    return (
        <div className="bg-muted/40 rounded-lg border p-3">
            <dt className="text-muted-foreground text-xs">{label}</dt>
            <dd className="mt-1 text-lg font-semibold">{value}</dd>
            <dd className="text-muted-foreground text-xs">{hint}</dd>
        </div>
    );
}

function BuyRow({
    item,
    walletId,
}: {
    item: BuySuggestionItem;
    walletId: string;
}) {
    return (
        <li className="rounded-lg border p-4">
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="text-sm">
                        <span className="font-semibold">{item.ticker}</span>
                        <span className="text-muted-foreground ml-2">
                            {item.name}
                        </span>
                    </p>
                    <p className="mt-1 text-lg font-semibold">
                        Comprar {quantityFormatter.format(item.quantity)} ações
                    </p>
                    <p className="text-muted-foreground text-sm">
                        {item.lots} {item.lots === 1 ? 'lote' : 'lotes'} ×{' '}
                        {formatCurrency(item.lot_cost)} (cotação{' '}
                        {formatCurrency(item.current_price)})
                    </p>
                </div>
                <div className="flex flex-col items-end gap-2">
                    <p className="text-lg font-semibold">
                        {formatCurrency(item.cost)}
                    </p>
                    <Button asChild size="sm" variant="outline">
                        <Link
                            href={createWalletTransaction.url(walletId, {
                                query: {
                                    asset_id: item.asset_id,
                                    quantity: item.quantity,
                                },
                            })}
                        >
                            Registrar compra
                        </Link>
                    </Button>
                </div>
            </div>
            <AllocationBar item={item} />
        </li>
    );
}

function AllocationBar({ item }: { item: BuySuggestionItem }) {
    const scale =
        Math.max(item.target_percentage, item.after_percentage, 1) * 1.15;
    const toWidth = (value: number) => `${Math.min(value / scale, 1) * 100}%`;

    return (
        <div className="mt-4 grid gap-1.5">
            <div
                className="bg-muted relative h-2 rounded-full"
                role="img"
                aria-label={`Participação atual ${formatPercentage(item.current_percentage)}, após a compra ${formatPercentage(item.after_percentage)}, objetivo ${formatPercentage(item.target_percentage)}`}
            >
                <div
                    className="bg-primary/80 absolute inset-y-0 left-0 rounded-full"
                    style={{ width: toWidth(item.after_percentage) }}
                />
                <div
                    className="bg-muted-foreground/60 absolute inset-y-0 left-0 rounded-full"
                    style={{ width: toWidth(item.current_percentage) }}
                />
                <div
                    className="bg-foreground absolute -inset-y-1 w-0.5"
                    style={{ left: toWidth(item.target_percentage) }}
                />
            </div>
            <p className="text-muted-foreground text-xs">
                Atual {formatPercentage(item.current_percentage)} →{' '}
                <span className="text-foreground font-medium">
                    Após {formatPercentage(item.after_percentage)}
                </span>{' '}
                · Objetivo {formatPercentage(item.target_percentage)}
            </p>
        </div>
    );
}
