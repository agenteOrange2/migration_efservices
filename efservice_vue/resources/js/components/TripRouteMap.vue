<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'

interface LatLng {
    lat: number
    lng: number
}

const props = defineProps<{
    gpsRoute?: LatLng[]
    originLat?: number | null
    originLng?: number | null
    destinationLat?: number | null
    destinationLng?: number | null
    originAddress?: string | null
    destinationAddress?: string | null
}>()

const mapEl = ref<HTMLElement | null>(null)
let mapInstance: any = null

async function initMap() {
    if (!mapEl.value) return

    const L = (await import('leaflet')).default
    await import('leaflet/dist/leaflet.css')

    // Fix Leaflet marker icon paths broken by Vite
    delete (L.Icon.Default.prototype as any)._getIconUrl
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
        iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
        shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
    })

    const hasGpsRoute = Array.isArray(props.gpsRoute) && props.gpsRoute.length > 0
    const hasOrigin = props.originLat != null && props.originLng != null
    const hasDestination = props.destinationLat != null && props.destinationLng != null

    const center: [number, number] = hasGpsRoute
        ? [props.gpsRoute![0].lat, props.gpsRoute![0].lng]
        : hasOrigin
            ? [props.originLat as number, props.originLng as number]
            : [39.5, -98.35] // USA fallback

    mapInstance = L.map(mapEl.value).setView(center, hasGpsRoute || hasOrigin ? 11 : 4)

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(mapInstance)

    // Route polyline — solid blue if GPS points exist, dashed grey if only origin/destination
    if (hasGpsRoute) {
        const points: [number, number][] = props.gpsRoute!.map(p => [p.lat, p.lng])
        const poly = L.polyline(points, { color: '#3b82f6', weight: 5, opacity: 0.85 }).addTo(mapInstance)
        mapInstance.fitBounds(poly.getBounds(), { padding: [40, 40] })
    } else if (hasOrigin && hasDestination) {
        const pts: [number, number][] = [
            [props.originLat as number, props.originLng as number],
            [props.destinationLat as number, props.destinationLng as number],
        ]
        const poly = L.polyline(pts, { color: '#94a3b8', weight: 3, opacity: 0.7, dashArray: '10,8' }).addTo(mapInstance)
        mapInstance.fitBounds(poly.getBounds(), { padding: [60, 60] })
    }

    // Helper to create a colored dot marker
    const dot = (color: string) => L.divIcon({
        html: `<div style="width:14px;height:14px;border-radius:50%;background:${color};border:3px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.4)"></div>`,
        iconSize: [14, 14],
        iconAnchor: [7, 7],
        popupAnchor: [0, -12],
        className: '',
    })

    // Origin / start marker (green)
    const originLat = hasGpsRoute ? props.gpsRoute![0].lat : props.originLat
    const originLng = hasGpsRoute ? props.gpsRoute![0].lng : props.originLng
    if (originLat != null && originLng != null) {
        L.marker([originLat as number, originLng as number], { icon: dot('#22c55e') })
            .bindPopup(`<b>Start</b>${props.originAddress ? '<br><small>' + props.originAddress + '</small>' : ''}`)
            .addTo(mapInstance)
    }

    // End / destination marker (red if actual GPS end, orange if only planned destination)
    const lastPt = hasGpsRoute ? props.gpsRoute![props.gpsRoute!.length - 1] : null
    const destLat = lastPt ? lastPt.lat : props.destinationLat
    const destLng = lastPt ? lastPt.lng : props.destinationLng
    if (destLat != null && destLng != null) {
        const color = hasGpsRoute ? '#ef4444' : '#f97316'
        const label = hasGpsRoute ? 'End' : 'Destination'
        L.marker([destLat as number, destLng as number], { icon: dot(color) })
            .bindPopup(`<b>${label}</b>${props.destinationAddress ? '<br><small>' + props.destinationAddress + '</small>' : ''}`)
            .addTo(mapInstance)
    }
}

onMounted(initMap)
onUnmounted(() => { mapInstance?.remove(); mapInstance = null })
</script>

<template>
    <div ref="mapEl" style="height: 320px; width: 100%; border-radius: 12px; z-index: 0;" />
</template>
