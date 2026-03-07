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

function transformBackendMenu(items: any[]): Array<MenuItem | string> {
  if (!items || !Array.isArray(items)) return [];

  return items.map((item) => {
    if (typeof item === 'string') {
      return item;
    }

    const menuItem: MenuItem = {
      icon: item.icon || 'Circle',
      title: item.title || '',
      pageName: item.route_name === '#' ? undefined : item.route_name,
    };

    if (item.sub_menu && Array.isArray(item.sub_menu)) {
      menuItem.subMenu = item.sub_menu.map((sub: any) => ({
        icon: sub.icon || 'Circle',
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
