document.addEventListener('DOMContentLoaded', function() {
    console.log('Script loaded - checking for add button');
    
    const addItemBtn = document.getElementById('add-item-btn');
    const addItemModal = document.getElementById('add-item-modal');
    
    console.log('Add button found:', !!addItemBtn);
    console.log('Modal found:', !!addItemModal);
    
    if (addItemBtn && addItemModal) {
        addItemBtn.onclick = function() {
            console.log('Add button clicked');
            addItemModal.style.display = 'block';
        };
        console.log('Click handler attached to add button');
    }
    
    const closeModalBtn = document.getElementById('close-modal');
    if (closeModalBtn && addItemModal) {
        closeModalBtn.onclick = function() {
            addItemModal.style.display = 'none';
        };
    }
    
    const editItemModal = document.getElementById('edit-item-modal');
    const editButtons = document.querySelectorAll('.edit-item');
    
    editButtons.forEach(button => {
        button.onclick = function() {
            const productId = this.getAttribute('data-id');
            
            const row = this.closest('tr');
            const name = row.cells[0].textContent;
            const description = row.cells[1].textContent;
            const price = row.cells[2].textContent;
            const quantity = row.cells[3].textContent;
            const units = row.cells[4].textContent;
            
            document.getElementById('edit-item-id').value = productId;
            document.getElementById('edit-item-name').value = name;
            document.getElementById('edit-item-description').value = description;
            document.getElementById('edit-item-price').value = price;
            document.getElementById('edit-item-quantity').value = quantity;
            document.getElementById('edit-item-units').value = units;
            
            editItemModal.style.display = 'block';
        };
    });
    
    const closeEditModalBtn = document.getElementById('close-edit-modal');
    if (closeEditModalBtn && editItemModal) {
        closeEditModalBtn.onclick = function() {
            editItemModal.style.display = 'none';
        };
    }
    
    window.onclick = function(event) {
        if (event.target === addItemModal) {
            addItemModal.style.display = 'none';
        }
        if (event.target === editItemModal) {
            editItemModal.style.display = 'none';
        }
    };
    
    const deleteButtons = document.querySelectorAll('.delete-item');
    deleteButtons.forEach(button => {
        button.onclick = function(e) {
            if (!confirm('Are you sure you want to delete this item?')) {
                e.preventDefault();
            }
        };
    });
});
