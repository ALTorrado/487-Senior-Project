document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input[maxlength]');
    
    inputs.forEach(input => {
        const maxLength = input.getAttribute('maxlength');
        const counter = input.nextElementSibling;
        
        if (counter && counter.classList.contains('char-count')) {
            counter.textContent = `${input.value.length}/${maxLength} characters`;
            
            input.addEventListener('input', function() {
                counter.textContent = `${this.value.length}/${maxLength} characters`;
                
                if (this.value.length > maxLength * 0.8) {
                    counter.style.color = '#e74c3c';
                } else {
                    counter.style.color = '';
                }
            });
        }
    });
    
    const passwordField = document.getElementById('password');
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const counter = this.nextElementSibling;
            const length = this.value.length;
            const maxLength = this.getAttribute('maxlength');
            
            counter.textContent = `${length}/${maxLength} characters`;
            
            if (length < 6) {
                counter.style.color = '#e74c3c'; 
            } else if (length < 10) {
                counter.style.color = '#f39c12'; 
            } else {
                counter.style.color = '#27ae60'; 
            }
        });
    }
});
document.querySelectorAll('.approve-employee').forEach(btn => {
    btn.addEventListener('click', () => {
        if (confirm('Are you sure you want to approve this employee?')) {
            updateEmployeeStatus(btn.dataset.id, 1, true);
        }
    });
});

document.querySelectorAll('.reject-employee').forEach(btn => {
    btn.addEventListener('click', () => {
        if (confirm('Are you sure you want to reject this employee?')) {
            updateEmployeeStatus(btn.dataset.id, 2, true);
        }
    });
});

function updateEmployeeStatus(userId, status, refreshPendingTab = false) {
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = `
        <input type="hidden" name="action" value="update_employee_status">
        <input type="hidden" name="user_id" value="${userId}">
        <input type="hidden" name="status" value="${status}">
        ${refreshPendingTab ? '<input type="hidden" name="refresh_tab" value="pending">' : ''}
    `;
    document.body.appendChild(form);
    form.submit();
}
