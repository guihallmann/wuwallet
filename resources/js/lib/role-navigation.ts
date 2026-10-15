import { Repeat2, Send, Users, Wallet } from 'lucide-react';
import { create as createInvite } from '@/routes/analyst/invites';
import { index as transactionsIndex } from '@/routes/transactions';
import { index as usersIndex } from '@/routes/users';
import { index as walletsIndex } from '@/routes/wallets';
import type { NavItem, User } from '@/types';

export function getRoleNavigationItems(
    role: User['role'] | null | undefined,
): NavItem[] {
    switch (role) {
        case 'manager':
            return [
                {
                    title: 'Analistas',
                    href: usersIndex(),
                    icon: Users,
                },
            ];
        case 'analyst':
            return [
                {
                    title: 'Clientes',
                    href: usersIndex(),
                    icon: Users,
                },
                {
                    title: 'Convidar cliente',
                    href: createInvite(),
                    icon: Send,
                },
            ];
        case 'client':
            return [
                {
                    title: 'Carteiras',
                    href: walletsIndex(),
                    icon: Wallet,
                },
                {
                    title: 'Movimentações',
                    href: transactionsIndex(),
                    icon: Repeat2,
                },
            ];
        default:
            return [];
    }
}
