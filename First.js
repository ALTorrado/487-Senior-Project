document.addEventListener('DOMContentLoaded', function () {
    document.querySelector('.menu li[onclick="showTab(\'inventory\')"]').addEventListener('click', () => showTab('inventory'));
    document.querySelector('.menu li[onclick="showTab(\'orders\')"]').addEventListener('click', () => showTab('orders'));
    document.querySelector('.menu li[onclick="showTab(\'analytics\')"]').addEventListener('click', () => showTab('analytics'));

    function showTab(tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });

        const selectedTab = document.getElementById(tabName);
        if (selectedTab) {
            selectedTab.classList.add('active');
        }

        document.querySelectorAll('.menu li').forEach(li => {
            li.classList.remove('active');
        });

        document.querySelector(`.menu li[onclick="showTab('${tabName}')"]`).classList.add('active');
    }

    showTab('inventory');

    document.querySelectorAll('.menu li').forEach(item => {
        item.addEventListener('mouseover', function () {
            this.style.backgroundColor = '#34495e';
        });
        item.addEventListener('mouseout', function () {
            this.style.backgroundColor = '';
        });
    });

    const inventoryModal = document.getElementById('add-inventory-modal');
    const addInventoryBtn = document.getElementById('add-inventory-btn');
    const closeInventoryModal = document.getElementById('close-modal');

    if (addInventoryBtn) {
        addInventoryBtn.addEventListener('click', () => {
            inventoryModal.style.display = 'flex';
        });
    }

    if (closeInventoryModal) {
        closeInventoryModal.addEventListener('click', () => {
            inventoryModal.style.display = 'none';
        });
    }

    const addOrderModal = document.getElementById('add-order-modal');
    const editOrderModal = document.getElementById('edit-order-modal');
    const addOrderBtn = document.getElementById('add-order-btn');
    const closeOrderModalBtn = document.querySelector('.cancel-btn');
    const closeEditOrderModalBtn = document.getElementById('close-edit-order-modal');
    
    if (addOrderBtn) {
        addOrderBtn.addEventListener('click', function() {
            addOrderModal.style.display = 'block';
        });
    }
    
    const closeModalButtons = document.querySelectorAll('#close-order-modal, .cancel-btn, button.cancel-btn');
    closeModalButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    if (closeEditOrderModalBtn) {
        closeEditOrderModalBtn.addEventListener('click', function() {
            editOrderModal.style.display = 'none';
        });
    }
    
    const editOrderBtns = document.querySelectorAll('.edit-order-btn');
    editOrderBtns.forEach(button => {
        button.addEventListener('click', function() {
            const orderId = this.getAttribute('data-id');
            const customerName = this.getAttribute('data-customer');
            const status = this.getAttribute('data-status');
            const amount = this.getAttribute('data-amount');
            
            document.getElementById('edit-order-id').value = orderId;
            document.getElementById('edit-customer-name').value = customerName;
            document.getElementById('edit-order-status').value = status;
            document.getElementById('edit-order-amount').value = amount;
            
            editOrderModal.style.display = 'flex';
        });
    });
    
    const addOrderForm = document.querySelector('#add-order-modal form');
    if (addOrderForm) {
        addOrderForm.addEventListener('submit', function(e) {
            console.log('Form submitted!', {
                customer: document.getElementById('customer-name').value,
                date: document.getElementById('order-date').value,
                status: document.getElementById('order-status').value
            });
        });
    }
    
    const editOrderForm = document.querySelector('#edit-order-modal form');
    if (editOrderForm) {
        editOrderForm.addEventListener('submit', function(e) {
            const customerName = document.getElementById('edit-customer-name').value.trim();
            const totalAmount = document.getElementById('edit-order-amount').value;
            
            if (customerName === '') {
                e.preventDefault();
                alert('Please enter a customer name');
                return;
            }
            
            if (isNaN(totalAmount) || parseFloat(totalAmount) < 0) {
                e.preventDefault();
                alert('Please enter a valid order amount');
                return;
            }
        });
    }
    
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('cancel-btn') || 
            event.target.id === 'close-order-modal' ||
            (event.target.tagName.toLowerCase() === 'button' && 
             event.target.textContent.trim() === 'Cancel')) {
            
            const modal = event.target.closest('.modal');
            if (modal) {
                modal.style.display = 'none';
            }
        }
        
        if (event.target.id === 'add-order-btn' || 
            event.target.closest('#add-order-btn')) {
            const orderModal = document.getElementById('add-order-modal');
            if (orderModal) {
                orderModal.style.display = 'block';
            }
        }
    });
    
    window.addEventListener('click', function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    });
    
    const inventoryList = document.getElementById('inventory-list');
    if (inventoryList) {
        inventoryList.addEventListener('click', (event) => {
            if (event.target.classList.contains('remove-btn')) {
                if (confirm('Are you sure you want to remove this inventory type?')) {
                    return true;
                } else {
                    event.preventDefault();
                    return false;
                }
            }
        });
    }
    
    function updateInventoryCount() {
        const inventoryList = document.getElementById('inventory-list');
        if (!inventoryList) return;
        
        const inventoryCount = inventoryList.children.length;
        const inventoryCountElement = document.getElementById('inventory-count');
        if (inventoryCountElement) {
            inventoryCountElement.textContent = inventoryCount;
        }

        const emptyMessage = document.getElementById('empty-message');
        if (emptyMessage) {
            emptyMessage.style.display = inventoryCount > 0 ? 'none' : 'block';
        }
    }
    
    updateInventoryCount();
});

function showErrorMessage(title, message) {
    document.getElementById('error-title').textContent = title || 'Error';
    document.getElementById('error-message').textContent = message;
    document.getElementById('error-modal').style.display = 'block';
}

document.getElementById('error-close-btn').addEventListener('click', function() {
    document.getElementById('error-modal').style.display = 'none';
});

window.addEventListener('click', function(event) {
    if (event.target === document.getElementById('error-modal')) {
        document.getElementById('error-modal').style.display = 'none';
    }
});

document.getElementById('add-order-btn').addEventListener('click', () => {
    document.getElementById('add-order-modal').style.display = 'flex';
});

document.getElementById('close-order-modal').addEventListener('click', () => {
    document.getElementById('add-order-modal').style.display = 'none';
});

window.addEventListener('click', (event) => {
    const modal = document.getElementById('add-order-modal');
    if (event.target === modal) {
        modal.style.display = 'none';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    console.log("DOM loaded, looking for help button");
    
    const helpBtn = document.querySelector('.help-logo');
    console.log("Help button found:", helpBtn);
    
    const helpModal = document.getElementById('help-modal');
    console.log("Help modal found:", helpModal);
    
    if (helpBtn && helpModal) {
        helpBtn.addEventListener('click', function(e) {
            console.log("Help button clicked");
            e.preventDefault();
            helpModal.style.display = 'flex';
        });
        
        const closeHelpModalBtn = document.getElementById('close-help-modal');
        if (closeHelpModalBtn) {
            closeHelpModalBtn.addEventListener('click', function() {
                console.log("Close button clicked");
                helpModal.style.display = 'none';
            });
        } else {
            console.log("Close help modal button not found");
        }
        
        window.addEventListener('click', function(event) {
            if (event.target === helpModal) {
                helpModal.style.display = 'none';
            }
        });
    } else {
        console.log("Help button or modal not found");
    }
});
