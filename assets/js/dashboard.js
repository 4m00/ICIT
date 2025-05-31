// Simple JavaScript for dashboard functionality

document.addEventListener('DOMContentLoaded', function() {
    // Add hover effect to request cards
    const requestCards = document.querySelectorAll('.request-card');
    requestCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
            this.style.boxShadow = '0 15px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)';
        });
    });
    
    // User dropdown in header
    const userDropdown = document.querySelector('.user-dropdown');
    if (userDropdown) {
        const dropdownBtn = userDropdown.querySelector('.dropdown-btn');
        const dropdownContent = userDropdown.querySelector('.dropdown-content');
        
        dropdownBtn.addEventListener('click', function() {
            dropdownContent.style.display = dropdownContent.style.display === 'block' ? 'none' : 'block';
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            if (!userDropdown.contains(event.target)) {
                dropdownContent.style.display = 'none';
            }
        });
    }
    
    // Handle alert dismissal
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
});