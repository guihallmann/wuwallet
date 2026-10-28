import { Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatCurrency } from '@/lib/utils';
import { search as searchAssets } from '@/routes/assets';

const TOTAL_CENTS = 10000; // 100,00% em centésimos
const MAX_ASSETS = 50;

// Uma linha da alocação (ativo escolhido + percentual).
// O percentual fica como texto pra permitir digitar "33," sem o campo brigar com você.
export type AllocationRow = {
    asset_id: number;
    ticker: string;
    name: string;
    target_percentage: string;
    current_quantity?: number;
};

// Formato que o endpoint de busca devolve (conforme o plano do projeto)
type SearchResult = {
    id: number;
    ticker: string;
    name: string;
    sector: string;
    current_price: string | number;
};

function isSearchResult(value: unknown): value is SearchResult {
    return (
        value !== null &&
        typeof value === 'object' &&
        'id' in value &&
        typeof value.id === 'number' &&
        'ticker' in value &&
        typeof value.ticker === 'string' &&
        'name' in value &&
        typeof value.name === 'string' &&
        'sector' in value &&
        typeof value.sector === 'string' &&
        'current_price' in value &&
        (typeof value.current_price === 'number' ||
            typeof value.current_price === 'string')
    );
}

type Props = {
    rows: AllocationRow[];
    onChange: (rows: AllocationRow[]) => void;
    sectors: string[];
    errors: Record<string, string>;
};

// Converte "33.33" em 3333 (inteiro). Somar inteiros evita erro de float.
function toCents(value: string): number {
    const parsed = Number(value.replace(',', '.'));

    return Number.isFinite(parsed) ? Math.round(parsed * 100) : 0;
}

function formatCents(cents: number): string {
    return (cents / 100).toFixed(2).replace('.', ',');
}

export function allocationTotalCents(rows: AllocationRow[]): number {
    return rows.reduce((sum, row) => sum + toCents(row.target_percentage), 0);
}

// Usado pelas páginas pra liberar (ou não) o botão de salvar
export function isAllocationComplete(rows: AllocationRow[]): boolean {
    return rows.length > 0 && allocationTotalCents(rows) === TOTAL_CENTS;
}

export default function AssetAllocationField({
    rows,
    onChange,
    sectors,
    errors,
}: Props) {
    const containerRef = useRef<HTMLDivElement>(null);

    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [sector, setSector] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [loading, setLoading] = useState(false);
    const [searchError, setSearchError] = useState<string | null>(null);

    const totalCents = allocationTotalCents(rows);
    const reachedLimit = rows.length >= MAX_ASSETS;

    // Não mostra na busca os ativos que já foram escolhidos
    const available = results.filter(
        (result) => !rows.some((row) => row.asset_id === result.id),
    );

    // Busca no servidor sempre que o dropdown está aberto e o texto/setor muda.
    // O atraso de 300ms evita uma requisição a cada letra digitada.
    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();

        const timeout = setTimeout(async () => {
            const params = new URLSearchParams();

            if (query.trim()) {
                params.set('q', query.trim());
            }

            if (sector) {
                params.set('sector', sector);
            }

            setLoading(true);
            setSearchError(null);

            try {
                const queryString = params.toString();
                const response = await fetch(
                    `${searchAssets.url()}${queryString ? `?${queryString}` : ''}`,
                    {
                        headers: { Accept: 'application/json' },
                        signal: controller.signal,
                    },
                );

                if (!response.ok) {
                    const message =
                        response.status === 401
                            ? 'Sua sessão expirou. Atualize a página e tente novamente.'
                            : response.status === 403
                              ? 'Você não tem permissão para buscar ativos.'
                              : `Não foi possível buscar os ativos (erro ${response.status}).`;

                    throw new Error(message);
                }

                const body: unknown = await response.json();
                const items: unknown[] | null = Array.isArray(body)
                    ? body
                    : body !== null &&
                        typeof body === 'object' &&
                        'data' in body &&
                        Array.isArray(body.data)
                      ? body.data
                      : null;

                if (items === null || !items.every(isSearchResult)) {
                    throw new Error(
                        'O servidor retornou uma resposta de busca inválida.',
                    );
                }

                setResults(items);
            } catch (error) {
                if (controller.signal.aborted) {
                    return;
                }

                setResults([]);
                setSearchError(
                    error instanceof TypeError
                        ? 'Falha de conexão ao buscar ativos. Verifique a conexão e tente novamente.'
                        : error instanceof Error
                          ? error.message
                          : 'Falha inesperada ao buscar ativos. Tente novamente.',
                );
            } finally {
                if (!controller.signal.aborted) {
                    setLoading(false);
                }
            }
        }, 300);

        return () => {
            clearTimeout(timeout);
            controller.abort();
        };
    }, [open, query, sector]);

    // Fecha o dropdown ao clicar fora dele
    useEffect(() => {
        function handleClickOutside(event: MouseEvent) {
            if (
                containerRef.current &&
                !containerRef.current.contains(event.target as Node)
            ) {
                setOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);

        return () =>
            document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    function addAsset(asset: SearchResult) {
        if (reachedLimit) {
            return;
        }

        onChange([
            ...rows,
            {
                asset_id: asset.id,
                ticker: asset.ticker,
                name: asset.name,
                target_percentage: '',
                current_quantity: 0,
            },
        ]);

        setQuery('');
        setOpen(false);
    }

    function updatePercentage(position: number, value: string) {
        onChange(
            rows.map((row, index) =>
                index === position ? { ...row, target_percentage: value } : row,
            ),
        );
    }

    function removeAsset(position: number) {
        onChange(rows.filter((_, index) => index !== position));
    }

    // Divide 100% em partes iguais; o último ativo fica com a sobra do arredondamento
    function distributeEqually() {
        if (rows.length === 0) {
            return;
        }

        const base = Math.floor(TOTAL_CENTS / rows.length);
        const remainder = TOTAL_CENTS - base * rows.length;

        onChange(
            rows.map((row, index) => ({
                ...row,
                target_percentage: (
                    (index === rows.length - 1 ? base + remainder : base) / 100
                ).toFixed(2),
            })),
        );
    }

    const difference = TOTAL_CENTS - totalCents;

    return (
        <div className="grid gap-3">
            <Label>Ativos</Label>

            {/* Busca + filtro de setor.
                Estes campos NÃO têm "name" de propósito: são só auxiliares
                e não devem ser enviados junto com o formulário. */}
            <div ref={containerRef} className="relative">
                <div className="flex gap-2">
                    <Input
                        value={query}
                        placeholder="Buscar por ticker ou nome..."
                        autoComplete="off"
                        disabled={reachedLimit}
                        onChange={(event) => {
                            setQuery(event.target.value);
                            setOpen(true);
                        }}
                        onFocus={() => setOpen(true)}
                        onKeyDown={(event) => {
                            // Enter aqui não pode enviar o formulário
                            if (event.key === 'Enter') {
                                event.preventDefault();
                            }

                            if (event.key === 'Escape') {
                                setOpen(false);
                            }
                        }}
                    />

                    <select
                        value={sector}
                        onChange={(event) => {
                            setSector(event.target.value);
                            setOpen(true);
                        }}
                        className="border-input bg-background h-9 max-w-48 rounded-md border px-3 text-sm"
                    >
                        <option value="">Todos os setores</option>
                        {sectors.map((item) => (
                            <option key={item} value={item}>
                                {item}
                            </option>
                        ))}
                    </select>
                </div>

                {open && (
                    <div className="bg-popover text-popover-foreground absolute z-20 mt-1 max-h-64 w-full overflow-auto rounded-md border shadow-md">
                        {loading && (
                            <p className="text-muted-foreground px-3 py-2 text-sm">
                                Buscando...
                            </p>
                        )}

                        {!loading && searchError !== null && (
                            <p
                                role="alert"
                                className="text-destructive px-3 py-2 text-sm"
                            >
                                {searchError}
                            </p>
                        )}

                        {!loading &&
                            searchError === null &&
                            available.length === 0 && (
                                <p className="text-muted-foreground px-3 py-2 text-sm">
                                    Nenhum ativo encontrado. Verifique se o
                                    catálogo foi sincronizado.
                                </p>
                            )}

                        {available.map((asset) => (
                            <button
                                key={asset.id}
                                type="button"
                                onClick={() => addAsset(asset)}
                                className="hover:bg-accent flex w-full items-center justify-between gap-3 px-3 py-2 text-left text-sm"
                            >
                                <span className="flex flex-col">
                                    <span className="font-medium">
                                        {asset.ticker}
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        {asset.name} · {asset.sector}
                                    </span>
                                </span>
                                <span className="text-muted-foreground text-xs">
                                    {formatCurrency(
                                        Number(asset.current_price),
                                    )}
                                </span>
                            </button>
                        ))}
                    </div>
                )}
            </div>

            {/* Ativos escolhidos. Cada linha envia os campos assets[i][...] */}
            {rows.map((row, position) => (
                <div key={row.asset_id} className="grid gap-1">
                    <div className="bg-muted/50 flex items-center gap-3 rounded-md px-3 py-2">
                        <input
                            type="hidden"
                            name={`assets[${position}][asset_id]`}
                            value={row.asset_id}
                        />

                        <div className="flex flex-1 flex-col text-sm">
                            <span className="font-medium">{row.ticker}</span>
                            <span className="text-muted-foreground text-xs">
                                {row.name}
                            </span>
                        </div>

                        <div className="relative">
                            <Input
                                name={`assets[${position}][target_percentage]`}
                                type="number"
                                min="0"
                                max="100"
                                step="0.01"
                                aria-label={`Percentual de ${row.ticker}`}
                                className="w-28 pr-7"
                                value={row.target_percentage}
                                onChange={(event) =>
                                    updatePercentage(
                                        position,
                                        event.target.value,
                                    )
                                }
                                onKeyDown={(event) => {
                                    if (event.key === 'Enter') {
                                        event.preventDefault();
                                    }
                                }}
                            />
                            <span className="text-muted-foreground pointer-events-none absolute top-1/2 right-3 -translate-y-1/2 text-sm">
                                %
                            </span>
                        </div>

                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            disabled={(row.current_quantity ?? 0) > 0}
                            title={
                                (row.current_quantity ?? 0) > 0
                                    ? 'Não é possível remover um ativo com saldo na carteira.'
                                    : 'Remover ativo da carteira'
                            }
                            onClick={() => removeAsset(position)}
                        >
                            <Trash2 />
                        </Button>
                    </div>
                    {(row.current_quantity ?? 0) > 0 && (
                        <p className="text-muted-foreground px-3 text-xs">
                            Venda as {row.current_quantity} ações antes de
                            remover este ativo da carteira.
                        </p>
                    )}

                    <InputError
                        message={errors[`assets.${position}.asset_id`]}
                    />
                    <InputError
                        message={errors[`assets.${position}.target_percentage`]}
                    />
                </div>
            ))}

            {rows.length > 0 && (
                <div className="flex items-center justify-between gap-3">
                    <p
                        className={
                            difference === 0
                                ? 'text-sm text-green-600'
                                : 'text-destructive text-sm'
                        }
                    >
                        {difference === 0 && 'Total fechado em 100,00%'}
                        {difference > 0 &&
                            `Total: ${formatCents(totalCents)}% (faltam ${formatCents(difference)}%)`}
                        {difference < 0 &&
                            `Total: ${formatCents(totalCents)}% (excedeu ${formatCents(-difference)}%)`}
                    </p>

                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={distributeEqually}
                    >
                        Dividir igualmente
                    </Button>
                </div>
            )}

            {reachedLimit && (
                <p className="text-muted-foreground text-sm">
                    Limite de {MAX_ASSETS} ativos por carteira.
                </p>
            )}

            <InputError message={errors.assets} />
        </div>
    );
}
