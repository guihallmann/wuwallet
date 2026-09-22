import { Head } from '@inertiajs/react';
import {
    Bell,
    ChevronLeft,
    ChevronRight,
    Minus,
    Plus,
    Search,
    Users,
    Wallet,
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';

const summaryCards = [
    {
        label: 'Patrimônio sob custódia',
        value: 'R$ 3.846.000,00',
        detail: '+14,2% este mês',
        tone: 'blue',
        icon: Wallet,
    },
    {
        label: 'Total de clientes',
        value: '42 Ativos',
        detail: '+3 novos nos últimos 15 dias',
        tone: 'green',
        icon: Users,
    },
    {
        label: 'Alertas de rebalancemento',
        value: '3 Pendentes',
        detail: 'Requer atenção do assessor',
        tone: 'amber',
        icon: Bell,
    },
] as const;

const clients = [
    {
        name: 'Ana Beatriz Silva',
        email: 'anabeatriz@gmail.com',
        cpf: '***.842.102-12',
        patrimonio: 'R$ 342.500,00',
        status: 'Ativo',
    },
    {
        name: 'Carlos Eduardo Santos',
        email: 'carlos.eduardo@gmail.com',
        cpf: '***.973.548-??',
        patrimonio: 'R$ 1.250.000,00',
        status: 'Ativo',
    },
    {
        name: 'Mariana Souza Oliveira',
        email: 'mariana.oliveira@gmail.com',
        cpf: '***.482.908-01',
        patrimonio: 'R$ 85.000,00',
        status: 'Pendente',
    },
    {
        name: 'Rodrigo Mendes Pereira',
        email: 'rodrigo.mendes@gmail.com',
        cpf: '***.721.408-99',
        patrimonio: 'R$ 510.200,00',
        status: 'Ativo',
    },
    {
        name: 'Juliana Costa Ferreira',
        email: 'juliana.costa@gmail.com',
        cpf: '***.365.112-08',
        patrimonio: 'R$ 720.000,00',
        status: 'Em Revisão',
    },
    {
        name: 'Felipe Augusto Lima',
        email: 'felipe.lima@gmail.com',
        cpf: '***.984.328-56',
        patrimonio: 'R$ 195.400,00',
        status: 'Ativo',
    },
    {
        name: 'Gabriela Duarte Rocha',
        email: 'gabriela.rocha@gmail.com',
        cpf: '***.259.738-88',
        patrimonio: 'R$ 430.000,00',
        status: 'Ativo',
    },
    {
        name: 'Thiago Henrique Castro',
        email: 'thiago.castro@gmail.com',
        cpf: '***.614.858-34',
        patrimonio: 'R$ 310.000,00',
        status: 'Pendente',
    },
] as const;

function getStatusStyle(status: string) {
    switch (status) {
        case 'Ativo':
            return 'bg-emerald-100 text-emerald-700 border-emerald-200';
        case 'Pendente':
            return 'bg-amber-100 text-amber-700 border-amber-200';
        case 'Em Revisão':
            return 'bg-sky-100 text-sky-700 border-sky-200';
        default:
            return 'bg-slate-100 text-slate-700 border-slate-200';
    }
}

export default function Dashboard() {
    return (
        <>
            <Head title="DCarteiras Vinculadas" />

            <div className="flex h-full flex-1 flex-col gap-5 overflow-x-auto rounded-xl bg-[#f5f7fb] p-4">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-3xl font-semibold tracking-tight text-slate-900">
                            Carteiras Vinculadas
                        </h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Monitore a alocação e o patrimônio dos clientes sob
                            sua custódia ativa.
                        </p>
                    </div>

                    <Button className="bg-[#2d6df6] text-white hover:bg-[#245ee0]">
                        <Plus className="mr-2 h-4 w-4" />
                        Vincular Novo Cliente
                    </Button>
                    <Button className="bg-[#2d6df6] text-white hover:bg-[#245ee0]">
                        <Minus className="mr-2 h-4 w-4" />
                        Desvincular Cliente
                    </Button>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {summaryCards.map(
                        ({ label, value, detail, tone, icon: Icon }) => (
                            <div
                                key={label}
                                className="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-4 shadow-sm"
                            >
                                <div className="space-y-2">
                                    <p className="text-[11px] font-medium tracking-[0.12em] text-slate-500 uppercase">
                                        {label}
                                    </p>
                                    <div className="flex items-baseline gap-2">
                                        <span className="text-3xl font-semibold text-slate-900">
                                            {value}
                                        </span>
                                    </div>
                                    <p className="text-sm text-slate-500">
                                        {detail}
                                    </p>
                                </div>

                                <div
                                    className={[
                                        'flex h-12 w-12 items-center justify-center rounded-lg',
                                        tone === 'blue' &&
                                            'bg-blue-100 text-blue-600',
                                        tone === 'green' &&
                                            'bg-emerald-100 text-emerald-600',
                                        tone === 'amber' &&
                                            'bg-amber-100 text-amber-600',
                                    ].join(' ')}
                                >
                                    <Icon className="h-5 w-5" />
                                </div>
                            </div>
                        ),
                    )}
                </div>

                <div className="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div className="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div className="relative w-full max-w-sm">
                            <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <Input
                                placeholder="Buscar cliente por nome ou CPF..."
                                className="h-10 rounded-md border-slate-200 pl-9 text-sm"
                            />
                        </div>

                        <div className="flex items-center gap-2 self-end md:self-auto">
                            <span className="text-sm text-slate-500">
                                Filtrar por:
                            </span>
                            <Button
                                variant="secondary"
                                className="h-9 rounded-md bg-emerald-100 text-emerald-700 hover:bg-emerald-200"
                            >
                                Todos os Clientes
                            </Button>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-lg border border-slate-200">
                        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead className="bg-slate-50 text-[11px] font-medium tracking-[0.12em] text-slate-500 uppercase">
                                <tr>
                                    <th className="px-4 py-3">
                                        Nome do cliente
                                    </th>
                                    <th className="px-4 py-3">CPF</th>
                                    <th className="px-4 py-3">Patrimônio</th>
                                    <th className="px-4 py-3">Status</th>
                                    <th className="px-4 py-3 text-center">
                                        Ações
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-200 bg-white">
                                {clients.map((client) => (
                                    <tr
                                        key={client.name}
                                        className="hover:bg-slate-50"
                                    >
                                        <td className="px-4 py-4">
                                            <div className="font-medium text-slate-900">
                                                {client.name}
                                            </div>
                                            <div className="text-xs text-slate-500">
                                                {client.email}
                                            </div>
                                        </td>
                                        <td className="px-4 py-4 text-slate-600">
                                            {client.cpf}
                                        </td>
                                        <td className="px-4 py-4 font-medium text-slate-900">
                                            {client.patrimonio}
                                        </td>
                                        <td className="px-4 py-4">
                                            <Badge
                                                variant="outline"
                                                className={[
                                                    'border px-2.5 py-1 text-xs font-medium',
                                                    getStatusStyle(
                                                        client.status,
                                                    ),
                                                ].join(' ')}
                                            >
                                                {client.status}
                                            </Badge>
                                        </td>
                                        <td className="px-4 py-4 text-center">
                                            <Button
                                                variant="outline"
                                                className="h-9 rounded-md border-blue-200 bg-blue-50 px-3 text-sm font-medium text-blue-700 hover:bg-blue-100"
                                            >
                                                Visualizar Carteira
                                            </Button>
                                            
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-5 flex items-center justify-between gap-3 text-sm text-slate-500">
                        <p>Mostrando 8 de 42 clientes vinculados</p>

                        <div className="flex items-center gap-2">
                            <Button
                                variant="outline"
                                size="icon"
                                className="h-8 w-8 rounded-md border-slate-200 bg-white"
                            >
                                <ChevronLeft className="h-4 w-4" />
                            </Button>
                            <Button className="h-8 w-8 rounded-md bg-[#2d6df6] text-white hover:bg-[#245ee0]">
                                1
                            </Button>
                            <Button
                                variant="outline"
                                className="h-8 w-8 rounded-md border-slate-200 bg-white text-slate-700"
                            >
                                2
                            </Button>
                            <Button
                                variant="outline"
                                className="h-8 w-8 rounded-md border-slate-200 bg-white text-slate-700"
                            >
                                3
                            </Button>
                            <Button
                                variant="outline"
                                size="icon"
                                className="h-8 w-8 rounded-md border-slate-200 bg-white"
                            >
                                <ChevronRight className="h-4 w-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
