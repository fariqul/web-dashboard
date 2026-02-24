/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx",
  ],
  theme: {
    extend: {
      fontFamily: {
        display: ['"Plus Jakarta Sans"', 'system-ui', 'sans-serif'],
        sans: ['"DM Sans"', 'system-ui', 'sans-serif'],
      },
      colors: {
        // PLN Brand Colors - Softer Version
        pln: {
          yellow: {
            DEFAULT: '#F5C842',
            50: '#FFFDF5',
            100: '#FEF9E7',
            200: '#FCF0C3',
            300: '#FAE69F',
            400: '#F7D56B',
            500: '#F5C842',
            600: '#E5B82E',
            700: '#C9A128',
          },
          blue: {
            DEFAULT: '#4AADE8',
            50: '#F0F9FF',
            100: '#E0F2FE',
            200: '#BAE6FD',
            300: '#7DD3FC',
            400: '#5BC0EB',
            500: '#4AADE8',
            600: '#3B9DD6',
            700: '#2D87BE',
            800: '#1E6FA3',
          },
          red: {
            DEFAULT: '#E8636B',
            50: '#FFF5F5',
            100: '#FFE8E9',
            200: '#FFCDD0',
            300: '#FFA8AD',
            400: '#F07D84',
            500: '#E8636B',
            600: '#D64F57',
            700: '#BE3F47',
          },
        },
      },
      animation: {
        'fade-in': 'fadeIn 0.5s ease-out forwards',
        'slide-in-left': 'slideInLeft 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards',
        'pulse-glow': 'pulseGlow 2s ease-in-out infinite',
        'scale-in': 'scaleIn 0.2s ease-out forwards',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(8px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideInLeft: {
          '0%': { opacity: '0', transform: 'translateX(-12px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' },
        },
        pulseGlow: {
          '0%, 100%': { boxShadow: '0 0 8px rgba(245, 200, 66, 0.3)' },
          '50%': { boxShadow: '0 0 20px rgba(245, 200, 66, 0.6)' },
        },
        scaleIn: {
          '0%': { opacity: '0', transform: 'scale(0.95)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
      },
    },
  },
  plugins: [],
}
