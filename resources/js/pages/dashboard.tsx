import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { getRoleNavigationItems } from '@/lib/role-navigation';
import { dashboard } from '@/routes';

export default function Dashboard() {
    const { auth } = usePage().props;
    const navigationItems = getRoleNavigationItems(auth.user?.role);

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex flex-1 flex-col gap-8 p-4 md:p-8">
                <div className="space-y-2">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Olá, {auth.user?.name}
                    </h1>
                    <p className="text-muted-foreground">
                        Acesse rapidamente as páginas disponíveis para o seu
                        perfil.
                    </p>
                </div>

                <section
                    aria-labelledby="dashboard-shortcuts"
                    className="space-y-4"
                >
                    <h2
                        id="dashboard-shortcuts"
                        className="text-lg font-semibold tracking-tight"
                    >
                        Acesso rápido
                    </h2>

                    {navigationItems.length > 0 ? (
                        <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                            {navigationItems.map((item) => {
                                const Icon = item.icon;

                                return (
                                    <Link
                                        key={item.title}
                                        href={item.href}
                                        className="border-border bg-card hover:bg-muted/50 focus-visible:ring-ring flex min-h-36 items-center justify-between gap-4 rounded-xl border p-5 transition-colors focus-visible:ring-2 focus-visible:outline-none"
                                    >
                                        <div className="flex items-center gap-4">
                                            {Icon && (
                                                <span className="bg-primary/10 text-primary flex size-11 shrink-0 items-center justify-center rounded-lg">
                                                    <Icon className="size-5" />
                                                </span>
                                            )}
                                            <span className="font-medium">
                                                {item.title}
                                            </span>
                                        </div>
                                        <ArrowRight
                                            aria-hidden="true"
                                            className="text-muted-foreground size-4 shrink-0"
                                        />
                                    </Link>
                                );
                            })}
                        </div>
                    ) : (
                        <p className="text-muted-foreground rounded-xl border p-5 text-sm">
                            Nenhuma página adicional está disponível para este
                            perfil.
                        </p>
                    )}
                </section>
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
