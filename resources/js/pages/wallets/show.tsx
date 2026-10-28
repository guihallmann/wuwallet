import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Pencil, Repeat2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
};

export default function WalletsShow({ wallet }: Props) {
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

                    <div className="flex gap-2">
                        <Button asChild variant="outline">
                            <Link href={edit(wallet.id)}>
                                <Pencil />
                                Editar
                            </Link>
                        </Button>
                        <Button asChild>
                            <Link href={walletTransactionsIndex(wallet.id)}>
                                <Repeat2 />
                                Movimentações
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="border-border rounded-xl border p-4">
                    <h2 className="text-sm font-medium">Objetivo</h2>
                    <p className="text-muted-foreground mt-2 text-sm wrap-break-word whitespace-pre-line">
                        {wallet.objective_text ?? '—'}
                    </p>
                </div>

                <div className="border-border overflow-hidden rounded-xl border">
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
                            ) : (
                                wallet.assets.map((asset) => (
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
