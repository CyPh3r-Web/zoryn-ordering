/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    './**/*.php',
    './**/*.html',
    './**/*.js',
  ],
  theme: {
    extend: {
      colors: {
        z: {
          black: '#0D0D0D', dark: '#121212', gray: '#1F1F1F',
          'gray-light': '#2A2A2A', 'gray-lighter': '#333333',
          border: '#2E2E2E', 'border-light': '#3A3A3A',
          gold: '#D4AF37', 'gold-light': '#F5D76E',
          'gold-muted': '#B8921E', 'gold-pale': '#F4D26B',
          'gold-dark': '#A67C00', 'gold-bright': '#FFD700',
          'text-primary': '#FFFFFF', 'text-secondary': '#B0B0B0',
          'text-muted': '#888888', 'text-dark': '#666666',
          success: '#00B894', warning: '#FDCB6E',
          danger: '#FF7675', info: '#74B9FF',
        }
      },
      fontFamily: {
        poppins: ['Poppins', 'sans-serif'],
        montserrat: ['Montserrat', 'sans-serif'],
      },
      boxShadow: {
        'gold': '0 4px 20px rgba(212, 175, 55, 0.15)',
        'gold-lg': '0 8px 30px rgba(212, 175, 55, 0.2)',
        'gold-xl': '0 12px 40px rgba(212, 175, 55, 0.25)',
        'gold-glow': '0 0 20px rgba(212, 175, 55, 0.3)',
        'dark': '0 4px 20px rgba(0, 0, 0, 0.4)',
        'dark-lg': '0 8px 30px rgba(0, 0, 0, 0.5)',
        'glass': '0 8px 32px rgba(0, 0, 0, 0.3)',
        'glass-lg': '0 16px 48px rgba(0, 0, 0, 0.4)',
        'inner-gold': 'inset 0 2px 4px rgba(212, 175, 55, 0.1)',
      },
      backgroundImage: {
        'gold-gradient': 'linear-gradient(135deg, #F4D26B, #C99B2A)',
        'gold-gradient-hover': 'linear-gradient(135deg, #FFDF7D, #D3A533)',
        'gold-shimmer': 'linear-gradient(90deg, #D4AF37, #F5D76E, #D4AF37)',
        'dark-gradient': 'linear-gradient(180deg, #151515, #090909)',
        'dark-radial': 'radial-gradient(circle at top, #1a1a1a, #0D0D0D 60%, #000)',
        'brand-gradient': 'linear-gradient(160deg, #F4D26B 0%, #D4AF37 45%, #B8921E 100%)',
        'glass-gradient': 'linear-gradient(135deg, rgba(31,31,31,0.8), rgba(13,13,13,0.9))',
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-out',
        'fade-in-up': 'fadeInUp 0.4s ease-out',
        'slide-down': 'slideDown 0.3s ease-out',
        'slide-in': 'slideIn 0.3s ease-out',
        'slide-up': 'slideUp 0.3s ease-out',
        'scale-in': 'scaleIn 0.2s ease-out',
        'pulse-gold': 'pulseGold 2s ease-in-out infinite',
        'shimmer': 'shimmer 2s linear infinite',
        'spin-slow': 'spin 3s linear infinite',
        'bounce-in': 'bounceIn 0.5s ease-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' },
        },
        fadeInUp: {
          '0%': { opacity: '0', transform: 'translateY(16px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideDown: {
          '0%': { opacity: '0', transform: 'translateY(-10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        slideIn: {
          '0%': { opacity: '0', transform: 'translateX(-10px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' },
        },
        slideUp: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        },
        scaleIn: {
          '0%': { opacity: '0', transform: 'scale(0.95)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
        pulseGold: {
          '0%, 100%': { boxShadow: '0 0 0 0 rgba(212, 175, 55, 0.2)' },
          '50%': { boxShadow: '0 0 0 8px rgba(212, 175, 55, 0)' },
        },
        shimmer: {
          '0%': { backgroundPosition: '-200% 0' },
          '100%': { backgroundPosition: '200% 0' },
        },
        bounceIn: {
          '0%': { opacity: '0', transform: 'scale(0.3)' },
          '50%': { transform: 'scale(1.05)' },
          '70%': { transform: 'scale(0.9)' },
          '100%': { opacity: '1', transform: 'scale(1)' },
        },
      },
    },
  },
  plugins: [],
}
