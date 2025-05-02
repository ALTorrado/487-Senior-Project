document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[maxlength]');
    
    inputs.forEach(input => {
        const maxLength = input.getAttribute('maxlength');
        const counter = input.nextElementSibling;
        
        counter.textContent = `${input.value.length}/${maxLength} characters`;
        
        input.addEventListener('input', function() {
            counter.textContent = `${this.value.length}/${maxLength} characters`;
            
            if (this.value.length > maxLength * 0.8) {
                counter.style.color = '#e74c3c';
            } else {
                counter.style.color = '';
            }
        });
    });
});    form.addEventListener('submit', function(e) {
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
;
