/**
 * Active Page Detection
 * This script provides a fallback method for highlighting the active page in the sidebar.
 * It can be included in all admin pages to ensure the active page is highlighted correctly.
 */

document.addEventListener('DOMContentLoaded', function() {
    // Get the current page filename
    const currentPage = window.location.pathname.split('/').pop();
    
    // Find all sidebar items
    const sidebarItems = document.querySelectorAll('.sidebar-item');
    
    // Loop through each sidebar item
    sidebarItems.forEach(item => {
        // Get the link inside the sidebar item
        const link = item.querySelector('a');
        
        if (link) {
            // Get the href attribute
            const href = link.getAttribute('href');
            
            // Check if the href matches the current page
            if (href === currentPage) {
                // Add the active class to the sidebar item
                item.classList.add('active');
            }
        }
    });
}); 