import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
};

export type SideMenuItem = {
    icon: string;
    title: string;
    route_name: string;
    params?: Record<string, unknown>;
    badge?: number;
    sub_menu?: SideMenuItem[];
};

export type SideMenuSection = string | SideMenuItem;
