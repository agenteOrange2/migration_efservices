import { ref, onUnmounted } from 'vue'

declare function route(name: string, params?: any): string

interface GpsOptions {
    tripId: number
    intervalSeconds?: number
    routeName?: string
}

export type GpsPermissionState = 'unknown' | 'granted' | 'denied' | 'prompt'

export function useGpsTracking(options: GpsOptions) {
    const { tripId, intervalSeconds = 30, routeName = 'driver.trips.gps.record' } = options

    const isTracking    = ref(false)
    const lastPoint     = ref<{ lat: number; lng: number; speed: number | null } | null>(null)
    const error         = ref<string | null>(null)
    const permissionState = ref<GpsPermissionState>('unknown')

    let watchId: number | null = null
    let sendInterval: ReturnType<typeof setInterval> | null = null
    let pendingPoint: GeolocationPosition | null = null

    // Check current browser permission without triggering the dialog
    async function checkPermission(): Promise<GpsPermissionState> {
        if (!navigator.permissions) return 'unknown'
        try {
            const status = await navigator.permissions.query({ name: 'geolocation' })
            permissionState.value = status.state as GpsPermissionState
            // React to future permission changes (e.g. user enables it in browser settings)
            status.onchange = () => {
                permissionState.value = status.state as GpsPermissionState
                // Auto-restart tracking if permission was just granted while on the page
                if (status.state === 'granted' && !isTracking.value) {
                    start()
                }
            }
            return status.state as GpsPermissionState
        } catch {
            return 'unknown'
        }
    }

    function start() {
        if (!navigator.geolocation) {
            error.value = 'Geolocation is not supported by this browser.'
            return
        }

        // Clean up any previous watch before starting a new one
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId)
            watchId = null
        }
        if (sendInterval !== null) {
            clearInterval(sendInterval)
            sendInterval = null
        }

        error.value = null

        watchId = navigator.geolocation.watchPosition(
            (pos) => {
                // First successful fix — mark as tracking
                isTracking.value = true
                permissionState.value = 'granted'
                error.value = null
                pendingPoint = pos
                lastPoint.value = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    speed: pos.coords.speed ? Math.round(pos.coords.speed * 2.237) : null,
                }
            },
            (err) => {
                isTracking.value = false
                if (err.code === err.PERMISSION_DENIED) {
                    permissionState.value = 'denied'
                    error.value = 'GPS permission denied. Please enable location access in your browser settings and tap "Retry GPS".'
                } else if (err.code === err.TIMEOUT) {
                    error.value = 'GPS timed out. Make sure you are in an area with GPS signal and tap "Retry GPS".'
                } else {
                    error.value = 'GPS unavailable. Tap "Retry GPS" to try again.'
                }
            },
            {
                enableHighAccuracy: true,
                maximumAge: 15000,
                timeout: 20000,
            }
        )

        // Only start sending interval after first successful point
        sendInterval = setInterval(sendPoint, intervalSeconds * 1000)
        setTimeout(sendPoint, 3000)
    }

    async function sendPoint() {
        if (!pendingPoint) return

        const pos = pendingPoint

        try {
            await fetch(route(routeName, tripId), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': getCsrfToken(),
                },
                body: JSON.stringify({
                    latitude: pos.coords.latitude,
                    longitude: pos.coords.longitude,
                    speed: pos.coords.speed ? +(pos.coords.speed * 2.237).toFixed(1) : null,
                    heading: pos.coords.heading ?? null,
                    recorded_at: new Date(pos.timestamp).toISOString(),
                }),
            })
        } catch {
            // GPS points are best-effort — silently continue
        }
    }

    function stop() {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId)
            watchId = null
        }
        if (sendInterval !== null) {
            clearInterval(sendInterval)
            sendInterval = null
        }
        isTracking.value = false
        pendingPoint = null
    }

    function getCsrfToken(): string {
        const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
        return match ? decodeURIComponent(match[1]) : ''
    }

    onUnmounted(stop)

    return { isTracking, lastPoint, error, permissionState, checkPermission, start, stop }
}
