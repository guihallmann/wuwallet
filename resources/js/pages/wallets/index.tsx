import { Head, Link, setLayoutProps } from '@inertiajs/react';
import { Plus, Repeat2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { formatCurrency } from '@/lib/utils';
import { index as clientWalletsIndex } from '@/routes/clients/wallets';
import { index as usersIndex } from '@/routes/users';
import { create, edit, index } from '@/routes/wallets';
import { index as walletTransactionsIndex } from '@/routes/wallets/transactions';

type WalletRow = {
    id: string;
    name: string;
    objective_text: string | null;
    total_value: number;
    allocation: {
        ticker: string;
        name: string;
        target_percentage: string;
        current_quantity: number;
    }[];
};

type Props =
    | {
          mode: 'manage';
          wallets: WalletRow[];
          totalValue: number;
      }
    | {
          mode: 'view';
          client: { id: string; name: string };
          wallets: WalletRow[];
          totalValue: number;
      };

export default function WalletsIndex(props: Props) {
    const { mode, wallets, totalValue } = props;
    const title =
        mode === 'manage'
            ? 'Minhas carteiras'
            : `Carteiras de ${props.client.name}`;

    setLayoutProps({
        breadcrumbs:
            mode === 'manage'
                ? [{ title: 'Carteiras', href: index() }]
                : [
                      { title: 'Clientes', href: usersIndex() },
                      {
                          title: props.client.name,
                          href: clientWalletsIndex(props.client.id),
                      },
                  ],
    });

    return (
        <>
            <Head title={title} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-8">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">
                            {title}
                        </h1>
                        <p className="text-muted-foreground mt-1 text-sm">
                            Patrimônio total: {formatCurrency(totalValue)}
                        </p>
                    </div>

                    {mode === 'manage' && (
                        <Button asChild>
                            <Link href={create()}>
                                <Plus />
                                Nova carteira
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="border-border overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-muted-foreground">
                            <tr className="text-left">
                                <th className="px-4 py-3 font-medium">Nome</th>
                                <th className="px-4 py-3 font-medium">
                                    Objetivo
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Alocação
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Quantidade
                                </th>
                                <th className="px-4 py-3 text-right font-medium">
                                    Valor
                                </th>
                                {mode === 'manage' && (
                                    <th className="px-4 py-3 text-right font-medium">
                                        Ações
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody className="divide-border divide-y">
                            {wallets.length === 0 ? (
                                <tr>
                                    <td
                                        colSpan={mode === 'manage' ? 6 : 5}
                                        className="text-muted-foreground px-4 py-8 text-center"
                                    >
                                        Nenhuma carteira cadastrada.
                                    </td>
                                </tr>
                            ) : (
                                wallets.map((wallet) => (
                                    <tr
                                        key={wallet.id}
                                        className="hover:bg-muted/30"
                                    >
                                        <td className="px-4 py-3 font-medium">
                                            {wallet.name}
                                        </td>
                                        <td className="text-muted-foreground px-4 py-3">
                                            {wallet.objective_text ?? '—'}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex flex-wrap gap-1.5">
                                                {wallet.allocation.map(
                                                    (asset) => (
                                                        <span
                                                            key={asset.ticker}
                                                            title={asset.name}
                                                            className="bg-muted rounded-md px-2 py-1 text-xs"
                                                        >
                                                            {asset.ticker}{' '}
                                                            {Number(
                                                                asset.target_percentage,
                                                            ).toFixed(2)}
                                                            %
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex flex-wrap justify-end gap-1.5">
                                                {wallet.allocation.map(
                                                    (asset) => (
                                                        <span
                                                            key={asset.ticker}
                                                            title={asset.name}
                                                            className="bg-muted rounded-md px-2 py-1 text-xs"
                                                        >

                                                            {asset.current_quantity.toLocaleString(
                                                                'pt-BR',
                                                            )}{' '}
                                                            ações
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            {formatCurrency(wallet.total_value)}
                                        </td>
                                        {mode === 'manage' && (
                                            <td className="px-4 py-3 text-right">
                                                <div className="flex flex-col items-stretch gap-2">
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        <Link
                                                            href={edit(
                                                                wallet.id,
                                                            )}
                                                        >
                                                            Editar
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        asChild
                                                        variant="outline"
                                                        size="sm"
                                                    >
                                                        <Link
                                                            href={walletTransactionsIndex(
                                                                wallet.id,
                                                            )}
                                                        >
                                                            <Repeat2 />
                                                            Movimentações
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </td>
                                        )}
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
