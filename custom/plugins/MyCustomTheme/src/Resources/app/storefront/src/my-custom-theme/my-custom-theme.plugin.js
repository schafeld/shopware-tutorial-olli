import Plugin from 'src/plugin-system/plugin.class';

export default class MyCustomThemePlugin extends Plugin {
    init() {
        // Test if JavaScript is working
        console.log('🎯 MyCustomTheme JavaScript plugin is running...!');
        console.log('Location: ', window.location.pathname);

        // Add a visual indicator
        this.addVisualIndicator();
        
        // Add event listeners or other functionality here
        this.bindEvents();
    }

    addVisualIndicator() {
        const body = document.body;
        if (body) {
            body.style.border = '5px solid blue';
            console.log('🔵 Blue border added to body - JavaScript plugin is working!');
        }
    }

    bindEvents() {
        // Example: Add scroll event listener
        window.addEventListener('scroll', this.onScroll.bind(this));
    }

    onScroll() {
        // Example scroll functionality
        // You can add your custom scroll behavior here
    }
}