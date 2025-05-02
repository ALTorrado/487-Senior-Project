<?php
require_once 'db_connect.php';
require_once 'session.php'; 


requireLogin(); 


$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT Role FROM Users WHERE User_Id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$isManager = isset($user['Role']) && ($user['Role'] == 2 || $user['Role'] == 1); 

$company_code = $_SESSION['company_code'];
$company_name = "Warehouse Dashboard"; 
if ($company_code) {
    $stmt = $conn->prepare("SELECT Name FROM Company WHERE Company_Code = :code");
    $stmt->execute([':code' => $company_code]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($company) {
        $company_name = $company['Name'];
    }
}

if (!isset($_GET['order_id'])) {
    header("Location: First.php");
    exit();
}

$order_id = $_GET['order_id'];
$is_new_order = isset($_GET['new']) && $_GET['new'] == 1;
$success_message = '';
$error_message = '';

$stmt = $conn->prepare("
    SELECT o.*, u.Name as CreatedBy 
    FROM Orders o 
    LEFT JOIN Users u ON o.Users_User_Id = u.User_Id
    WHERE o.Order_Id = :order_id
");
$stmt->execute([':order_id' => $order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: First.php");
    exit();
}

function updateOrderTotal($conn, $order_id) {
    $stmt = $conn->prepare("
        SELECT SUM(op.Quantity * op.Price_At_Time) as Total
        FROM Order_Products op
        WHERE op.Orders_Order_Id = :order_id
    ");
    $stmt->execute([':order_id' => $order_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $total = $result['Total'] ?: 0;
    
    $stmt = $conn->prepare("UPDATE Orders SET Total_Amount = :total WHERE Order_Id = :order_id");
    $stmt->execute([
        ':total' => $total,
        ':order_id' => $order_id
    ]);
    
    return $total;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$isManager) {
    $error_message = "You don't have permission to modify orders.";
    header("Location: order-details.php?order_id={$order_id}&error=" . urlencode($error_message));
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add_product' && $isManager) {
            $product_id = $_POST['product_id'];
            $quantity = $_POST['quantity'];
            
            $stmt = $conn->prepare("SELECT Price, Stock_Quantity, Name FROM Product WHERE Product_Id = :product_id");
            $stmt->execute([':product_id' => $product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                if ($product['Stock_Quantity'] < $quantity) {
                    $error_message = "Not enough stock available for " . $product['Name'] . ". Only " . $product['Stock_Quantity'] . " units available.";
                } else {
                    $price_at_time = $product['Price'];
                    
                    $stmt = $conn->prepare("
                        SELECT * FROM Order_Products 
                        WHERE Orders_Order_Id = :order_id AND Product_Product_Id = :product_id
                    ");
                    $stmt->execute([
                        ':order_id' => $order_id,
                        ':product_id' => $product_id
                    ]);
                    $existingProduct = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($existingProduct) {
                        $newQuantity = $existingProduct['Quantity'] + $quantity;
                        $stmt = $conn->prepare("
                            UPDATE Order_Products 
                            SET Quantity = :quantity 
                            WHERE Order_Item_Id = :item_id
                        ");
                        $stmt->execute([
                            ':quantity' => $newQuantity,
                            ':item_id' => $existingProduct['Order_Item_Id']
                        ]);
                        $success_message = "Product quantity updated!";
                    } else {
                        $stmt = $conn->prepare("
                            INSERT INTO Order_Products (Quantity, Price_At_Time, Orders_Order_Id, Product_Product_Id)
                            VALUES (:quantity, :price, :order_id, :product_id)
                        ");
                        $stmt->execute([
                            ':quantity' => $quantity,
                            ':price' => $price_at_time,
                            ':order_id' => $order_id,
                            ':product_id' => $product_id
                        ]);
                        $success_message = "Product added to order!";
                    }
                    
                    updateOrderTotal($conn, $order_id);
                    
                    $stmt = $conn->prepare("
                        UPDATE Product 
                        SET Stock_Quantity = Stock_Quantity - :quantity 
                        WHERE Product_Id = :product_id
                    ");
                    $stmt->execute([
                        ':quantity' => $quantity,
                        ':product_id' => $product_id
                    ]);
                }
            } else {
                $error_message = "Product not found!";
            }
        }
        
        else if ($_POST['action'] == 'remove_product' && $isManager) {
            $item_id = $_POST['item_id'];
            $product_id = $_POST['product_id'];
            $quantity = $_POST['quantity'];
            
            try {
                $stmt = $conn->prepare("DELETE FROM Order_Products WHERE Order_Item_Id = :item_id");
                $stmt->execute([':item_id' => $item_id]);
                
                $stmt = $conn->prepare("
                    UPDATE Product 
                    SET Stock_Quantity = Stock_Quantity + :quantity 
                    WHERE Product_Id = :product_id
                ");
                $stmt->execute([
                    ':quantity' => $quantity,
                    ':product_id' => $product_id
                ]);
                
                updateOrderTotal($conn, $order_id);
                
                $success_message = "Product removed from order!";
            } catch (PDOException $e) {
                $error_message = "Error removing product: " . $e->getMessage();
            }
        }
        
        else if ($_POST['action'] == 'update_order' && $isManager) {
            $customer_name = $_POST['customer_name'];
            $order_date = $_POST['order_date'];
            $status = $_POST['status'];
            $payment_status = $_POST['payment_status'];
            
            if (strlen($customer_name) > 15) {
                $error_message = "Customer name cannot exceed 15 characters.";
            } 
            else {
                $minDate = date('Y-m-d', strtotime('-5 years'));
                $maxDate = date('Y-m-d', strtotime('+5 years'));
                
                if ($order_date < $minDate || $order_date > $maxDate) {
                    $error_message = "Order date must be between " . date('M d, Y', strtotime($minDate)) . " and " . date('M d, Y', strtotime($maxDate));
                } else {
                    try {
                        $stmt = $conn->prepare("
                            UPDATE Orders 
                            SET Customer_Name = :customer_name, 
                                Order_Date = :order_date,
                                Status = :status, 
                                Payment_Status = :payment_status
                            WHERE Order_Id = :order_id
                        ");
                        $stmt->execute([
                            ':customer_name' => $customer_name,
                            ':order_date' => $order_date,
                            ':status' => $status,
                            ':payment_status' => $payment_status,
                            ':order_id' => $order_id
                        ]);
                        
                        $success_message = "Order details updated!";
                        
                        $stmt = $conn->prepare("
                            SELECT o.*, u.Name as CreatedBy 
                            FROM Orders o 
                            LEFT JOIN Users u ON o.Users_User_Id = u.User_Id
                            WHERE o.Order_Id = :order_id
                        ");
                        $stmt->execute([':order_id' => $order_id]);
                        $order = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $error_message = "Error updating order: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

$stmt = $conn->prepare("
    SELECT op.*, p.Name as ProductName, p.Description, p.Units
    FROM Order_Products op
    JOIN Product p ON op.Product_Product_Id = p.Product_Id
    WHERE op.Orders_Order_Id = :order_id
");
$stmt->execute([':order_id' => $order_id]);
$orderProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("
    SELECT p.* 
    FROM Product p
    WHERE p.Company_Code = :company_code AND p.Stock_Quantity > 0
    ORDER BY p.Name
");
$stmt->execute([':company_code' => $company_code]);
$availableProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orderTotal = 0;
foreach ($orderProducts as $product) {
    $orderTotal += $product['Quantity'] * $product['Price_At_Time'];
}

if ($orderTotal != $order['Total_Amount']) {
    $stmt = $conn->prepare("UPDATE Orders SET Total_Amount = :total WHERE Order_Id = :order_id");
    $stmt->execute([
        ':total' => $orderTotal,
        ':order_id' => $order_id
    ]);
    $order['Total_Amount'] = $orderTotal;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order #<?= $order_id ?> - <?= htmlspecialchars($company_name) ?></title>
    <link rel="stylesheet" href="First.css">
    <link rel="stylesheet" href="order-details.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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

    <div class="main-content">
        <?php if ($is_new_order): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> Order created successfully! Add products to complete your order.
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <?php if (!$isManager): ?>
            <div class="view-only-indicator">
                <i class="fas fa-eye"></i>
                <span>View Only Mode: You can view order details but cannot make changes.</span>
            </div>
        <?php endif; ?>

        <div class="order-details-container">
            <div class="order-header">
                <h1>Order #<?= $order_id ?></h1>
                
                <?php if ($isManager): ?>
                    <button id="edit-order-btn" class="edit-order-btn">
                        <i class="fas fa-edit"></i> Edit Order
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="order-meta">
                <div class="meta-item">
                    <h3>Customer</h3>
                    <p><?= htmlspecialchars($order['Customer_Name'] ?? 'N/A') ?></p>
                </div>
                <div class="meta-item">
                    <h3>Date</h3>
                    <p><?= date('M d, Y', strtotime($order['Order_Date'])) ?></p>
                </div>
                <div class="meta-item">
                    <h3>Status</h3>
                    <p>
                        <span class="status-badge status-<?= strtolower($order['Status']) ?>">
                            <?= htmlspecialchars($order['Status']) ?>
                        </span>
                    </p>
                </div>
                <div class="meta-item">
                    <h3>Payment Status</h3>
                    <p>
                        <span class="status-badge payment-<?= strtolower($order['Payment_Status'] ?? 'pending') ?>">
                            <?= htmlspecialchars($order['Payment_Status'] ?? 'Pending') ?>
                        </span>
                    </p>
                </div>
                <div class="meta-item">
                    <h3>Created By</h3>
                    <p><?= htmlspecialchars($order['CreatedBy']) ?></p>
                </div>
            </div>
            
            <div class="order-products">
                <h2>Order Items</h2>
                
                <?php if (count($orderProducts) > 0): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Units</th>
                                <th>Subtotal</th>
                                <?php if ($isManager): ?>
                                    <th>Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orderProducts as $product): ?>
                                <tr>
                                <td><?= htmlspecialchars($product['ProductName']) ?></td>
                                    <td><?= htmlspecialchars($product['Description']) ?></td>
                                    <td>$<?= number_format($product['Price_At_Time'], 2) ?></td>
                                    <td><?= $product['Quantity'] ?></td>
                                    <td><?= htmlspecialchars($product['Units']) ?></td>
                                    <td>$<?= number_format($product['Quantity'] * $product['Price_At_Time'], 2) ?></td>
                                    <?php if ($isManager): ?>
                                        <td>
                                            <form method="POST" class="remove-product-form">
                                                <input type="hidden" name="action" value="remove_product">
                                                <input type="hidden" name="item_id" value="<?= $product['Order_Item_Id'] ?>">
                                                <input type="hidden" name="product_id" value="<?= $product['Product_Product_Id'] ?>">
                                                <input type="hidden" name="quantity" value="<?= $product['Quantity'] ?>">
                                                <button type="submit" class="remove-btn">
                                                    <i class="fas fa-trash"></i> Remove
                                                </button>
                                            </form>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="<?= $isManager ? '5' : '5' ?>" class="total-label">Total:</td>
                                <td class="total-amount">$<?= number_format($order['Total_Amount'], 2) ?></td>
                                <?php if ($isManager): ?>
                                    <td></td>
                                <?php endif; ?>
                            </tr>
                        </tfoot>
                    </table>
                <?php else: ?>
                    <div class="empty-message">
                        <i class="fas fa-shopping-cart"></i>
                        <p>No products added to this order yet.</p>
                        <?php if ($isManager): ?>
                            <button id="add-product-btn" class="add-btn">
                                <i class="fas fa-plus"></i> Add Products
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($isManager && count($orderProducts) > 0): ?>
                    <div class="add-product">
                        <button id="add-product-btn" class="add-btn">
                            <i class="fas fa-plus"></i> Add More Products
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="order-actions">
                <a href="First.php#orders" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Orders
                </a>
            </div>
        </div>
    </div>
    
    <?php if ($isManager): ?>
        <div id="add-product-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Add Product to Order #<?= $order_id ?></h3>
                    <span class="close-modal">×</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_product">
                    <div class="form-group">
                        <label for="product_id">Select Product:</label>
                        <select id="product_id" name="product_id" required>
                            <option value="">-- Select a product --</option>
                            <?php foreach ($availableProducts as $product): ?>
                                <option value="<?= $product['Product_Id'] ?>" data-stock="<?= $product['Stock_Quantity'] ?>" data-price="<?= $product['Price'] ?>">
                                    <?= htmlspecialchars($product['Name']) ?> - $<?= number_format($product['Price'], 2) ?> (<?= $product['Stock_Quantity'] ?> available)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="quantity">Quantity:</label>
                        <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                        <p class="stock-info">Maximum available: <span id="max-stock">0</span></p>
                    </div>
                    <div class="form-group buttons">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Add to Order</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($isManager): ?>
        <div id="edit-order-modal" class="modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Order #<?= $order_id ?></h3>
                    <span class="close-modal">×</span>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="update_order">
                    <div class="form-group">
                        <label for="customer_name">Customer Name:</label>
                        <input type="text" id="customer_name" name="customer_name" value="<?= htmlspecialchars($order['Customer_Name'] ?? '') ?>" maxlength="15" required>
                    </div>
                    <div class="form-group">
                        <label for="order_date">Order Date:</label>
                        <input type="date" id="order_date" name="order_date" value="<?= htmlspecialchars($order['Order_Date'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="status">Order Status:</label>
                        <select id="status" name="status" required>
                            <?php 
                            $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
                            foreach ($statuses as $status): 
                            ?>
                                <option value="<?= $status ?>" <?= ($order['Status'] == $status) ? 'selected' : '' ?>>
                                    <?= $status ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="payment_status">Payment Status:</label>
                        <select id="payment_status" name="payment_status" required>
                            <?php 
                            $paymentStatuses = ['Pending', 'Paid', 'Failed', 'Refunded'];
                            foreach ($paymentStatuses as $payStatus): 
                            ?>
                                <option value="<?= $payStatus ?>" <?= ($order['Payment_Status'] == $payStatus) ? 'selected' : '' ?>>
                                    <?= $payStatus ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group buttons">
                        <button type="button" class="cancel-btn">Cancel</button>
                        <button type="submit" class="save-btn">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const addProductBtn = document.getElementById('add-product-btn');
        const addProductModal = document.getElementById('add-product-modal');
        
        if (addProductBtn && addProductModal) {
            addProductBtn.addEventListener('click', function() {
                addProductModal.style.display = 'block';
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
                editOrderModal.style.display = 'block';
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
        
        const productSelect = document.getElementById('product_id');
        const quantityInput = document.getElementById('quantity');
        const maxStockSpan = document.getElementById('max-stock');
        
        if (productSelect && quantityInput && maxStockSpan) {
            productSelect.addEventListener('change', function() {
                const selectedOption = this.options[this.selectedIndex];
                const maxStock = selectedOption.dataset.stock || 0;
                
                maxStockSpan.textContent = maxStock;
                quantityInput.max = maxStock;
                
                if (parseInt(quantityInput.value) > maxStock) {
                    quantityInput.value = maxStock;
                }
            });
            
            if (productSelect.selectedIndex > 0) {
                const selectedOption = productSelect.options[productSelect.selectedIndex];
                const maxStock = selectedOption.dataset.stock || 0;
                
                maxStockSpan.textContent = maxStock;
                quantityInput.max = maxStock;
            }
        }
        
        const removeForms = document.querySelectorAll('.remove-product-form');
        removeForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                if (!confirm('Are you sure you want to remove this product from the order?')) {
                    e.preventDefault();
                }
            });
        });
        
        const orderDateInput = document.getElementById('order_date');
        
        if (orderDateInput) {
            const today = new Date();
            
            const minDate = new Date();
            minDate.setFullYear(today.getFullYear() - 5);
            
            const maxDate = new Date();
            maxDate.setFullYear(today.getFullYear() + 5);
            
            const formatDate = (date) => {
                const year = date.getFullYear();
                const month = String(date.getMonth() + 1).padStart(2, '0');
                const day = String(date.getDate()).padStart(2, '0');
                return `${year}-${month}-${day}`;
            };
            
            orderDateInput.min = formatDate(minDate);
            orderDateInput.max = formatDate(maxDate);
            
            if (!orderDateInput.value) {
                orderDateInput.value = formatDate(today);
            }
        }
    });
    </script>
</body>
</html>
