// Session Manager - Prevent back button issues and maintain login state
(function() {
    'use strict';
    
    // Check if we're on a page that requires authentication
    const protectedPages = [
        'passenger_profile.php',
        'profile.html',
        'booking.html',
        'book.php',
        'profile.php'
    ];
    
    const currentPage = window.location.pathname.split('/').pop();
    
    // Only run on protected pages
    if (protectedPages.includes(currentPage)) {
        // Prevent caching
        window.history.pushState(null, null, window.location.href);
        window.addEventListener('popstate', function(event) {
            window.history.pushState(null, null, window.location.href);
        });
        
        // Periodically check session status
        setInterval(checkSession, 60000); // Check every minute
        
        // Initial check
        checkSession();
    }
    
    function checkSession() {
        // Use the dedicated endpoint to check session status
        fetch('check_session.php')
            .then(response => response.json())
            .then(data => {
                if (!data.logged_in) {
                    // Redirect to login
                    window.location.href = 'login.html?session_expired=1';
                }
            })
            .catch(error => {
                console.log('Session check error:', error);
            });
    }
    
    // Disable back button completely on protected pages
    if (protectedPages.includes(currentPage)) {
        // Add to browser history
        window.history.pushState(null, "", window.location.href);
        
        // Listen for back button press
        window.addEventListener('popstate', function(event) {
            // Add current page back to history
            window.history.pushState(null, "", window.location.href);
        });
    }
})();