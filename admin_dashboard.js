document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded');
    

    document.querySelectorAll('.menu li').forEach(tab => {
        tab.addEventListener('click', () => {
            console.log('Tab clicked:', tab.dataset.tab);
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            document.getElementById(tab.dataset.tab).style.display = 'block';
            
            document.querySelectorAll('.menu li').forEach(t => {
                t.classList.remove('active');
            });
            tab.classList.add('active');
        });
    });
    
  
    document.querySelectorAll('.view-employees').forEach(btn => {
        btn.addEventListener('click', () => {
            const code = btn.dataset.code;
            window.location.href = `view_employees.php?company_code=${code}`;
        });
    });

   
    function openModal(modalId) {
        console.log('Opening modal:', modalId);
        const modal = document.getElementById(modalId);
        modal.style.display = 'block';
        modal.style.backgroundColor = 'rgba(0, 0, 0, 0.7)';
        modal.style.zIndex = '9999';
        
      
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.backgroundColor = 'white';
            modalContent.style.padding = '20px';
            modalContent.style.position = 'absolute';
            modalContent.style.top = '50%';
            modalContent.style.left = '50%';
            modalContent.style.transform = 'translate(-50%, -50%)';
            modalContent.style.width = '400px';
            modalContent.style.zIndex = '10000';
        }
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

  
    function showAlert(message, type) {
      
        document.querySelectorAll('.alert-container').forEach(container => {
            container.remove();
        });
        
  
        const alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container';
        alertContainer.style.width = '100%';
        alertContainer.style.display = 'flex';
        alertContainer.style.justifyContent = 'center';
        
   
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
     
        alertContainer.appendChild(alertDiv);
        
       
        const adminContainer = document.querySelector('.admin-container');
        if (adminContainer) {
            adminContainer.insertBefore(alertContainer, adminContainer.firstChild);
            
           
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const container = alert.parentElement;
                    if (container) {
                        container.style.display = 'none';
                    }
                }, 5000);
            });
        }
    }

    
    function isValidEmail(email) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailPattern.test(email);
    }

    
    document.getElementById('add-company-btn').addEventListener('click', () => {
        document.getElementById('add-company-form').reset();
        openModal('add-company-modal');
    });

    document.getElementById('add-company-form').addEventListener('submit', function(e) {
        const name = document.getElementById('company-name').value.trim();
        const email = document.getElementById('company-email').value.trim();
        
        if (!name || !email) {
            e.preventDefault();
            alert('Please fill in all fields');
            return;
        }
        
        if (!isValidEmail(email)) {
            e.preventDefault();
            alert('Please enter a valid email address');
            return;
        }
    });

  
    document.querySelectorAll('.edit-company').forEach(btn => {
        btn.addEventListener('click', () => {
            console.log('Edit company button clicked');
            const row = btn.closest('tr');
            const name = row.querySelector('.company-name').textContent;
            const email = row.querySelector('.company-email').textContent;
            const code = btn.dataset.code;
            
            document.getElementById('edit-company-name').value = name;
            document.getElementById('edit-company-email').value = email;
            document.getElementById('edit-company-code').value = code;
            
            openModal('edit-company-modal');
        });
    });

    document.querySelectorAll('.delete-company').forEach(btn => {
        btn.addEventListener('click', () => {
            console.log('Delete company button clicked');
            const code = btn.dataset.code;
            document.getElementById('delete-company-code').value = code;
            openModal('delete-company-modal');
        });
    });

    const deleteCompanyForm = document.getElementById('delete-company-form');
    if (deleteCompanyForm) {
        deleteCompanyForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            if (confirm('Are you sure you want to delete this company? This action cannot be undone.')) {
                
                deleteCompanyForm.submit();
            }
        });
    }

   
    function updateCompanyStatus(code, status, refreshPendingTab = false) {
       
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'admin_dashboard.php';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'update_company_status';
        
        const codeInput = document.createElement('input');
        codeInput.type = 'hidden';
        codeInput.name = 'code';
        codeInput.value = code;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'status';
        statusInput.value = status;
        
        if (refreshPendingTab) {
            const refreshInput = document.createElement('input');
            refreshInput.type = 'hidden';
            refreshInput.name = 'refresh_tab';
            refreshInput.value = 'pending';
            form.appendChild(refreshInput);
        }
        
        form.appendChild(actionInput);
        form.appendChild(codeInput);
        form.appendChild(statusInput);
        
        document.body.appendChild(form);
        form.submit();
    }

    
    document.querySelectorAll('.approve-company-pending').forEach(btn => {
        btn.addEventListener('click', () => {
            console.log('Approve company button clicked');
            if (confirm('Are you sure you want to approve this company?')) {
                const code = btn.dataset.code;
                console.log('Approving company with code:', code);
                updateCompanyStatus(code, 1, true); 
            }
        });
    });

    document.querySelectorAll('.reject-company-pending').forEach(btn => {
        btn.addEventListener('click', () => {
            console.log('Reject company button clicked');
            if (confirm('Are you sure you want to reject this company?')) {
                const code = btn.dataset.code;
                console.log('Rejecting company with code:', code);
                updateCompanyStatus(code, 2, true); 
            }
        });
    });

    
    document.querySelectorAll('.approve-employee, .reject-employee').forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.classList.contains('approve-employee') ? 'approve' : 'reject';
            const userId = this.dataset.id;
            const status = action === 'approve' ? 1 : 2;
            
            if (confirm(`Are you sure you want to ${action} this employee?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'admin_dashboard.php';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_employee_status">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="status" value="${status}">
                    <input type="hidden" name="refresh_tab" value="pending">
                `;
                document.body.appendChild(form);
                form.submit();
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

    
    if (window.location.hash) {
        const tabId = window.location.hash.substring(1); 
        const tab = document.querySelector(`.menu li[data-tab="${tabId}"]`);
        
        if (tab) {
            
            document.querySelectorAll('.tab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            
            document.getElementById(tabId).style.display = 'block';
            
           
            document.querySelectorAll('.menu li').forEach(t => {
                t.classList.remove('active');
            });
            tab.classList.add('active');
        }
    }

   
    function debugElement(selector, message) {
        const elements = document.querySelectorAll(selector);
        console.log(`${message}: Found ${elements.length} elements`);
        elements.forEach((el, index) => {
            console.log(`Element ${index}:`, el);
            if (el.dataset) {
                console.log(`  Dataset:`, el.dataset);
            }
        });
    }

    
    debugElement('.approve-company-pending', 'Approve company buttons');
    debugElement('.reject-company-pending', 'Reject company buttons');
});
