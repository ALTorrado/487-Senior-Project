document.addEventListener('DOMContentLoaded', function() {
    const categorySelect = document.getElementById('category-select');
    const productSelect = document.getElementById('product-select');
    const quantityInput = document.getElementById('product-quantity');
    const addToOrderBtn = document.getElementById('add-to-order');
    
    categorySelect.addEventListener('change', function() {
        const categoryId = this.value;
        
        productSelect.innerHTML = '<option value="">-- Select a Product --</option>';
        
        if (!categoryId) {
            productSelect.disabled = true;
            quantityInput.disabled = true;
            addToOrderBtn.disabled = true;
            return;
        }
        
        productSelect.disabled = true;
        productSelect.innerHTML = '<option value="">Loading products...</option>';
        
        fetch(`get_products.php?category_id=${categoryId}`)
            .then(response => response.json())
            .then(products => {
                productSelect.innerHTML = '<option value="">-- Select a Product --</option>';
                
                products.forEach(product => {
                    const option = document.createElement('option');
                    option.value = product.Product_Id;
                    option.textContent = `${product.Name} ($${parseFloat(product.Price).toFixed(2)})`;
                    option.dataset.price = product.Price;
                    option.dataset.stock = product.Stock_Quantity;
                    productSelect.appendChild(option);
                });
                
                productSelect.disabled = false;
            })
            .catch(error => {
                console.error('Error fetching products:', error);
                productSelect.innerHTML = '<option value="">Error loading products</option>';
            });
    });
    
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
        
        addProductToOrder(productId, productName, quantity, productPrice);
    });
});

function addProductToOrder(productId, productName, quantity, price) {
    const orderItemsTable = document.getElementById('order-items-tbody');
    
    const existingRow = document.querySelector(`tr[data-product-id="${productId}"]`);
    if (existingRow) {
        const quantityCell = existingRow.querySelector('.item-quantity');
        const priceCell = existingRow.querySelector('.item-total');
        const newQuantity = parseInt(quantityCell.dataset.quantity) + parseInt(quantity);
        const newTotal = (price * newQuantity).toFixed(2);
        
        quantityCell.textContent = newQuantity;
        quantityCell.dataset.quantity = newQuantity;
        priceCell.textContent = `$${newTotal}`;
        
        updateOrderTotal();
        return;
    }
    
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
    
    row.querySelector('.remove-item-btn').addEventListener('click', function() {
        row.remove();
        updateOrderTotal();
    });
    
    orderItemsTable.appendChild(row);
    
    document.getElementById('product-select').value = '';
    document.getElementById('product-quantity').value = 1;
    document.getElementById('product-quantity').disabled = true;
    document.getElementById('add-to-order').disabled = true;
    
    updateOrderTotal();
}

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

document.addEventListener('DOMContentLoaded', function() {
    const addProductBtn = document.getElementById('add-product-btn');
    const addProductModal = document.getElementById('add-product-modal');
    
    if (addProductBtn && addProductModal) {
        addProductBtn.addEventListener('click', function() {
            addProductModal.removeAttribute('style');
            addProductModal.style.display = 'flex';
        });
        
        const closeButtons = addProductModal.querySelectorAll('.close-modal, .cancel-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                addProductModal.style.display = 'none';
            });
        });
    }
    
    const editOrderBtn = document.getElementById('edit-order-btn');
    const editOrderModal = document.getElementById('edit-order-modal');
    
    if (editOrderBtn && editOrderModal) {
        editOrderBtn.addEventListener('click', function() {
            editOrderModal.removeAttribute('style');
            editOrderModal.style.display = 'flex';
        });
        
        const closeButtons = editOrderModal.querySelectorAll('.close-modal, .cancel-btn');
        closeButtons.forEach(button => {
            button.addEventListener('click', function() {
                editOrderModal.style.display = 'none';
            });
        });
    }
    
    window.addEventListener('click', function(event) {
        if (event.target === addProductModal) {
            addProductModal.style.display = 'none';
        }
        if (event.target === editOrderModal) {
            editOrderModal.style.display = 'none';
        }
    });
});
