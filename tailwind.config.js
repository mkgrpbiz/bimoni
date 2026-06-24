import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    safelist: [
        // Application ステータスバッジ（PHPメソッドから動的に返すため）
        'bg-yellow-500', 'bg-purple-500', 'bg-indigo-500', 'bg-orange-500',
        'bg-teal-500', 'bg-gray-500', 'bg-green-500', 'bg-red-500',
        'bg-blue-500', 'bg-pink-500',
        // 精算・フォームフィールド等のバッジ
        'bg-yellow-400', 'bg-green-400', 'bg-red-400',
        // テキスト
        'text-white',
    ],

    plugins: [forms],
};
