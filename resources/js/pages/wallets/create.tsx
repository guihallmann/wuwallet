import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import WalletController from '@/actions/App/Http/Controllers/WalletController';
import AssetAllocationField, {
    isAllocationComplete,
} from '@/components/asset-allocation-field';
import type { AllocationRow } from '@/components/asset-allocation-field';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { index } from '@/routes/wallets';

type Props = {
    // Lista de setores vinda do backend (valor padrão evita quebrar enquanto ela não existir)
    sectors?: string[];
};

export default function WalletsCreate({ sectors = [] }: Props) {
    // Ativos escolhidos até agora (só existem na tela até enviar o formulário)
    const [rows, setRows] = useState<AllocationRow[]>([]);

    return (
        <>
            <Head title="Nova carteira" />

            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="max-w-2xl">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Nova carteira
                    </h1>
                </div>

                <div className="border-border bg-card max-w-2xl rounded-xl border p-6 shadow-sm">
                    <Form
                        {...WalletController.store.form()}
                        className="flex flex-col gap-6"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Nome</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        autoFocus
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
                                        Criar carteira
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
            </div>
        </>
    );
}

WalletsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Carteiras',
            href: index(),
        },
    ],
};
