import DOMPurify from 'dompurify'

// Sanitized HTML for v-html rendering of user-authored rich text.
// Legacy plain-text values (pre rich-text) are escaped and get <br> newlines.
export function richHtml(value) {
  if (!value) return ''
  const html = /<[a-z][\s\S]*>/i.test(value)
    ? value
    : value.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/\n/g, '<br>')
  return DOMPurify.sanitize(html)
}
