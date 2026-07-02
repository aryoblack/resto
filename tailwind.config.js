/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: '#FF7A2F',
                    50: '#FFF3EC',
                    100: '#FFE4D0',
                    200: '#FFC9A1',
                    300: '#FFAD72',
                    400: '#FF9243',
                    500: '#FF7A2F',
                    600: '#E85E10',
                    700: '#C04A0C',
                    800: '#983A09',
                    900: '#702B07',
                },
                secondary: {
                    DEFAULT: '#2EC4B6',
                    50: '#EDFAF9',
                    100: '#D0F4F1',
                    200: '#A1E9E3',
                    300: '#72DED5',
                    400: '#43D3C7',
                    500: '#2EC4B6',
                    600: '#22A99C',
                    700: '#1A8880',
                    800: '#136764',
                    900: '#0C4644',
                },
                background: '#F8F9FA',
                text: {
                    DEFAULT: '#2D3436',
                    secondary: '#636E72',
                },
                success: '#00B894',
                warning: '#FF7675',
            },
            fontFamily: {
                sans: ['Inter', 'sans-serif'],
                heading: ['Poppins', 'sans-serif'],
            },
            borderRadius: {
                card: '16px',
                button: '30px',
                input: '12px',
            },
            boxShadow: {
                card: '0px 4px 12px rgba(0, 0, 0, 0.05)',
            },
        },
    },

    plugins: [],
};
