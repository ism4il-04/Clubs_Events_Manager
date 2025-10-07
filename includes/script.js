function navigateTo(page) {
    // Remove active class from all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Add active class to clicked tab
    event.target.classList.add('active');
    
    // Navigate to the selected page
    window.location.href = page;
}

// Add hover effects for better UX
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.tab');
    
    tabs.forEach(tab => {
        // Add click feedback
        tab.addEventListener('mousedown', function() {
            this.style.transform = 'translateY(-1px) scale(0.98)';
        });
        
        tab.addEventListener('mouseup', function() {
            this.style.transform = '';
        });
        
        tab.addEventListener('mouseleave', function() {
            this.style.transform = '';
        });
    });
});