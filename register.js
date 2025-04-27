document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const name = document.getElementById('name').value.trim();
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;
        const companyCode = document.getElementById('company_code').value.trim();

        if (!name || !email || !password || !companyCode) {
            alert('Please fill in all fields');
            return;
        }

        if (!isValidEmail(email)) {
            alert('Please enter a valid email address');
            return;
        }

        if (password.length < 8) {
            alert('Password must be at least 8 characters long');
            return;
        }

        this.submit();
    });

    function isValidEmail(email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailPattern.test(email);
    }
});

document.querySelectorAll('.approve-employee').forEach(btn => {
    btn.addEventListener('click', () => {
        if (confirm('Are you sure you want to approve this employee?')) {
            updateEmployeeStatus(btn.dataset.id, 1, true); // 1 = Approved, true = refresh pending tab
        }
    });
});

document.querySelectorAll('.reject-employee').forEach(btn => {
    btn.addEventListener('click', () => {
        if (confirm('Are you sure you want to reject this employee?')) {
            updateEmployeeStatus(btn.dataset.id, 2, true); // 2 = Rejected, true = refresh pending tab
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
