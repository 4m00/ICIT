document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality for admin panel
    const tabBtns = document.querySelectorAll('.tab-btn');
    const tabContents = document.querySelectorAll('.tab-content');
    
    tabBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all buttons
            tabBtns.forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Add active class to clicked button
            this.classList.add('active');
        });
    });
    
    // Modal functionality for role change
    const modal = document.getElementById('roleModal');
    const modalClose = document.querySelector('.modal-close');
    const modalCancel = document.querySelector('.modal-cancel');
    
    // Close modal when clicking X or Cancel
    if (modalClose) {
        modalClose.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    if (modalCancel) {
        modalCancel.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Alert auto-dismiss
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

// Function to open role change modal
function openRoleModal(userId, userName, currentRole) {
    const modal = document.getElementById('roleModal');
    const modalUserName = document.getElementById('modalUserName');
    const modalUserId = document.getElementById('modalUserId');
    const modalUserRole = document.getElementById('modalUserRole');
    
    modalUserName.textContent = userName;
    modalUserId.value = userId;
    modalUserRole.value = currentRole;
    
    modal.style.display = 'block';
}