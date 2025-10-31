import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        return pages[`./Pages/${name}.jsx`];
    },
    setup({ el, App, props }) {
        // Initialize Ziggy with routes from Inertia props
        if (props.initialPage.props?.ziggy) {
            globalThis.Ziggy = props.initialPage.props.ziggy;
        }
        
        createRoot(el).render(<App {...props} />);
    },
});
