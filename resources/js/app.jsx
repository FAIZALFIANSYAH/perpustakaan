import '../css/app.css';
import './bootstrap';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.{jsx,tsx}');
        console.log('Available pages:', Object.keys(pages));
        console.log('Looking for:', `./Pages/${name}`);
        
        const pagePath = `./Pages/${name}.jsx`;
        if (pages[pagePath]) {
            console.log('Found page:', pagePath);
            return pages[pagePath]();
        }
        
        const tsxPath = `./Pages/${name}.tsx`;
        if (pages[tsxPath]) {
            console.log('Found TSX page:', tsxPath);
            return pages[tsxPath]();
        }
        
        console.error('Page not found:', `./Pages/${name}`);
        throw new Error(`Page not found: ./Pages/${name}`);
    },
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(<App {...props} />);
    },
    progress: {
        color: '#4B5563',
    },
});
