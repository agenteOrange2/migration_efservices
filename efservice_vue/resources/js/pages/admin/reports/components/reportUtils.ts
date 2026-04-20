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
    if ([
        'active',
        'approved',
        'completed',
        'complete',
        'pass',
        'passed',
        'valid',
        'verified',
        'negative',
        'acknowledged',
        'compliant',
    ].includes(normalized)) {
        return 'bg-success/10 text-success'
    }
    if ([
        'pending',
        'in_progress',
        'draft',
        'assigned',
        'minor',
        'moderate',
        'upcoming',
        'scheduled',
        'under_review',
        'in process',
        'in_process',
        'unacknowledged',
    ].includes(normalized)) {
        return 'bg-warning/10 text-warning'
    }
    if ([
        'inactive',
        'rejected',
        'overdue',
        'rolled_back',
        'critical',
        'out_of_service',
        'suspended',
        'fail',
        'failed',
        'expired',
        'fatal',
        'fatalities',
        'cancelled',
        'canceled',
        'positive',
    ].includes(normalized)) {
        return 'bg-danger/10 text-danger'
    }
    if ([
        'new',
        'total',
        'registered',
        'logs',
        'open',
        'info',
    ].includes(normalized)) {
        return 'bg-info/10 text-info'
    }
    return 'bg-slate-100 text-slate-600'
}
