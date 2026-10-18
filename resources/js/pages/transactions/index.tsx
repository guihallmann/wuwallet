import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Plus, Wallet } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/utils';
import { index as walletsIndex } from '@/routes/wallets';
import {
    create as createWalletTransaction,
    index as walletTransactionsIndex,
} from '@/routes/wallets/transactions';

type Transaction = {
    id: string;
    ticker: string;
    asset_name: string;
    transaction_type: 'buy' | 'sell';
    quantity: number;
    unit_price: string;
    transaction_date: string;
};

type Props = {
    wallet: {
        id: string;
        name: string;
    };
    transactions: Transaction[];
};

export default function TransactionsIndex({ wallet, transactions }: Props) {
    setLayoutProps({
        breadcrumbs: [
            { title: 'Carteiras', href: walletsIndex() },
            {
                title: wallet.name,
                href: walletTransactionsIndex(wallet.id),
            },
        ],
    });

    return (
        <>
            <Head title={`Movimentações - ${wallet.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            Movimentações
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Histórico da carteira {wallet.name}.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={createWalletTransaction(wallet.id)}>
                            <Plus />
                            Registrar movimentação
                        </Link>
                    </Button>
                </div>

                <div className="border-border overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground">
                            <tr className="text-left">
                                <th className="px-4 py-3 font-medium">Data</th>
                                <th className="px-4 py-3 font-medium">Tipo</th>
                                <th className="px-4 py-3 font-medium">Ativo</th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Quantidade
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Preço unitário
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Total
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {transactions.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        Ainda não há movimentações nesta
                                        carteira.
                                    </td>
                                </tr>
                            ) : (
                                transactions.map((transaction) => (
                                    <tr key={transaction.id}>
                                        <td className="px-4 py-3">
                                            {new Date(
                                                `${transaction.transaction_date}T00:00:00`,
                                            ).toLocaleDateString('pt-BR')}
                                        </td>
                                        <td className="px-4 py-3">
                                            {transaction.transaction_type ===
                                            'buy'
                                                ? 'Compra'
                                                : 'Venda'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <span className="font-medium">
                                                {transaction.ticker}
                                            </span>
                                            <span className="text-muted-foreground ml-2">
                                                {transaction.asset_name}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {transaction.quantity}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatCurrency(
                                                Number(transaction.unit_price),
                                            )}
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatCurrency(
                                                transaction.quantity *
                                                    Number(
                                                        transaction.unit_price,
                                                    ),
                                            )}
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>

                <Button asChild variant="ghost" className="w-fit">
                    <Link href={walletsIndex()}>
                        <Wallet />
                        Voltar às carteiras
                    </Link>
                </Button>
            </div>
        </>
    );
}
