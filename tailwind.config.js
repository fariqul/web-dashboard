/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.jsx",
  ],
  theme: {
    extend: {
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
    },
  },
  plugins: [],
}
