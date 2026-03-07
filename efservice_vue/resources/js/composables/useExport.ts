import { ref } from 'vue'
import axios from 'axios'

type ExportFormat = 'pdf' | 'excel' | 'csv'

interface ExportOptions {
    routeName: string
    routeParams?: Record<string, unknown>
    filters?: Record<string, unknown>
    filename?: string
}

export function useExport() {
    const exporting = ref(false)
    const exportError = ref<string | null>(null)

    async function exportData(format: ExportFormat, options: ExportOptions) {
        exporting.value = true
        exportError.value = null

        try {
            const params = {
                ...options.filters,
                format,
            }

            const response = await axios.get(
                route(options.routeName, options.routeParams),
                {
                    params,
                    responseType: 'blob',
                },
            )

            const contentDisposition = response.headers['content-disposition']
            let filename = options.filename ?? `export.${format === 'excel' ? 'xlsx' : format}`

            if (contentDisposition) {
                const match = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/)
                if (match?.[1]) {
                    filename = match[1].replace(/['"]/g, '')
                }
            }

            const blob = new Blob([response.data])
            const url = window.URL.createObjectURL(blob)
            const link = document.createElement('a')
            link.href = url
            link.download = filename
            document.body.appendChild(link)
            link.click()
            document.body.removeChild(link)
            window.URL.revokeObjectURL(url)
        } catch (error) {
            exportError.value = 'Export failed. Please try again.'
            console.error('Export error:', error)
        } finally {
            exporting.value = false
        }
    }

    function exportPdf(options: ExportOptions) {
        return exportData('pdf', options)
    }

    function exportExcel(options: ExportOptions) {
        return exportData('excel', options)
    }

    function exportCsv(options: ExportOptions) {
        return exportData('csv', options)
    }

    return {
        exporting,
        exportError,
        exportData,
        exportPdf,
        exportExcel,
        exportCsv,
    }
}
