document.addEventListener('DOMContentLoaded', function() {
    const registerBtn = document.querySelector('.register-btn');
    const form = document.querySelector('form');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const companyName = document.getElementById('company_name').value.trim();
        const companyEmail = document.getElementById('company_email').value.trim();

        if (!companyName || !companyEmail) {
            alert('Please fill in all fields');
            return;
        }

        if (!isValidEmail(companyEmail)) {
            alert('Please enter a valid email address');
            return;
        }

        this.submit();
    });

    function isValidEmail(email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailPattern.test(email);
    }
});
