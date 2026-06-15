export function formatDate(value: string | Date | null | undefined, locale = 'id-ID') {
  if (!value) return '-'
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? '-' : new Intl.DateTimeFormat(locale, { dateStyle: 'medium' }).format(date)
}

export function formatDateTime(value: string | Date | null | undefined, locale = 'id-ID') {
  if (!value) return '-'
  const date = value instanceof Date ? value : new Date(value)
  return Number.isNaN(date.getTime()) ? '-' : new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' }).format(date)
}

export function formatRupiah(value: number | string | null | undefined) {
  const amount = typeof value === 'string' ? Number(value) : value
  if (amount === null || amount === undefined || Number.isNaN(amount)) return 'Rp0'
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(amount)
}

export function formatPhoneNumber(value: string | null | undefined) {
  if (!value) return '-'
  const digits = value.replace(/\D/g, '')
  if (digits.startsWith('62')) return `+${digits}`
  if (digits.startsWith('0')) return `+62${digits.slice(1)}`
  return value.trim()
}

export function formatDuration(minutes: number | null | undefined) {
  if (!minutes || minutes < 1) return '0 menit'
  const hours = Math.floor(minutes / 60)
  const remainder = minutes % 60
  if (!hours) return `${remainder} menit`
  return remainder ? `${hours} jam ${remainder} menit` : `${hours} jam`
}
