/**
 * Umami Analytics Loader (for HTML pages)
 */
(function() {
    // Umami Configuration
    const UMAMI_SCRIPT_URL = 'https://cloud.umami.is/script.js';
    const UMAMI_WEBSITE_ID = '8f0c5c95-1845-4d81-b8ea-7502db23f587';
    
    // Load Umami Analytics
    if (UMAMI_SCRIPT_URL && UMAMI_WEBSITE_ID) {
        const script = document.createElement('script');
        script.defer = true;
        script.src = UMAMI_SCRIPT_URL;
        script.setAttribute('data-website-id', UMAMI_WEBSITE_ID);
        document.head.appendChild(script);
    }
})();

