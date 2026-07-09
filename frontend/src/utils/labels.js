// Display labels + colour classes for the task enums (mirrors the backend constants).

// Each entry carries a `dot` colour; the Badge renders a hairline pill + dot.
export const STATUS = {
  not_started: { label: 'Not Started', dot: 'bg-stone-400' },
  in_progress: { label: 'In Progress', dot: 'bg-blue-500' },
  on_hold: { label: 'On Hold', dot: 'bg-amber-500' },
  completed: { label: 'Completed', dot: 'bg-emerald-500' },
  cancelled: { label: 'Cancelled', dot: 'bg-rose-400' },
}

export const PRIORITY = {
  low: { label: 'Low', dot: 'bg-stone-400' },
  medium: { label: 'Medium', dot: 'bg-sky-500' },
  high: { label: 'High', dot: 'bg-orange-500' },
  critical: { label: 'Critical', dot: 'bg-rose-500' },
}

export const TYPE = {
  development: 'Development',
  bug_fix: 'Bug Fix',
  testing: 'Testing',
  meeting: 'Meeting',
  documentation: 'Documentation',
  research: 'Research',
  others: 'Others',
}

export const STATUS_OPTIONS = Object.entries(STATUS).map(([value, v]) => ({ value, label: v.label }))
export const PRIORITY_OPTIONS = Object.entries(PRIORITY).map(([value, v]) => ({ value, label: v.label }))
export const TYPE_OPTIONS = Object.entries(TYPE).map(([value, label]) => ({ value, label }))

export function formatDate(d) {
  if (!d) return '—'
  return new Date(d).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })
}

export function formatDateTime(d) {
  if (!d) return '—'
  return new Date(d).toLocaleString('en-GB', {
    day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
  })
}

export function formatBytes(bytes) {
  if (!bytes) return '0 B'
  const units = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return `${(bytes / 1024 ** i).toFixed(1)} ${units[i]}`
}
