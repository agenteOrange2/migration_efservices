import { ref, onUnmounted } from 'vue'

declare function route(name: string, params?: any): string

interface GpsOptions {
    tripId: number
    intervalSeconds?: number  // how often to send a point (default 30s)
    routeName?: string
}

export function useGpsTracking(options: GpsOptions) {
    const { tripId, intervalSeconds = 30, routeName = 'driver.trips.gps.record' } = options

    const isTracking = ref(false)
    const lastPoint = ref<{ lat: number; lng: number; speed: number | null } | null>(null)
    const error = ref<string | null>(null)

    let watchId: number | null = null
    let sendInterval: ReturnType<typeof setInterval> | null = null
    let pendingPoint: GeolocationPosition | null = null

    function start() {
        if (!navigator.geolocation) {
            error.value = 'Geolocation is not supported by this browser.'
            return
        }

        error.value = null
        isTracking.value = true

        // Watch position continuously — stores latest in pendingPoint
        watchId = navigator.geolocation.watchPosition(
            (pos) => {
                pendingPoint = pos
                lastPoint.value = {
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    speed: pos.coords.speed ? Math.round(pos.coords.speed * 2.237) : null, // m/s → mph
                }
            },
            (err) => {
                error.value = err.message
            },
            {
                enableHighAccuracy: true,
                maximumAge: 15000,
                timeout: 20000,
            }
        )

        // Send point to server every N seconds
        sendInterval = setInterval(sendPoint, intervalSeconds * 1000)

        // Send first point immediately after a short delay
        setTimeout(sendPoint, 3000)
    }

    async function sendPoint() {
        if (!pendingPoint || !isTracking.value) return

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
            // Silently fail — GPS points are best-effort, not critical
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

    return { isTracking, lastPoint, error, start, stop }
}
