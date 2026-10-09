const { copyFileSync } = require('node:fs');

copyFileSync('src/resources/views/modern/tailwind.blade.php', 'src/resources/assets/tailwind.css');
