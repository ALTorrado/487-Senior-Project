<?php
require_once 'db_connect.php';
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get company name for the logged-in user
$company_code = $_SESSION['company_code'];
$company_name = "Warehouse Dashboard"; // Default name

if ($company_code) {
    $stmt = $conn->prepare("SELECT Name FROM Company WHERE Company_Code = :code");
    $stmt->execute([':code' => $company_code]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($company) {
        $company_name = $company['Name'];
    }
}

// Get category details from URL
$category_id = isset($_GET['category_id']) ? $_GET['category_id'] : 0;
$type_name = isset($_GET['type']) ? $_GET['type'] : "Unknown";

// Initialize messages
$error_message = '';
$success_message = '';

// Form submission handling
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // ADD NEW ITEM
    if (isset($_POST['action']) && $_POST['action'] == 'add_item') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $quantity = $_POST['quantity'];
        $units = $_POST['units'];
        
        try {
            // Check if category exists
            $catCheck = $conn->prepare("SELECT COUNT(*) FROM Category WHERE Category_Id = :cat_id");
            $catCheck->execute([':cat_id' => $category_id]);
            $categoryExists = $catCheck->fetchColumn();
            
            if (!$categoryExists) {
                $error_message = "Error: Category ID $category_id does not exist in the database.";
            } else {
                // Insert the product
                $stmt = $conn->prepare("INSERT INTO Product (Name, Description, Price, Stock_Quantity, Units, Category_Category_Id, Company_Code) 
                                      VALUES (:name, :description, :price, :quantity, :units, :category_id, :company_code)");
                
                $params = [
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':quantity' => $quantity,
                    ':units' => $units,
                    ':category_id' => $category_id,
                    ':company_code' => $company_code
                ];
                
                $result = $stmt->execute($params);
                
                if ($result) {
                    $success_message = "Product added successfully!";
                    header("Location: inventory-details.php?category_id=$category_id&type=" . urlencode($type_name) . "&success=1");
                    exit();
                } else {
                    $error_message = "Failed to add product.";
                }
            }
        } catch (PDOException $e) {
            $error_message = "Error adding product: " . $e->getMessage();
        }
    }
    
    // EDIT ITEM
    else if (isset($_POST['action']) && $_POST['action'] == 'edit_item') {
        $product_id = $_POST['product_id'];
        $name = $_POST['name'];
        $description = $_POST['description'];
        $price = $_POST['price'];
        $quantity = $_POST['quantity'];
        $units = $_POST['units'];
        
        try {
            $stmt = $conn->prepare("UPDATE Product SET Name = :name, Description = :description,
                                    Price = :price, Stock_Quantity = :quantity, Units = :units
                                    WHERE Product_Id = :product_id AND Company_Code = :company_code");
            $result = $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':price' => $price,
                ':quantity' => $quantity,
                ':units' => $units,
                ':product_id' => $product_id,
                ':company_code' => $company_code
            ]);
            
            if ($result) {
                $success_message = "Product updated successfully!";
                header("Location: inventory-details.php?category_id=$category_id&type=" . urlencode($type_name) . "&success=1");
                exit();
            } else {
                $error_message = "Failed to update product.";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating product: " . $e->getMessage();
        }
    }
    
    // DELETE ITEM
    else if (isset($_POST['action']) && $_POST['action'] == 'delete_item') {
        $product_id = $_POST['product_id'];
        
        try {
            $stmt = $conn->prepare("DELETE FROM Product WHERE Product_Id = :product_id AND Company_Code = :company_code");
            $result = $stmt->execute([
                ':product_id' => $product_id,
                ':company_code' => $company_code
            ]);
            
            if ($result) {
                $success_message = "Product deleted successfully!";
                header("Location: inventory-details.php?category_id=$category_id&type=" . urlencode($type_name) . "&success=1");
                exit();
            } else {
                $error_message = "Failed to delete product.";
            }
        } catch (PDOException $e) {
            $error_message = "Error deleting product: " . $e->getMessage();
        }
    }
}

// Check for success message from URL
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success_message = "Operation completed successfully!";
}

// Get all products for this category and company
$stmt = $conn->prepare("SELECT * FROM Product WHERE Category_Category_Id = :category_id AND Company_Code = :company_code");
$stmt->execute([
    ':category_id' => $category_id,
    ':company_code' => $company_code
]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Inventory Details - <?= htmlspecialchars($type_name) ?></title>
    <link rel="stylesheet" href="inventory-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="top-info-bar">
        <div class="company-info">
            <h1 class="company-name"><?= htmlspecialchars($company_name) ?> Warehouse</h1>
        </div>
        <a href="First.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
        </a>
    </div>

    <div class="inventory-details">
        <h2 class="inventory-type-title"><?= htmlspecialchars($type_name) ?></h2>
        
        <!-- Success and Error messages -->
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>
        
        <button id="add-item-btn">Add New Item</button>
        
        <table class="inventory-table">
            <thead>
                <tr>
                    <th>Item Name</th>
                    <th>Description</th>
                    <th>Price</th>
                    <th>Stock Quantity</th>
                    <th>Units</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['Name']) ?></td>
                    <td><?= htmlspecialchars($product['Description']) ?></td>
                    <td><?= htmlspecialchars($product['Price']) ?></td>
                    <td><?= htmlspecialchars($product['Stock_Quantity']) ?></td>
                    <td><?= htmlspecialchars($product['Units']) ?></td>
                    <td>
                        <button class="edit-item" data-id="<?= $product['Product_Id'] ?>">Edit</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_item">
                            <input type="hidden" name="product_id" value="<?= $product['Product_Id'] ?>">
                            <button type="submit" class="delete-item">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (count($products) === 0): ?>
                <tr>
                    <td colspan="6" class="empty-message">No items found in this category</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Item Modal -->
    <div id="add-item-modal" class="modal">
        <div class="modal-content">
            <h3>Add New Item</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_item">
                <div class="form-group">
                    <label for="item-name">Item Name:</label>
                    <input type="text" id="item-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="item-description">Description:</label>
                    <input type="text" id="item-description" name="description" required>
                </div>
                <div class="form-group">
                    <label for="item-price">Price:</label>
                    <input type="number" id="item-price" name="price" required>
                </div>
                <div class="form-group">
                    <label for="item-quantity">Stock Quantity:</label>
                    <input type="number" id="item-quantity" name="quantity" required>
                </div>
                <div class="form-group">
                    <label for="item-units">Units:</label>
                    <input type="text" id="item-units" name="units" required>
                </div>
                <div class="form-group buttons">
                    <button type="submit" id="save-item">Save</button>
                    <button type="button" id="close-modal">Close</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div id="edit-item-modal" class="modal">
        <div class="modal-content">
            <h3>Edit Item</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit_item">
                <input type="hidden" id="edit-item-id" name="product_id">
                <div class="form-group">
                    <label for="edit-item-name">Item Name:</label>
                    <input type="text" id="edit-item-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit-item-description">Description:</label>
                    <input type="text" id="edit-item-description" name="description" required>
                </div>
                <div class="form-group">
                    <label for="edit-item-price">Price:</label>
                    <input type="number" id="edit-item-price" name="price" required>
                </div>
                <div class="form-group">
                    <label for="edit-item-quantity">Stock Quantity:</label>
                    <input type="number" id="edit-item-quantity" name="quantity" required>
                </div>
                <div class="form-group">
                    <label for="edit-item-units">Units:</label>
                    <input type="text" id="edit-item-units" name="units" required>
                </div>
                <div class="form-group buttons">
                    <button type="submit" id="update-item">Update</button>
                    <button type="button" id="close-edit-modal">Close</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add item modal functionality
        const addItemModal = document.getElementById('add-item-modal');
        const addItemBtn = document.getElementById('add-item-btn');
        const closeModalBtn = document.getElementById('close-modal');
        
        if (addItemBtn) {
            addItemBtn.addEventListener('click', function() {
                addItemModal.style.display = 'block';
            });
        }
        
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', function() {
                addItemModal.style.display = 'none';
            });
        }
        
        // Edit item modal functionality
        const editItemModal = document.getElementById('edit-item-modal');
        const closeEditModalBtn = document.getElementById('close-edit-modal');
        const editButtons = document.querySelectorAll('.edit-item');
        
        // Make sure edit buttons work
        editButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                
                // Get data from the table row
                const row = this.closest('tr');
                const name = row.cells[0].textContent;
                const description = row.cells[1].textContent;
                const price = row.cells[2].textContent;
                const quantity = row.cells[3].textContent;
                const units = row.cells[4].textContent;
                
                // Populate the edit form
                document.getElementById('edit-item-id').value = productId;
                document.getElementById('edit-item-name').value = name;
                document.getElementById('edit-item-description').value = description;
                document.getElementById('edit-item-price').value = price;
                document.getElementById('edit-item-quantity').value = quantity;
                document.getElementById('edit-item-units').value = units;
                
                // Display the edit modal
                editItemModal.style.display = 'block';
            });
        });
        
        if (closeEditModalBtn) {
            closeEditModalBtn.addEventListener('click', function() {
                editItemModal.style.display = 'none';
            });
        }
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === addItemModal) {
                addItemModal.style.display = 'none';
            }
            if (event.target === editItemModal) {
                editItemModal.style.display = 'none';
            }
        });
        
        // Confirm delete
        const deleteButtons = document.querySelectorAll('.delete-item');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!confirm('Are you sure you want to delete this item?')) {
                    e.preventDefault();
                }
            });
        });
        
        // Form validation for edit item
        const editItemForm = document.querySelector('#edit-item-modal form');
        if (editItemForm) {
            editItemForm.addEventListener('submit', function(e) {
                const nameInput = document.getElementById('edit-item-name');
                
                if (nameInput.value.trim() === '') {
                    e.preventDefault();
                    alert('Item name cannot be empty');
                    return;
                }
            });
        }
    });
    </script></body>
</html>
