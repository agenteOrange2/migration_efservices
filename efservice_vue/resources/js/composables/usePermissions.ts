import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export function usePermissions() {
    const page = usePage()

    const user = computed(() => page.props.auth?.user as {
        roles?: string[]
        permissions?: string[]
        all_permissions?: string[]
    } | null)

    const roles = computed(() => user.value?.roles ?? [])
    const permissions = computed(() => user.value?.all_permissions ?? [])

    function hasRole(role: string): boolean {
        return roles.value.includes(role)
    }

    function hasAnyRole(...rolesToCheck: string[]): boolean {
        return rolesToCheck.some(role => roles.value.includes(role))
    }

    function can(permission: string): boolean {
        if (hasRole('superadmin')) return true
        return permissions.value.includes(permission)
    }

    function canAny(...perms: string[]): boolean {
        if (hasRole('superadmin')) return true
        return perms.some(p => permissions.value.includes(p))
    }

    function canAll(...perms: string[]): boolean {
        if (hasRole('superadmin')) return true
        return perms.every(p => permissions.value.includes(p))
    }

    const isSuperAdmin = computed(() => hasRole('superadmin'))
    const isCarrier = computed(() => hasRole('user_carrier'))
    const isDriver = computed(() => hasRole('user_driver'))

    return {
        user,
        roles,
        permissions,
        hasRole,
        hasAnyRole,
        can,
        canAny,
        canAll,
        isSuperAdmin,
        isCarrier,
        isDriver,
    }
}
