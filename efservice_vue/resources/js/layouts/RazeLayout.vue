<script setup lang="ts">
import axios from 'axios';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { useDebounceFn } from '@vueuse/core';
import Breadcrumb from '@/components/Base/Breadcrumb';
import { Dialog, Menu } from '@/components/Base/Headless';
import Lucide from '@/components/Base/Lucide';
import { useAppearance } from '@/composables/useAppearance';
import { useCompactMenu } from '@/composables/useCompactMenu';
import { type MenuItem, useMenu } from '@/composables/useMenu';
import { useNotifications } from '@/composables/useNotifications';

type SearchNavigationItem = {
  id: string;
  title: string;
  fullPath?: string;
  section?: string;
  icon?: string;
  url: string;
};

type SearchEntityItem = {
  id: string;
  title: string;
  subtitle?: string | null;
  icon?: string;
  url: string;
  category?: string;
};

type SearchQuickAction = {
  title: string;
  icon?: string;
  url: string;
};

type ShortcutLink = {
  title: string;
  section: string;
  icon: string;
  href: string;
};

type BreadcrumbLink = {
  title: string;
  href: string;
  active?: boolean;
};

type FlattenedMenuRoute = {
  title: string;
  href: string;
  hrefPath: string;
  icon: string;
  section: string;
  parentTitle?: string;
};

const page = usePage();
const auth = computed(() => (page.props.auth as any) ?? {});
const user = computed(() => auth.value.user ?? null);
const userRoles = computed<string[]>(() => Array.isArray(user.value?.roles) ? user.value.roles : []);
const isAdmin = computed(() => userRoles.value.includes('superadmin'));
const isCarrier = computed(() => userRoles.value.includes('user_carrier'));
const isDriver = computed(() => userRoles.value.includes('user_driver'));

const flash = computed(() => (page.props as any).flash ?? {});
const toastVisible = ref(false);
const toastMessage = ref('');
const toastType = ref<'success' | 'error' | 'warning' | 'info'>('success');
let toastTimer: ReturnType<typeof setTimeout> | null = null;

function showToast(type: 'success' | 'error' | 'warning' | 'info', message: string) {
  toastType.value = type;
  toastMessage.value = message;
  toastVisible.value = true;
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toastVisible.value = false;
  }, 5000);
}

watch(
  flash,
  (f) => {
    if (f.success) showToast('success', f.success);
    else if (f.error) showToast('error', f.error);
    else if (f.warning) showToast('warning', f.warning);
    else if (f.info) showToast('info', f.info);
  },
  { immediate: true },
);

const { menu } = useMenu();
const { appearance, updateAppearance } = useAppearance();
const { notifications, unreadCount, markAsRead, markAllAsRead } = useNotifications();
const { compactMenu, setCompactMenu } = useCompactMenu();

const compactMenuOnHover = ref(false);
const activeMobileMenu = ref(false);
const openSubMenus = reactive<Record<number, boolean>>({});

const showSearch = ref(false);
const searchQuery = ref('');
const searchInputRef = ref<HTMLInputElement | null>(null);
const searchLoading = ref(false);
const searchNavigation = ref<SearchNavigationItem[]>([]);
const searchEntities = ref<Record<string, SearchEntityItem[]>>({});
const quickActions = ref<SearchQuickAction[]>([]);
const selectedSearchIndex = ref(-1);

const isFullscreen = ref(false);

function getSafeOrigin() {
  if (typeof window !== 'undefined' && window.location?.origin) {
    return window.location.origin;
  }

  return 'http://localhost';
}

function safeRoute(routeName?: string | null): string | null {
  if (!routeName || routeName === '#') return null;

  try {
    return route(routeName);
  } catch {
    return null;
  }
}

function hrefPath(href: string): string {
  try {
    return new URL(href, getSafeOrigin()).pathname;
  } catch {
    return href;
  }
}

function titleCase(value: string): string {
  return value
    .toLowerCase()
    .split(/[\s_-]+/)
    .filter(Boolean)
    .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
    .join(' ');
}

function humanizeSegment(segment?: string | null): string {
  if (!segment) return 'Details';
  if (/^\d+$/.test(segment)) return 'Details';
  if (/^[0-9a-f-]{8,}$/i.test(segment)) return 'Details';

  return titleCase(segment);
}

const roleLabel = computed(() => {
  if (isAdmin.value) return 'Administrator';
  if (isCarrier.value) return 'Carrier';
  if (isDriver.value) return 'Driver';
  return 'User';
});

const roleBadgeClass = computed(() => {
  if (isAdmin.value) return 'bg-success/10 text-success';
  if (isCarrier.value) return 'bg-primary/10 text-primary';
  if (isDriver.value) return 'bg-info/10 text-info';
  return 'bg-slate-100 text-slate-600';
});

const homeHref = computed(() => {
  if (isAdmin.value) return safeRoute('admin.dashboard') ?? '/admin';
  if (isCarrier.value) return safeRoute('carrier.dashboard') ?? '/carrier';
  if (isDriver.value) return safeRoute('driver.dashboard') ?? '/driver';
  return safeRoute('dashboard') ?? '/dashboard';
});

const profileHref = computed(() => {
  if (isAdmin.value) return safeRoute('admin.settings');
  if (isCarrier.value) return safeRoute('carrier.profile');
  if (isDriver.value) return safeRoute('driver.profile');
  return safeRoute('profile.edit');
});

const emailSettingsHref = computed(() => {
  if (isAdmin.value) return safeRoute('admin.settings-email-settings');
  if (isCarrier.value) return safeRoute('carrier.profile.edit');
  if (isDriver.value) return safeRoute('driver.profile.edit');
  return safeRoute('profile.edit');
});

const securityHref = computed(() => {
  if (isAdmin.value) return safeRoute('admin.settings-security');
  if (isCarrier.value) return safeRoute('carrier.profile.edit');
  if (isDriver.value) return safeRoute('driver.profile.edit');
  return safeRoute('user-password.edit');
});

const rolesHref = computed(() => safeRoute('admin.roles.index') ?? safeRoute('admin.permissions.index'));
const carrierDocumentsHref = computed(() => safeRoute('carrier.documents.index'));
const carrierReportsHref = computed(() => safeRoute('carrier.reports.index'));
const driverDocumentsHref = computed(() => safeRoute('driver.documents.index'));
const driverVehiclesHref = computed(() => safeRoute('driver.vehicles.index'));
const notificationsIndexHref = computed(() => {
  if (isAdmin.value) return safeRoute('admin.notifications.index');
  if (isCarrier.value) return safeRoute('carrier.notifications.index');
  if (isDriver.value) return safeRoute('driver.notifications.index');
  return null;
});
const searchShortcutLabel = computed(() => {
  if (typeof navigator !== 'undefined' && /Mac|iPhone|iPad|iPod/i.test(navigator.platform)) {
    return '⌘K';
  }

  return 'Ctrl+K';
});

const currentPath = computed(() => hrefPath(page.url));

function flattenMenuRoutes(items: Array<MenuItem | string>, section = ''): FlattenedMenuRoute[] {
  const flattened: FlattenedMenuRoute[] = [];
  let currentSection = section;

  items.forEach((item) => {
    if (typeof item === 'string') {
      currentSection = titleCase(item);
      return;
    }

    if (item.pageName) {
      const href = safeRoute(item.pageName);
      if (href) {
        flattened.push({
          title: item.title,
          href,
          hrefPath: hrefPath(href),
          icon: item.icon,
          section: currentSection,
        });
      }
    }

    if (item.subMenu?.length) {
      item.subMenu.forEach((subItem) => {
        if (!subItem.pageName) return;

        const href = safeRoute(subItem.pageName);
        if (!href) return;

        flattened.push({
          title: subItem.title,
          href,
          hrefPath: hrefPath(href),
          icon: subItem.icon,
          section: currentSection,
          parentTitle: item.title,
        });
      });
    }
  });

  return flattened;
}

const flattenedMenuRoutes = computed(() => flattenMenuRoutes(menu.value as Array<MenuItem | string>));

const matchedMenuRoute = computed(() => {
  const path = currentPath.value;
  let bestMatch: FlattenedMenuRoute | null = null;
  let bestScore = -1;

  flattenedMenuRoutes.value.forEach((item) => {
    if (path === item.hrefPath) {
      const score = 1000 + item.hrefPath.length;
      if (score > bestScore) {
        bestScore = score;
        bestMatch = item;
      }
      return;
    }

    if (
      item.hrefPath !== '/' &&
      path.startsWith(item.hrefPath) &&
      item.hrefPath.length > bestScore
    ) {
      bestScore = item.hrefPath.length;
      bestMatch = item;
    }
  });

  return bestMatch;
});

const fallbackPageTitle = computed(() => {
  const segments = currentPath.value.split('/').filter(Boolean);
  return humanizeSegment(segments[segments.length - 1] ?? 'Dashboard');
});

const pageTitle = computed(() => {
  const props = page.props as Record<string, unknown>;
  const title = [props.title, props.pageTitle, props.heading]
    .find((value) => typeof value === 'string' && value.trim().length > 0);

  return typeof title === 'string' ? title.trim() : fallbackPageTitle.value;
});

const actionBreadcrumb = computed(() => {
  const pathSegments = currentPath.value.split('/').filter(Boolean);
  const lastSegment = pathSegments[pathSegments.length - 1];
  const matched = matchedMenuRoute.value;

  if (!lastSegment || !matched) return '';
  if (currentPath.value === matched.hrefPath) return '';

  if (
    pageTitle.value &&
    pageTitle.value.toLowerCase() !== matched.title.toLowerCase() &&
    pageTitle.value.toLowerCase() !== (matched.parentTitle ?? '').toLowerCase()
  ) {
    return pageTitle.value;
  }

  if (lastSegment === 'create') return 'Create';
  if (lastSegment === 'edit') return 'Edit';
  if (lastSegment === 'documents') return 'Documents';
  if (lastSegment === 'history') return 'History';
  if (lastSegment === 'dashboard') return 'Dashboard';
  if (lastSegment === 'statistics') return 'Statistics';
  if (lastSegment === 'reports') return 'Reports';
  if (lastSegment === 'calendar') return 'Calendar';

  return humanizeSegment(lastSegment);
});

const breadcrumbLinks = computed<BreadcrumbLink[]>(() => {
  const links: BreadcrumbLink[] = [
    {
      title: 'App',
      href: homeHref.value,
    },
  ];

  const matched = matchedMenuRoute.value;

  if (!matched) {
    links.push({
      title: pageTitle.value,
      href: page.url,
      active: true,
    });
    return links;
  }

  if (matched.parentTitle && matched.parentTitle.toLowerCase() !== matched.title.toLowerCase()) {
    links.push({
      title: matched.parentTitle,
      href: matched.href,
    });
  }

  if (!matched.parentTitle || matched.parentTitle.toLowerCase() !== matched.title.toLowerCase()) {
    links.push({
      title: matched.title,
      href: matched.href,
      active: !actionBreadcrumb.value,
    });
  }

  if (actionBreadcrumb.value) {
    links.push({
      title: actionBreadcrumb.value,
      href: page.url,
      active: true,
    });
  } else if (links.length > 0) {
    links[links.length - 1].active = true;
  }

  return links;
});

function formatNotificationTime(value?: string | null): string {
  if (!value) return '';

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;

  return new Intl.DateTimeFormat(undefined, {
    month: 'short',
    day: 'numeric',
    year: date.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined,
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

const shortcutLinks = computed<ShortcutLink[]>(() => {
  const items: ShortcutLink[] = [];
  let currentSection = 'Navigation';

  (menu.value as Array<MenuItem | string>).forEach((item) => {
    if (typeof item === 'string') {
      currentSection = titleCase(item);
      return;
    }

    if (item.pageName) {
      const href = safeRoute(item.pageName);
      if (href) {
        items.push({
          title: item.title,
          section: currentSection,
          icon: item.icon,
          href,
        });
      }
    }

    item.subMenu?.forEach((subItem) => {
      if (!subItem.pageName) return;
      const href = safeRoute(subItem.pageName);
      if (!href) return;
      items.push({
        title: subItem.title,
        section: item.title,
        icon: subItem.icon,
        href,
      });
    });
  });

  const deduped = new Map<string, ShortcutLink>();
  items.forEach((item) => {
    if (!deduped.has(item.href)) {
      deduped.set(item.href, item);
    }
  });

  return Array.from(deduped.values()).slice(0, 8);
});

const groupedSearchNavigation = computed(() => {
  const grouped = new Map<string, SearchNavigationItem[]>();

  searchNavigation.value.forEach((item) => {
    const section = item.section || 'Navigation';
    const group = grouped.get(section) ?? [];
    group.push(item);
    grouped.set(section, group);
  });

  return Array.from(grouped.entries());
});

const groupedSearchEntities = computed(() => Object.entries(searchEntities.value));

const flattenedSearchResults = computed(() => {
  const results: Array<{ id: string; title: string; url: string }> = [];

  searchNavigation.value.forEach((item) => {
    results.push({
      id: item.id,
      title: item.title,
      url: item.url,
    });
  });

  groupedSearchEntities.value.forEach(([, items]) => {
    items.forEach((item) => {
      results.push({
        id: item.id,
        title: item.title,
        url: item.url,
      });
    });
  });

  return results;
});

const hasSearchResults = computed(
  () => searchNavigation.value.length > 0 || groupedSearchEntities.value.some(([, items]) => items.length > 0),
);

const fetchSearchResults = useDebounceFn(async () => {
  const endpoint = safeRoute('search.quick');
  if (!endpoint || !showSearch.value) return;

  searchLoading.value = true;

  try {
    const response = await axios.get(endpoint, {
      params: {
        q: searchQuery.value,
      },
    });

    searchNavigation.value = response.data.navigation ?? [];
    searchEntities.value = response.data.entities ?? {};
    quickActions.value = response.data.quickActions ?? [];
    selectedSearchIndex.value = flattenedSearchResults.value.length ? 0 : -1;
  } catch {
    searchNavigation.value = [];
    searchEntities.value = {};
  } finally {
    searchLoading.value = false;
  }
}, 250);

watch(searchQuery, () => {
  fetchSearchResults();
});

watch(showSearch, async (isOpen) => {
  if (isOpen) {
    await nextTick();
    searchInputRef.value?.focus();
    fetchSearchResults();
  } else {
    searchQuery.value = '';
    searchNavigation.value = [];
    searchEntities.value = {};
    selectedSearchIndex.value = -1;
  }
});

function openSearch() {
  showSearch.value = true;
}

function closeSearch() {
  showSearch.value = false;
}

function navigateSearch(direction: 1 | -1) {
  const results = flattenedSearchResults.value;
  if (!results.length) return;

  if (selectedSearchIndex.value < 0) {
    selectedSearchIndex.value = 0;
    return;
  }

  const nextIndex = selectedSearchIndex.value + direction;

  if (nextIndex < 0) {
    selectedSearchIndex.value = results.length - 1;
    return;
  }

  if (nextIndex >= results.length) {
    selectedSearchIndex.value = 0;
    return;
  }

  selectedSearchIndex.value = nextIndex;
}

function selectSearchResult() {
  const result = flattenedSearchResults.value[selectedSearchIndex.value];
  if (!result) return;

  closeSearch();
  router.visit(result.url);
}

function openNotification(notification: any) {
  const href = notification?.url || notification?.data?.url || notification?.data?.link || notification?.data?.action_url || null;

  if (!notification?.read_at) {
    markAsRead(notification.id);
  }

  if (href) {
    router.visit(href);
  }
}

function notificationTitle(notification: any): string {
  return notification?.title || notification?.data?.title || notification?.type_label || notification?.type?.split('\\').pop() || 'Notification';
}

function notificationDescription(notification: any): string {
  return notification?.message || notification?.data?.message || notification?.data?.description || 'You have a new update.';
}

function notificationIcon(notification: any): string {
  return notification?.icon || notification?.data?.icon || 'Bell';
}

function toggleSubMenu(index: number) {
  openSubMenus[index] = !openSubMenus[index];
}

function toggleCompactMenu(event: MouseEvent) {
  event.preventDefault();
  setCompactMenu(!compactMenu.value);
}

function isActive(pageName?: string) {
  if (!pageName) return false;

  try {
    const routeUrl = route(pageName);
    return page.url.startsWith(new URL(routeUrl, getSafeOrigin()).pathname);
  } catch {
    return false;
  }
}

function isSubMenuActive(item: MenuItem) {
  if (!item.subMenu) return false;
  return item.subMenu.some((sub) => isActive(sub.pageName));
}

function syncFullscreenState() {
  if (typeof document === 'undefined') return;
  isFullscreen.value = Boolean(document.fullscreenElement);
}

async function requestFullscreen() {
  if (typeof document === 'undefined') return;

  if (document.fullscreenElement) {
    await document.exitFullscreen();
    return;
  }

  const element = document.documentElement;
  if (element.requestFullscreen) {
    await element.requestFullscreen();
  }
}

function toggleDarkMode() {
  updateAppearance(appearance.value === 'dark' ? 'light' : 'dark');
}

function handleGlobalKeydown(event: KeyboardEvent) {
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault();
    openSearch();
  }
}

onMounted(() => {
  syncFullscreenState();
  document.addEventListener('fullscreenchange', syncFullscreenState);
  document.addEventListener('keydown', handleGlobalKeydown);
});

onBeforeUnmount(() => {
  document.removeEventListener('fullscreenchange', syncFullscreenState);
  document.removeEventListener('keydown', handleGlobalKeydown);
  if (toastTimer) clearTimeout(toastTimer);
});
</script>

<template>
  <div
    :class="[
      'raze',
      'before:content-[\'\'] before:bg-linear-to-b before:from-slate-100 before:to-slate-50 dark:before:from-darkmode-800 dark:before:to-darkmode-800 before:h-screen before:w-full before:fixed before:top-0',
    ]"
  >
    <div
      :class="[
        'xl:ml-0 shadow-xl transition-[margin] duration-300 xl:shadow-none fixed top-0 left-0 z-50 side-menu group',
        'after:content-[\'\'] after:fixed after:inset-0 after:bg-black/80 after:xl:hidden',
        { 'side-menu--collapsed': compactMenu },
        { 'side-menu--on-hover': compactMenuOnHover },
        { 'ml-0 after:block': activeMobileMenu },
        { '-ml-[275px] after:hidden': !activeMobileMenu },
      ]"
    >
      <div
        :class="[
          'fixed ml-[275px] w-10 h-10 items-center justify-center xl:hidden z-50',
          { flex: activeMobileMenu },
          { hidden: !activeMobileMenu },
        ]"
      >
        <a href="#" class="mt-5 ml-5" @click.prevent="activeMobileMenu = false">
          <Lucide icon="X" class="w-8 h-8 text-white" />
        </a>
      </div>

      <div
        :class="[
          'bg-linear-to-b from-theme-1 to-theme-2 z-20 relative w-[275px] duration-300 transition-[width] xl:rounded-[0_1.2rem_1.2rem_0/0_1.7rem_1.7rem_0] group-[.side-menu--collapsed]:xl:w-[91px] group-[.side-menu--collapsed.side-menu--on-hover]:xl:shadow-[6px_0_12px_-4px_#0000000f] group-[.side-menu--collapsed.side-menu--on-hover]:xl:w-[275px] overflow-hidden h-screen flex flex-col',
          'after:content-[\'\'] after:absolute after:inset-0 after:-mr-4 after:bg-texture-white after:bg-contain after:bg-fixed after:bg-[center_-20rem] after:bg-no-repeat',
        ]"
        @mouseover.prevent="compactMenuOnHover = true"
        @mouseleave.prevent="compactMenuOnHover = false"
      >
        <div class="flex-none hidden xl:flex items-center z-10 px-5 h-[65px] w-[275px] overflow-hidden relative duration-300 group-[.side-menu--collapsed]:xl:w-[91px] group-[.side-menu--collapsed.side-menu--on-hover]:xl:w-[275px]">
          <Link
            href="/"
            class="flex items-center transition-[margin] duration-300 group-[.side-menu--collapsed]:xl:ml-2 group-[.side-menu--collapsed.side-menu--on-hover]:xl:ml-0"
          >
            <div class="flex items-center justify-center w-[34px] rounded-lg h-[34px] bg-white/8 transition-transform ease-in-out group-[.side-menu--collapsed.side-menu--on-hover]:xl:-rotate-180">
              <Lucide icon="Truck" class="w-5 h-5 text-white" />
            </div>
            <div class="ml-3.5 group-[.side-menu--collapsed.side-menu--on-hover]:xl:opacity-100 group-[.side-menu--collapsed]:xl:opacity-0 transition-opacity font-medium text-white">
              EFService
            </div>
          </Link>
          <a
            href="#"
            class="group-[.side-menu--collapsed.side-menu--on-hover]:xl:opacity-100 group-[.side-menu--collapsed]:xl:rotate-180 group-[.side-menu--collapsed]:xl:opacity-0 transition-[opacity,transform] hidden 3xl:flex items-center justify-center w-[20px] h-[20px] ml-auto border rounded-full border-white/40 text-white hover:bg-white/5"
            @click="toggleCompactMenu"
          >
            <Lucide icon="ArrowLeft" class="w-3.5 h-3.5 stroke-[1.3]" />
          </a>
        </div>

        <div class="w-full h-full z-20 px-5 overflow-y-auto overflow-x-hidden pb-3">
          <ul class="scrollable">
            <template v-for="(item, index) in menu" :key="index">
              <li v-if="typeof item === 'string'" class="side-menu__divider">
                {{ item }}
              </li>

              <li v-else-if="item.subMenu && item.subMenu.length">
                <a
                  href="#"
                  :class="[
                    'side-menu__link',
                    { 'side-menu__link--active': isSubMenuActive(item) },
                    { 'side-menu__link--open': openSubMenus[index] },
                  ]"
                  @click.prevent="toggleSubMenu(index)"
                >
                  <Lucide :icon="item.icon" class="side-menu__link__icon" />
                  <div class="side-menu__link__title">{{ item.title }}</div>
                  <Lucide
                    :icon="openSubMenus[index] ? 'ChevronDown' : 'ChevronRight'"
                    class="side-menu__link__chevron w-4 h-4 ml-auto transition-transform text-white/50"
                  />
                </a>

                <Transition
                  enter-active-class="transition-all duration-300 ease-out overflow-hidden"
                  enter-from-class="max-h-0 opacity-0"
                  enter-to-class="max-h-[2000px] opacity-100"
                  leave-active-class="transition-all duration-200 ease-in overflow-hidden"
                  leave-from-class="max-h-[2000px] opacity-100"
                  leave-to-class="max-h-0 opacity-0"
                >
                  <ul v-show="openSubMenus[index] || isSubMenuActive(item)" class="pl-3 mt-1">
                    <li v-for="(sub, subIdx) in item.subMenu" :key="subIdx">
                      <Link
                        :href="sub.pageName ? route(sub.pageName) : '#'"
                        :class="[
                          'side-menu__link side-menu__link--sub',
                          { 'side-menu__link--active': isActive(sub.pageName) },
                        ]"
                      >
                        <Lucide :icon="sub.icon || 'Circle'" class="side-menu__link__icon !w-3.5 !h-3.5" />
                        <div class="side-menu__link__title text-sm">{{ sub.title }}</div>
                      </Link>
                    </li>
                  </ul>
                </Transition>
              </li>

              <li v-else>
                <Link
                  :href="item.pageName ? route(item.pageName) : '#'"
                  :class="[
                    'side-menu__link',
                    { 'side-menu__link--active': isActive(item.pageName) },
                  ]"
                >
                  <Lucide :icon="item.icon" class="side-menu__link__icon" />
                  <div class="side-menu__link__title">{{ item.title }}</div>
                  <div v-if="item.badge" class="side-menu__link__badge">
                    {{ item.badge }}
                  </div>
                </Link>
              </li>
            </template>
          </ul>
        </div>
      </div>

      <div
        :class="[
          'fixed h-[65px] transition-[margin] duration-100 xl:ml-[275px] group-[.side-menu--collapsed]:xl:ml-[90px] mt-3.5 inset-x-0 top-0',
          'before:content-[\'\'] before:mx-5 before:absolute before:top-0 before:inset-x-0 before:-mt-[15px] before:h-[20px] before:backdrop-blur',
        ]"
      >
        <div class="absolute inset-x-0 h-full mx-5 box dark:bg-darkmode-600 dark:border-darkmode-400 before:content-[''] before:z-[-1] before:inset-x-4 before:shadow-sm before:h-full before:bg-slate-50 dark:before:bg-darkmode-700 before:border before:border-slate-200 dark:before:border-darkmode-500 before:absolute before:rounded-lg before:mx-auto before:top-0 before:mt-3">
          <div class="flex items-center w-full h-full px-5">
            <div class="flex items-center gap-1 xl:hidden">
              <a href="#" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-darkmode-400" @click.prevent="activeMobileMenu = true">
                <Lucide icon="AlignJustify" class="w-[18px] h-[18px]" />
              </a>
              <a href="#" class="rounded-full p-2 hover:bg-slate-100 dark:hover:bg-darkmode-400" @click.prevent="openSearch">
                <Lucide icon="Search" class="h-[18px] w-[18px]" />
              </a>
            </div>

            <!-- BEGIN: Breadcrumb -->
            <Breadcrumb class="flex-1 hidden xl:block">
              <Breadcrumb.Link to="/">App</Breadcrumb.Link>
              <Breadcrumb.Link to="/dashboard">Dashboards</Breadcrumb.Link>
              <Breadcrumb.Link :to="page.url" :active="true">
                {{ page.props.title || 'Dashboard' }}
              </Breadcrumb.Link>
            </Breadcrumb>

            <div class="relative hidden flex-1 justify-center xl:flex" @click.prevent="openSearch">
              <div class="bg-slate-50 dark:bg-darkmode-400 border dark:border-darkmode-400 w-[350px] flex items-center py-2 px-3.5 rounded-[0.5rem] text-slate-400 cursor-pointer hover:bg-slate-100 dark:hover:bg-darkmode-300 transition-colors">
                <Lucide icon="Search" class="w-[18px] h-[18px]" />
                <div class="ml-2.5 mr-auto">Quick search...</div>
                <div>{{ searchShortcutLabel }}</div>
              </div>
            </div>

            <div class="flex flex-1 items-center">
              <div class="flex items-center gap-1 ml-auto">
                <Menu>
                  <Menu.Button as="button" type="button" class="rounded-full p-2 hover:bg-slate-100 dark:hover:bg-darkmode-400">
                    <Lucide icon="LayoutGrid" class="h-[18px] w-[18px]" />
                  </Menu.Button>
                  <Menu.Items class="mt-1 w-80 p-2">
                    <div class="px-3 py-2 border-b border-slate-200/60 dark:border-darkmode-400">
                      <div class="text-sm font-medium text-slate-800 dark:text-slate-100">Quick Links</div>
                      <div class="text-xs text-slate-500">Open the areas you use the most.</div>
                    </div>
                    <div class="grid grid-cols-2 gap-1 p-2">
                      <Link
                        v-for="shortcut in shortcutLinks"
                        :key="shortcut.href"
                        :href="shortcut.href"
                        class="rounded-lg border border-transparent px-3 py-3 text-left transition hover:border-slate-200 hover:bg-slate-50 dark:hover:bg-darkmode-400"
                      >
                        <div class="flex items-center gap-2">
                          <Lucide :icon="shortcut.icon" class="h-4 w-4 text-primary" />
                          <div class="truncate text-sm font-medium text-slate-700 dark:text-slate-200">{{ shortcut.title }}</div>
                        </div>
                        <div class="mt-1 truncate text-[11px] uppercase tracking-[0.12em] text-slate-400">
                          {{ shortcut.section }}
                        </div>
                      </Link>
                    </div>
                  </Menu.Items>
                </Menu>

                <a href="#" class="p-2 rounded-full hover:bg-slate-100 dark:hover:bg-darkmode-400" @click.prevent="toggleDarkMode">
                  <Lucide :icon="appearance === 'dark' ? 'Sun' : 'Moon'" class="w-[18px] h-[18px]" />
                </a>

                <a href="#" class="request-full-screen rounded-full p-2 hover:bg-slate-100 dark:hover:bg-darkmode-400" @click.prevent="requestFullscreen">
                  <Lucide :icon="isFullscreen ? 'Shrink' : 'Expand'" class="w-[18px] h-[18px]" />
                </a>

                <Menu>
                  <Menu.Button as="button" type="button" class="rounded-full p-2 hover:bg-slate-100 dark:hover:bg-darkmode-400 relative">
                    <Lucide icon="Bell" class="h-[18px] w-[18px]" />
                    <span
                      v-if="unreadCount > 0"
                      class="absolute -right-0.5 -top-0.5 inline-flex min-w-[18px] h-[18px] items-center justify-center rounded-full bg-primary px-1 text-[10px] font-medium text-white"
                    >
                      {{ unreadCount > 99 ? '99+' : unreadCount }}
                    </span>
                  </Menu.Button>
                  <Menu.Items class="mt-1 w-96 p-0">
                    <div class="px-4 py-3 border-b border-slate-200/60 dark:border-darkmode-400 flex items-center justify-between">
                      <div>
                        <div class="text-sm font-medium text-slate-800 dark:text-slate-100">Notifications</div>
                        <div class="text-xs text-slate-500">{{ unreadCount }} unread</div>
                      </div>
                      <div class="flex items-center gap-3">
                        <button
                          v-if="unreadCount > 0"
                          type="button"
                          class="text-xs font-medium text-primary hover:text-primary/80"
                          @click="markAllAsRead"
                        >
                          Mark all as read
                        </button>
                        <Link
                          v-if="notificationsIndexHref"
                          :href="notificationsIndexHref"
                          class="text-xs font-medium text-slate-500 hover:text-primary"
                        >
                          View all
                        </Link>
                      </div>
                    </div>
                    <div v-if="notifications.length" class="max-h-[360px] overflow-y-auto p-2">
                      <button
                        v-for="notification in notifications"
                        :key="notification.id"
                        type="button"
                        class="mb-2 flex w-full items-start gap-3 rounded-lg p-3 text-left transition hover:bg-slate-50 dark:hover:bg-darkmode-400"
                        @click="openNotification(notification)"
                      >
                        <div class="mt-0.5 flex h-9 w-9 items-center justify-center rounded-full" :class="notification.read_at ? 'bg-slate-100 text-slate-400' : 'bg-primary/10 text-primary'">
                          <Lucide :icon="notificationIcon(notification)" class="h-4 w-4" />
                        </div>
                        <div class="min-w-0 flex-1">
                          <div class="truncate text-sm font-medium text-slate-700 dark:text-slate-200">
                            {{ notificationTitle(notification) }}
                          </div>
                          <div class="mt-1 line-clamp-2 text-xs text-slate-500">
                            {{ notificationDescription(notification) }}
                          </div>
                          <div class="mt-2 text-[11px] text-slate-400">
                            {{ notification.created_at_human || formatNotificationTime(notification.created_at) }}
                          </div>
                        </div>
                      </button>
                    </div>
                    <div v-else class="px-6 py-10 text-center">
                      <Lucide icon="BellOff" class="mx-auto h-10 w-10 text-slate-300" />
                      <div class="mt-3 text-sm font-medium text-slate-600">You have no notifications</div>
                      <Link
                        v-if="notificationsIndexHref"
                        :href="notificationsIndexHref"
                        class="mt-4 inline-flex text-sm font-medium text-primary hover:text-primary/80"
                      >
                        Open notification center
                      </Link>
                    </div>
                  </Menu.Items>
                </Menu>
              </div>

              <Menu class="ml-5">
                <Menu.Button as="button" type="button" class="image-fit h-[36px] w-[36px] overflow-hidden rounded-full border-[3px] border-slate-200/70">
                  <img
                    v-if="user?.avatar"
                    :src="user.avatar"
                    :alt="user.name"
                    class="h-full w-full object-cover"
                  />
                  <div v-else class="flex h-full w-full items-center justify-center bg-slate-200">
                    <Lucide icon="User" class="h-5 w-5 text-slate-500" />
                  </div>
                </Menu.Button>
                <Menu.Items class="mt-1 w-64 p-0">
                  <div class="px-4 py-4 border-b border-slate-200/60 dark:border-darkmode-400">
                    <div class="font-medium text-slate-800 dark:text-slate-100 truncate">{{ user?.name }}</div>
                    <div class="text-xs text-slate-500 truncate">{{ user?.email }}</div>
                    <div class="mt-2 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="roleBadgeClass">
                      <Lucide :icon="isAdmin ? 'Shield' : isCarrier ? 'Building2' : isDriver ? 'Truck' : 'User'" class="mr-1 h-3 w-3" />
                      {{ roleLabel }}
                    </div>
                  </div>

                  <template v-if="isAdmin">
                    <Menu.Item v-if="profileHref">
                      <Link :href="profileHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="User" class="mr-2 h-4 w-4" />
                        Profile Info
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="emailSettingsHref">
                      <Link :href="emailSettingsHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="Mail" class="mr-2 h-4 w-4" />
                        Email Settings
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="securityHref">
                      <Link :href="securityHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="Lock" class="mr-2 h-4 w-4" />
                        Security
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="rolesHref">
                      <Link :href="rolesHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="ShieldCheck" class="mr-2 h-4 w-4" />
                        Roles & Permissions
                      </Link>
                    </Menu.Item>
                  </template>

                  <template v-else-if="isCarrier">
                    <Menu.Item v-if="profileHref">
                      <Link :href="profileHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="Building2" class="mr-2 h-4 w-4" />
                        Company Profile
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="emailSettingsHref">
                      <Link :href="emailSettingsHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="Settings" class="mr-2 h-4 w-4" />
                        Edit Profile
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="carrierDocumentsHref">
                      <Link :href="carrierDocumentsHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="FileText" class="mr-2 h-4 w-4" />
                        Documents
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="carrierReportsHref">
                      <Link :href="carrierReportsHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="BarChart3" class="mr-2 h-4 w-4" />
                        Reports
                      </Link>
                    </Menu.Item>
                  </template>

                  <template v-else-if="isDriver">
                    <Menu.Item v-if="profileHref">
                      <Link :href="profileHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="User" class="mr-2 h-4 w-4" />
                        My Profile
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="emailSettingsHref">
                      <Link :href="emailSettingsHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="Settings" class="mr-2 h-4 w-4" />
                        Edit Profile
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="driverDocumentsHref">
                      <Link :href="driverDocumentsHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="FileText" class="mr-2 h-4 w-4" />
                        My Documents
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="driverVehiclesHref">
                      <Link :href="driverVehiclesHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="Truck" class="mr-2 h-4 w-4" />
                        My Vehicle
                      </Link>
                    </Menu.Item>
                  </template>

                  <template v-else>
                    <Menu.Item v-if="profileHref">
                      <Link :href="profileHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="User" class="mr-2 h-4 w-4" />
                        Profile Info
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="emailSettingsHref">
                      <Link :href="emailSettingsHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="Mail" class="mr-2 h-4 w-4" />
                        Email Settings
                      </Link>
                    </Menu.Item>
                    <Menu.Item v-if="securityHref">
                      <Link :href="securityHref" class="flex w-full items-center px-4 py-2.5">
                        <Lucide icon="Lock" class="mr-2 h-4 w-4" />
                        Security
                      </Link>
                    </Menu.Item>
                  </template>

                  <Menu.Divider />

                  <Menu.Item>
                    <Link :href="route('logout')" method="post" as="button" class="flex w-full items-center px-4 py-2.5 text-left">
                      <Lucide icon="Power" class="mr-2 h-4 w-4" />
                      Log out
                    </Link>
                  </Menu.Item>
                </Menu.Items>
              </Menu>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Dialog :open="showSearch" @close="closeSearch">
      <Dialog.Panel class="mt-[15vh] w-[90%] max-w-[700px] overflow-hidden rounded-xl bg-white shadow-2xl dark:bg-darkmode-600">
        <div class="flex items-center border-b border-slate-200 px-5 py-4 dark:border-darkmode-400">
          <Lucide icon="Search" class="mr-3 h-5 w-5 text-slate-400" />
          <input
            ref="searchInputRef"
            v-model="searchQuery"
            type="text"
            class="flex-1 border-0 bg-transparent p-0 text-base text-slate-700 outline-none placeholder:text-slate-400 focus:ring-0 dark:text-slate-200"
            placeholder="Quick search..."
            @keydown.escape="closeSearch"
            @keydown.down.prevent="navigateSearch(1)"
            @keydown.up.prevent="navigateSearch(-1)"
            @keydown.enter.prevent="selectSearchResult"
          />
          <div v-if="searchLoading" class="mr-1">
            <Lucide icon="LoaderCircle" class="h-4 w-4 animate-spin text-slate-400" />
          </div>
          <div v-else class="rounded border border-slate-200 px-1.5 py-0.5 text-xs text-slate-400 dark:border-darkmode-400">
            ESC
          </div>
          <div class="p-3 max-h-[50vh] overflow-y-auto">
            <div class="px-2 py-1.5 text-xs font-medium text-slate-400 uppercase">Páginas</div>
            <Link :href="route('dashboard')"
              class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-darkmode-400 cursor-pointer"
              @click="showSearch = false">
              <Lucide icon="LayoutDashboard" class="w-4 h-4 text-slate-500 mr-3" />
              <span class="dark:text-slate-300">Dashboard</span>
            </Link>
            <Link :href="route('profile.edit')"
              class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-darkmode-400 cursor-pointer"
              @click="showSearch = false">
              <Lucide icon="User" class="w-4 h-4 text-slate-500 mr-3" />
              <span class="dark:text-slate-300">Perfil</span>
            </Link>
            <Link :href="route('profile.edit')"
              class="flex items-center px-3 py-2.5 rounded-lg hover:bg-slate-100 dark:hover:bg-darkmode-400 cursor-pointer"
              @click="showSearch = false">
              <Lucide icon="Settings" class="w-4 h-4 text-slate-500 mr-3" />
              <span class="dark:text-slate-300">Configuración</span>
            </Link>
          </div>
        </div>
      </Dialog.Panel>
    </Dialog>

    <div
      :class="[
        'transition-[margin,width] duration-100 px-5 pt-[56px] pb-16 relative z-20',
        { 'xl:ml-[275px]': !compactMenu },
        { 'xl:ml-[91px]': compactMenu },
      ]"
    >
      <div class="container mt-[65px]">
        <slot />
      </div>
    </div>

    <Teleport to="body">
      <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-2 opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-2 opacity-0"
      >
        <div v-if="toastVisible" class="fixed bottom-6 right-6 z-[9999] max-w-sm w-full">
          <div
            class="rounded-xl shadow-lg border px-5 py-4 flex items-start gap-3"
            :class="{
              'bg-white border-emerald-200': toastType === 'success',
              'bg-white border-red-200': toastType === 'error',
              'bg-white border-amber-200': toastType === 'warning',
              'bg-white border-blue-200': toastType === 'info',
            }"
          >
            <div class="flex-shrink-0 mt-0.5">
              <Lucide v-if="toastType === 'success'" icon="CheckCircle" class="w-5 h-5 text-emerald-500" />
              <Lucide v-else-if="toastType === 'error'" icon="XCircle" class="w-5 h-5 text-red-500" />
              <Lucide v-else-if="toastType === 'warning'" icon="AlertTriangle" class="w-5 h-5 text-amber-500" />
              <Lucide v-else icon="Info" class="w-5 h-5 text-blue-500" />
            </div>
            <div class="flex-1 min-w-0">
              <p
                class="text-sm font-medium"
                :class="{
                  'text-emerald-800': toastType === 'success',
                  'text-red-800': toastType === 'error',
                  'text-amber-800': toastType === 'warning',
                  'text-blue-800': toastType === 'info',
                }"
              >
                {{ toastMessage }}
              </p>
            </div>
            <button class="flex-shrink-0 rounded p-0.5 transition hover:bg-slate-100" @click="toastVisible = false">
              <Lucide icon="X" class="w-4 h-4 text-slate-400" />
            </button>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>
