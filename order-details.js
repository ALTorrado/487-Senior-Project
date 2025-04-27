document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category-select');
    const productSelect = document.getElementById('product-select');
    const quantityInput = document.getElementById('product-quantity');
    const addToOrderBtn = document.getElementById('add-to-order');
    
    // When category selection changes
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        
        // Reset product dropdown
        productSelect.innerHTML = '<option value="">-- Select a Product --</option>';
        
        if (!categoryId) {
            // If no category selected, disable product selection
            productSelect.disabled = true;
            quantityInput.disabled = true;
            addToOrderBtn.disabled = true;
            return;
        }
        
        // Enable product dropdown while loading
        productSelect.disabled = true;
        productSelect.innerHTML = '<option value="">Loading products...</option>';
        
        // Fetch products for the selected category
        fetch(`get_products.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(products => {
                // Populate product dropdown
                productSelect.innerHTML = '<option value="">-- Select a Product --</option>';
                
                products.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.Product_Id;
                    option.textContent = `${product.Name} ($${parseFloat(product.Price).toFixed(2)})`;
                    option.dataset.price = product.Price;
                    option.dataset.stock = product.Stock_Quantity;
                    productSelect.appendChild(option);
                });
                
                // Enable product selection
                productSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error fetching products:', error);
                productSelect.innerHTML = '<option value="">Error loading products</option>';
            });
    });
    
    // Enable/disable add button based on product selection
    productSelect.addEventListener('change', function() {
        const productSelected = this.value !== '';
        quantityInput.disabled = !productSelected;
        addToOrderBtn.disabled = !productSelected;
        
        if (productSelected) {
            const selectedOption = this.options[this.selectedIndex];
            const maxStock = selectedOption.dataset.stock;
            quantityInput.max = maxStock;
            quantityInput.value = Math.min(1, maxStock);
        }
    });
    
    // Add product to order when button is clicked
    addToOrderBtn.addEventListener('click', function() {
        const categoryId = categorySelect.value;
        const productId = productSelect.value;
        const quantity = quantityInput.value;
        
        if (!categoryId || !productId || quantity < 1) {
            alert('Please select both category and product with a valid quantity');
            return;
        }
        
        const selectedOption = productSelect.options[productSelect.selectedIndex];
        const productName = selectedOption.textContent;
        const productPrice = selectedOption.dataset.price;
        
        // Add to order (implement this part based on your order structure)
        addProductToOrder(productId, productName, quantity, productPrice);
    });
});

// Example function to add product to order (customize based on your UI)
function addProductToOrder(productId, productName, quantity, price) {
    const orderItemsTable = document.getElementById('order-items-tbody');
    
    // Check if product already exists in order
    const existingRow = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (existingRow) {
        // Update existing product quantity
        const quantityCell = existingRow.querySelector('.item-quantity');
        const priceCell = existingRow.querySelector('.item-total');
        const newQuantity = parseInt(quantityCell.dataset.quantity) + parseInt(quantity);
        const newTotal = (price * newQuantity).toFixed(2);
        
        quantityCell.textContent = newQuantity;
        quantityCell.dataset.quantity = newQuantity;
        priceCell.textContent = `$${newTotal}`;
        
        // Update order total
        updateOrderTotal();
        return;
    }
    
    // Create new row for product
    const row = document.createElement('tr');
    row.dataset.productId = productId;
    row.dataset.price = price;
    
    const total = (price * quantity).toFixed(2);
    
    row.innerHTML = `
        <td>${productName}</td>
        <td class="item-quantity" data-quantity="${quantity}">${quantity}</td>
        <td>$${parseFloat(price).toFixed(2)}</td>
        <td class="item-total">$${total}</td>
        <td>
            <button type="button" class="remove-item-btn">Remove</button>
        </td>
    `;
    
    // Add remove button functionality
    row.querySelector('.remove-item-btn').addEventListener('click', function() {
        row.remove();
        updateOrderTotal();
    });
    
    orderItemsTable.appendChild(row);
    
    // Reset product selection
    document.getElementById('product-select').value = '';
    document.getElementById('product-quantity').value = 1;
    document.getElementById('product-quantity').disabled = true;
    document.getElementById('add-to-order').disabled = true;
    
    // Update order total
    updateOrderTotal();
}

// Function to update order total
function updateOrderTotal() {
    const orderItemsRows = document.querySelectorAll('#order-items-tbody tr');
    let total = 0;
    
    orderItemsRows.forEach(row => {
        const itemTotal = row.querySelector('.item-total').textContent;
        total += parseFloat(itemTotal.replace('$', ''));
    });
    
    document.getElementById('order-total').textContent = `$${total.toFixed(2)}`;
    document.getElementById('total-amount-input').value = total.toFixed(2);
}

// Replace your current JavaScript modal handlers with this:
document.addEventListener('DOMContentLoaded', function() {
    // Add Product Modal
    const addProductBtn = document.getElementById('add-product-btn');
    const addProductModal = document.getElementById('add-product-modal');
    
    if (addProductBtn && addProductModal) {
        addProductBtn.addEventListener('click', function() {
            // Clear any inline styles first
            addProductModal.removeAttribute('style');
            // Then set display to flex
            addProductModal.style.display = 'flex';
        });
        
        // Close modal when X is clicked
        const closeButtons = addProductModal.querySelectorAll('.close-modal, .cancel-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                addProductModal.style.display = 'none';
            });
        });
    }
    
    // Edit Order Modal
    const editOrderBtn = document.getElementById('edit-order-btn');
    const editOrderModal = document.getElementById('edit-order-modal');
    
    if (editOrderBtn && editOrderModal) {
        editOrderBtn.addEventListener('click', function() {
            // Clear any inline styles first
            editOrderModal.removeAttribute('style');
            // Then set display to flex
            editOrderModal.style.display = 'flex';
        });
        
        // Close modal when X is clicked
        const closeButtons = editOrderModal.querySelectorAll('.close-modal, .cancel-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                editOrderModal.style.display = 'none';
            });
        });
    }
    
    // Close modals when clicking outside of them
    window.addEventListener('click', function(event) {
        if (event.target === addProductModal) {
            addProductModal.style.display = 'none';
        }
        if (event.target === editOrderModal) {
            editOrderModal.style.display = 'none';
        }
    });
});