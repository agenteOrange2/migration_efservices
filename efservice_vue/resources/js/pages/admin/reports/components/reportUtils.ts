export interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

export const pickerOptions = {
    autoApply: true,
    singleMode: true,
    numberOfColumns: 1,
    numberOfMonths: 1,
    format: 'M/D/YYYY',
    dropdowns: {
        minYear: 1990,
        maxYear: 2035,
        months: true,
        years: true,
    },
}

export function money(value: number | string | null | undefined) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value || 0))
}

export function labelize(value: string | null | undefined) {
    return String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (char) => char.toUpperCase())
}

export function statusTone(value: string | null | undefined) {
    const normalized = String(value || '').toLowerCase()
    if (['active', 'approved', 'completed'].includes(normalized)) {
        return 'bg-primary/10 text-primary'
    }
    if (['pending', 'in_progress', 'draft', 'assigned', 'minor', 'moderate'].includes(normalized)) {
        return 'bg-slate-100 text-slate-700'
    }
    if (['inactive', 'rejected', 'overdue', 'rolled_back', 'critical', 'out_of_service', 'suspended'].includes(normalized)) {
        return 'bg-slate-200 text-slate-700'
    }
    return 'bg-slate-100 text-slate-600'
}
