/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['"Hanken Grotesk"', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        mono: ['"JetBrains Mono"', 'ui-monospace', 'monospace'],
      },
      colors: {
        // Warm paper / ink foundation.
        paper: '#F6F5F1',
        surface: '#FFFFFF',
        ink: '#1B1A17',
        line: '#E7E3DA',
        accent: '#C2410C', // burnt-amber spark, used sparingly
        // "brand" remapped to a warm ink/stone ramp so the primary UI is monochrome.
        brand: {
          50: '#F4F2EE', 100: '#E7E3DA', 200: '#D6D0C4', 300: '#B8B0A0',
          400: '#8A8175', 500: '#57534C', 600: '#2A2723', 700: '#1B1A17',
          800: '#121110', 900: '#0A0908',
        },
      },
      boxShadow: {
        card: '0 1px 2px rgba(27,26,23,0.04), 0 1px 3px rgba(27,26,23,0.06)',
        lift: '0 4px 16px -4px rgba(27,26,23,0.12)',
      },
      keyframes: {
        'fade-up': {
          '0%': { opacity: '0', transform: 'translateY(8px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
      },
      animation: {
        'fade-up': 'fade-up 0.5s cubic-bezier(0.16,1,0.3,1) both',
      },
    },
  },
  plugins: [],
}
