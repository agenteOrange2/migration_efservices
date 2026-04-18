import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export interface MenuItem {
  icon: string;
  title: string;
  pageName?: string;
  subMenu?: MenuItem[];
  ignore?: boolean;
  badge?: string;
}

function normalizeIconName(icon?: string | null): string {
  const fallback = 'Circle';

  if (!icon || !String(icon).trim()) return fallback;

  const normalized = String(icon)
    .trim()
    .replace(/([a-z0-9])([A-Z])/g, '$1 $2')
    .split(/[\s_-]+/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join('');

  return normalized || fallback;
}

function transformBackendMenu(items: any[]): Array<MenuItem | string> {
  if (!items || !Array.isArray(items)) return [];

  return items.map((item) => {
    if (typeof item === 'string') {
      return item;
    }

    const menuItem: MenuItem = {
      icon: normalizeIconName(item.icon),
      title: item.title || '',
      pageName: item.route_name === '#' ? undefined : item.route_name,
    };

    if (item.sub_menu && Array.isArray(item.sub_menu)) {
      menuItem.subMenu = item.sub_menu.map((sub: any) => ({
        icon: normalizeIconName(sub.icon),
        title: sub.title || '',
        pageName: sub.route_name === '#' ? undefined : sub.route_name,
      }));
    }

    return menuItem;
  });
}

const fallbackMenu: Array<MenuItem | string> = [
  {
    icon: 'Home',
    pageName: 'dashboard',
    title: 'Dashboard',
  },
  'MENÚ',
  {
    icon: 'Settings',
    pageName: 'profile.edit',
    title: 'Configuración',
  },
];

export function useMenu() {
  const page = usePage();

  const menu = computed(() => {
    const backendMenu = (page.props as any).sideMenu;
    if (backendMenu && Array.isArray(backendMenu) && backendMenu.length > 0) {
      return transformBackendMenu(backendMenu);
    }
    return fallbackMenu;
  });

  return {
    menu,
  };
}
