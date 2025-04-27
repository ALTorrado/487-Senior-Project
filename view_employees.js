document.addEventListener('DOMContentLoaded', function() {
   
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        modal.style.display = 'block';
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    function closeAllModals() {
        document.querySelectorAll('.modal').forEach(modal => {
            modal.style.display = 'none';
        });
    }

    
    document.querySelectorAll('.close-modal, .cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            closeAllModals();
        });
    });

    
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal')) {
            closeAllModals();
        }
    });

   
    const addEmployeeBtn = document.getElementById('add-employee-btn');
    if (addEmployeeBtn) {
        addEmployeeBtn.addEventListener('click', () => {
            document.getElementById('add-employee-form').reset();
            openModal('add-employee-modal');
        });
    }

    
    document.querySelectorAll('.edit-employee').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            const name = btn.dataset.name;
            const email = btn.dataset.email;
            const role = btn.dataset.role;
            const status = btn.dataset.status;
            
            document.getElementById('edit-employee-id').value = id;
            document.getElementById('edit-employee-name').value = name;
            document.getElementById('edit-employee-email').value = email;
            document.getElementById('edit-employee-password').value = '';
            document.getElementById('edit-employee-role').value = role;
            document.getElementById('edit-employee-status').value = status;
            
            openModal('edit-employee-modal');
        });
    });

    
    document.querySelectorAll('.delete-employee').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.id;
            document.getElementById('delete-employee-id').value = id;
            openModal('delete-employee-modal');
        });
    });

    
    const addEmployeeForm = document.getElementById('add-employee-form');
    if (addEmployeeForm) {
        addEmployeeForm.addEventListener('submit', function(e) {
            const email = document.getElementById('employee-email').value;
            const password = document.getElementById('employee-password').value;
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return;
            }
        });
    }
    
    const editEmployeeForm = document.getElementById('edit-employee-form');
    if (editEmployeeForm) {
        editEmployeeForm.addEventListener('submit', function(e) {
            const email = document.getElementById('edit-employee-email').value;
            const password = document.getElementById('edit-employee-password').value;
            
            if (!isValidEmail(email)) {
                e.preventDefault();
                alert('Please enter a valid email address');
                return;
            }
            
            if (password && password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters long');
                return;
            }
        });
    }
    
    
    function isValidEmail(email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailPattern.test(email);
    }
    
    
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const container = alert.parentElement;
            if (container) {
                container.style.display = 'none';
            }
        }, 5000);
    });

    
    document.querySelectorAll('.close-alert').forEach(btn => {
        btn.addEventListener('click', function() {
            const alertContainer = this.parentElement.parentElement;
            if (alertContainer) {
                alertContainer.style.display = 'none';
            }
        });
    });
});
