const mix = require('laravel-mix');

// Галерея: Bootstrap (SCSS + JS) — результат помещается в public/
mix.js('resources/js/gallery.js', 'public/js')
   .sass('resources/scss/app.scss', 'public/css', {
       sassOptions: {
           quietDeps: true,
           silenceDeprecations: ['import', 'global-builtin', 'color-functions'],
       },
   });

// Laravel Breeze (страницы входа, регистрации, профиля): Tailwind CSS + Alpine.js
mix.js('resources/js/app.js', 'public/js/breeze.js')
   .postCss('resources/css/app.css', 'public/css/breeze.css', [
       require('tailwindcss'),
       require('autoprefixer'),
   ]);

mix.version();
