import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // O app não tem tema escuro. Com o padrão ('media'), as classes `dark:` herdadas
    // do Breeze seguiam o tema do sistema operacional e escureciam inputs/modais
    // dentro de páginas claras. 'class' só ativa com <html class="dark">, que não usamos.
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // sans = corpo (Figtree, mantido)
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                // serif = display editorial (Fraunces)
                serif: ['Fraunces', ...defaultTheme.fontFamily.serif],
                // mono = código/detalhes discretos
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },

            colors: {
                // Neutros editoriais
                paper: '#F5F1E8',      // fundo principal (areia/off-white)
                ink: '#1A1A1A',        // texto principal (preto suave)
                'ink-2': '#4A4A45',    // texto secundário (cinza quente)
                line: '#E5E0D6',       // divisores

                // App Financeiro — paleta sóbria, editorial
                finance: {
                    base: '#0B3D3A',    // verde-petróleo escuro
                    accent: '#B8956A',  // âmbar suave
                    paper: '#FAF7F0',   // areia levemente mais clara que o fundo
                },
            },
        },
    },

    plugins: [forms],
};
