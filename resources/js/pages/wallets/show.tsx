import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Pencil, Repeat2 } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Cell, Pie, PieChart, ResponsiveContainer } from 'recharts';
import { Button } from '@/components/ui/button';
import BuySuggestionDialog from '@/components/wallets/buy-suggestion-dialog';
import type { BuySuggestion } from '@/components/wallets/buy-suggestion-dialog';
import { formatCurrency, formatPercentage } from '@/lib/utils';
import { edit, index, show } from '@/routes/wallets';
import { index as walletTransactionsIndex } from '@/routes/wallets/transactions';

type WalletAsset = {
    ticker: string;
    name: string;
    sector: string | null;
    current_price: number;
    current_quantity: number;
    value: number;
    target_percentage: number;
    current_percentage: number;
};

type Props = {
    wallet: {
        id: string;
        name: string;
        objective_text: string | null;
        total_value: number;
        assets: WalletAsset[];
    };
    suggestion?: BuySuggestion | null;
};

const allocationChartColors = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

export default function WalletsShow({ wallet, suggestion }: Props) {
    const [sectorFilter, setSectorFilter] = useState('');
    const sectors = useMemo(
        () =>
            Array.from(
                new Set(
                    wallet.assets
                        .map((asset) => asset.sector)
                        .filter((sector): sector is string => Boolean(sector)),
                ),
            ).sort((left, right) => left.localeCompare(right, 'pt-BR')),
        [wallet.assets],
    );
    const visibleAssets = sectorFilter
        ? wallet.assets.filter((asset) => asset.sector === sectorFilter)
        : wallet.assets;
    const allocation = wallet.assets.map((asset, index) => ({
        ...asset,
        chartColor:
            allocationChartColors[index % allocationChartColors.length],
    }));
    const chartAssets = allocation.filter((asset) => asset.value > 0);

    setLayoutProps({
        breadcrumbs: [
            { title: 'Carteiras', href: index() },
            { title: wallet.name, href: show(wallet.id) },
        ],
    });

    return (
        <>
            <Head title={wallet.name} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {wallet.name}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Patrimônio total:{' '}
                            {formatCurrency(wallet.total_value)}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <Link href={edit(wallet.id)}>
                                <Pencil />
                                Editar
                            </Link>
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={walletTransactionsIndex(wallet.id)}>
                                <Repeat2 />
                                Movimentações
                            </Link>
                        </Button>
                        <BuySuggestionDialog
                            walletId={wallet.id}
                            suggestion={suggestion}
                            disabled={wallet.assets.length === 0}
                        />
                    </div>
                </div>

                <div className="border-border rounded-xl border p-4">
                    <h2 className="text-sm font-medium">Objetivo</h2>
                    <p className="text-muted-foreground mt-2 text-sm wrap-break-word whitespace-pre-line">
                        {wallet.objective_text ?? '—'}
                    </p>
                </div>

                {wallet.assets.length > 0 && (
                    <section className="border-border rounded-xl border p-4">
                        <h2 className="font-medium">Distribuição da carteira</h2>
                        <div className="mt-3">
                            <div
                                className="h-90 min-w-0 sm:h-105"
                                role="img"
                                aria-label={`Distribuição atual da carteira: ${allocation
                                    .map(
                                        (asset) =>
                                            `${asset.ticker}, ${formatCurrency(asset.value)}, ${formatPercentage(asset.current_percentage)}`,
                                    )
                                    .join('; ')}`}
                            >
                                {chartAssets.length > 0 ? (
                                    <ResponsiveContainer
                                        width="100%"
                                        height="100%"
                                    >
                                        <PieChart
                                            margin={{
                                                top: 16,
                                                right: 88,
                                                bottom: 16,
                                                left: 88,
                                            }}
                                        >
                                            <Pie
                                                data={chartAssets}
                                                dataKey="value"
                                                nameKey="ticker"
                                                cx="50%"
                                                cy="50%"
                                                outerRadius="54%"
                                                stroke="var(--background)"
                                                strokeWidth={2}
                                                labelLine={{
                                                    stroke: 'var(--muted-foreground)',
                                                    strokeWidth: 1,
                                                }}
                                                label={({
                                                    payload,
                                                    x,
                                                    y,
                                                    textAnchor,
                                                }) => (
                                                    <text
                                                        x={x}
                                                        y={y}
                                                        textAnchor={textAnchor}
                                                        fill="var(--foreground)"
                                                        fontSize={12}
                                                    >
                                                        <tspan
                                                            x={x}
                                                            dy="-0.2em"
                                                            fontWeight={600}
                                                        >
                                                            {payload.ticker}
                                                        </tspan>
                                                        <tspan x={x} dy="1.3em">
                                                            {formatCurrency(
                                                                payload.value,
                                                            )}
                                                        </tspan>
                                                        <tspan
                                                            x={x}
                                                            dy="1.3em"
                                                            fill="var(--muted-foreground)"
                                                        >
                                                            {formatPercentage(
                                                                payload.current_percentage,
                                                            )}
                                                        </tspan>
                                                    </text>
                                                )}
                                            >
                                                {chartAssets.map((asset) => (
                                                    <Cell
                                                        key={asset.ticker}
                                                        fill={asset.chartColor}
                                                    />
                                                ))}
                                            </Pie>
                                        </PieChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="text-muted-foreground flex h-full items-center justify-center text-sm">
                                        Sem valores para exibir.
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>
                )}

                <div className="border-border overflow-hidden rounded-xl border">
                    <div className="flex flex-wrap items-center justify-between gap-3 border-b p-4">
                        <h2 className="font-medium">Ativos da carteira</h2>
                        <label className="flex items-center gap-2 text-sm">
                            <span className="text-muted-foreground">Setor</span>
                            <select
                                value={sectorFilter}
                                onChange={(event) =>
                                    setSectorFilter(event.target.value)
                                }
                                className="border-input bg-background h-9 rounded-md border px-3"
                            >
                                <option value="">Todos os setores</option>
                                {sectors.map((sector) => (
                                    <option key={sector} value={sector}>
                                        {sector}
                                    </option>
                                ))}
                            </select>
                        </label>
                    </div>
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground">
                            <tr className="text-left">
                                <th className="px-4 py-3 font-medium">Ativo</th>
                                <th className="px-4 py-3 font-medium">Setor</th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Quantidade
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Cotação
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Valor
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Atual
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Objetivo
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {wallet.assets.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        Nenhum ativo nesta carteira.
                                    </td>
                                </tr>
                            ) : visibleAssets.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        Nenhum ativo neste setor.
                                    </td>
                                </tr>
                            ) : (
                                visibleAssets.map((asset) => (
                                    <tr
                                        key={asset.ticker}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3">
                                            <span className="font-medium">
                                                {asset.ticker}
                                            </span>
                                            <span className="text-muted-foreground ml-2">
                                                {asset.name}
                                            </span>
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {asset.sector ?? '—'}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {asset.current_quantity.toLocaleString(
                                                'pt-BR',
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatCurrency(
                                                asset.current_price,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatCurrency(asset.value)}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatPercentage(
                                                asset.current_percentage,
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatPercentage(
                                                asset.target_percentage,
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
