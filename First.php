<?php
require_once 'db_connect.php';
require_once 'session.php'; 

requireLogin(); 

if (!$conn) {
    echo "<div class='alert alert-danger'>Database connection error</div>";
} else {
    echo "<!-- Database connection successful -->";
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'add_inventory') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    
  
    echo "<!-- Company code from session: " . ($_SESSION['company_code'] ?? 'Not set') . " -->";
    $company_code = $_SESSION['company_code'] ?? '';
    
    if (empty($company_code)) {
        echo "<div class='alert alert-danger'>Error: Company code is not set in session. Please log in again.</div>";
    } else {
        try {
            $stmt = $conn->prepare("INSERT INTO Category (Name, Description, Company_Code) VALUES (:name, :description, :company_code)");
            $result = $stmt->execute([
                ':name' => $name,
                ':description' => $description,
                ':company_code' => $company_code
            ]);
            
            echo "<!-- Insert attempted with company_code: $company_code -->";
            
            if ($result) {
                echo "<div class='alert alert-success'>Category added successfully with company code!</div>";
            } else {
                echo "<div class='alert alert-danger'>Failed to add category.</div>";
            }
        } catch (PDOException $e) {
            echo "<div class='alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_inventory') {
    $category_id = $_POST['category_id'];
    
    $company_code = $_SESSION['company_code'] ?? '';
    
    try {
        $checkStmt = $conn->prepare("SELECT * FROM Category WHERE Category_Id = ?");
        $checkStmt->execute([$category_id]);
        $categoryData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$categoryData) {
            echo "<div class='alert alert-danger'>Error: Category not found.</div>";
        } else {
            $foundCompanyCode = $categoryData['Company_Code'];
            $categoryName = $categoryData['Name']; 
            
            if ($foundCompanyCode === $company_code) {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM Product WHERE Category_Category_Id = :category_id");
                $stmt->execute([':category_id' => $category_id]);
                $productCount = $stmt->fetchColumn();

                echo '<!-- Error Message Modal -->
                <div id="error-modal" class="error-modal">
                    <div class="error-modal-content">
                        <h3 id="error-title" class="error-title">Error</h3>
                        <p id="error-message" class="error-message"></p>
                        <button id="error-close-btn" class="error-close-btn">Close</button>
                    </div>
                </div>';

                if ($productCount > 0) {
                    echo "<script>
                        alert('Cannot delete this category because it contains products. Please delete all products in this category first.');
                    </script>";
                } else {
                    $stmt = $conn->prepare("DELETE FROM Category WHERE Category_Id = :category_id");
                    $stmt->execute([':category_id' => $category_id]);
                    echo "<script>
                        alert('Category deleted successfully.');
                    </script>";
                }
            } else {
                echo "<div class='alert alert-danger'>Error: You don't have permission to delete this category.</div>";
            }
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

try {
    $check_stmt = $conn->query("DESCRIBE Category");
    $columns = $check_stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<!-- Category columns: " . implode(", ", $columns) . " -->";
    
    if (!in_array('Company_Code', $columns)) {
        echo "<div class='alert alert-danger'>Error: Company_Code column is missing in Category table</div>";
    }
} catch (Exception $e) {
    echo "<!-- Structure check error: " . $e->getMessage() . " -->";
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT Role FROM Users WHERE User_Id = :user_id");
$stmt->execute([':user_id' => $user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
$isManager = isset($user['Role']) && ($user['Role'] == 2 || $user['Role'] == 1); 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    
    if (isset($_POST['action']) && $_POST['action'] == 'add_order') {
        $customerName = $_POST['customer_name'];
        $orderDate = $_POST['order_date'];
        $status = $_POST['status'];
        $paymentStatus = $_POST['payment_status'];
        
        try {
            $insertOrderStmt = $conn->prepare("
                INSERT INTO Orders (Order_Date, Status, Users_User_Id, Total_Amount, Payment_Status, Customer_Name) 
                VALUES (?, ?, ?, 0, ?, ?)
            ");
            $insertOrderStmt->execute([$orderDate, $status, $_SESSION['user_id'], $paymentStatus, $customerName]);
            
            $orderId = $conn->lastInsertId();
            
            $_SESSION['alert_message'] = "Order created successfully! You can now add products to this order.";
            $_SESSION['alert_type'] = "success";
            
            header("Location: order-details.php?order_id=" . $orderId);
            exit;
            
        } catch (PDOException $e) {
            $_SESSION['alert_message'] = "Error creating order: " . $e->getMessage();
            $_SESSION['alert_type'] = "danger";
            
            header("Location: First.php#orders");
            exit;
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM Category WHERE Company_Code = :company_code");
$stmt->bindParam(':company_code', $company_code);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<!-- Category query using company_code: $company_code -->";
echo "<!-- Found " . count($categories) . " categories -->";

$stmt = $conn->prepare("
    SELECT o.*, u.Name as CreatedByName 
    FROM Orders o
    LEFT JOIN Users u ON o.Users_User_Id = u.User_Id
    WHERE u.Company_Code = :company_code
    ORDER BY o.Order_Date DESC
");
$stmt->execute([':company_code' => $company_code]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ordersCount = count($orders);

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_order_status') {
    $orderId = $_POST['order_id'];
    $newStatus = $_POST['new_status'];
    $newPaymentStatus = $_POST['new_payment_status'];
    
    try {
        $stmt = $conn->prepare("UPDATE Orders SET Status = ?, Payment_Status = ? WHERE Order_Id = ?");
        $result = $stmt->execute([$newStatus, $newPaymentStatus, $orderId]);
        
        if ($result) {
            echo "<div class='alert alert-success'>Order status updated successfully!</div>";
        } else {
            echo "<div class='alert alert-danger'>Failed to update order status.</div>";
        }
    } catch (PDOException $e) {
        echo "<div class='alert alert-danger'>Error updating order: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_order') {
    $orderId = $_POST['order_id'];
    
    try {
        $checkStmt = $conn->prepare("SELECT * FROM Orders WHERE Order_Id = ?");
        $checkStmt->execute([$orderId]);
        $orderData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$orderData) {
            $_SESSION['alert_message'] = "Error: Order not found.";
            $_SESSION['alert_type'] = "danger";
        } else {
            $deleteItemsStmt = $conn->prepare("DELETE FROM Order_Products WHERE Orders_Order_Id = ?");
            $deleteItemsStmt->execute([$orderId]);
            
            $deleteOrderStmt = $conn->prepare("DELETE FROM Orders WHERE Order_Id = ?");
            $deleteOrderStmt->execute([$orderId]);
            
            $_SESSION['alert_message'] = "Order deleted successfully!";
            $_SESSION['alert_type'] = "success";
        }
    } catch (PDOException $e) {
        $_SESSION['alert_message'] = "Database error: " . $e->getMessage();
        $_SESSION['alert_type'] = "danger";
    }
    
    header("Location: First.php#orders");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Warehouse Dashboard</title>
    <link rel="stylesheet" href="First.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="top-info-bar">
        <div class="company-info">
            <h1 class="company-name" data-company-id="1"><?= htmlspecialchars($company_name) ?> Warehouse</h1>
        </div>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>

    <div class="sidebar">
        <ul class="menu">
            <li onclick="showTab('inventory')" class="active">
                <a href="#">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                </a>
            </li>
            <li onclick="showTab('orders')">
                <a href="#">
                    <i class="fas fa-shipping-fast"></i>
                    <span>Orders</span>
                </a>
            </li>
            <li onclick="showTab('analytics')">
                <a href="#">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-footer">
            <a href="#" class="help-logo" onclick="document.getElementById('help-modal').style.display='flex'; return false;">
                <i class="fas fa-question-circle"></i>
                <span>Help</span>
            </a>
        </div>
    </div>

    <div class="main-content">
        <div id="inventory" class="tab-content active">
            <h2>Inventory</h2>
            <div id="inventory-types">
                <div class="empty-message" id="empty-message" style="<?= count($categories) > 0 ? 'display: none;' : '' ?>">
                    <p><span id="inventory-count"><?= count($categories) ?></span> inventory types found.</p>
                </div>
                <button id="add-inventory-btn">Add Inventory Type</button>
                <div class="inventory-list" id="inventory-list">
                    <?php foreach ($categories as $category): ?>
                    <div class="inventory-type">
                        <h4><?= htmlspecialchars($category['Name']) ?></h4>
                        <p>Description: <?= htmlspecialchars($category['Description']) ?></p>
                        <button onclick="window.location.href='inventory-details.php?type=<?= urlencode($category['Name']) ?>&category_id=<?= $category['Category_Id'] ?>'">View Details</button>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_inventory">
                            <input type="hidden" name="category_id" value="<?= $category['Category_Id'] ?>">
                            <button type="submit" class="remove-btn">Remove</button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="orders" class="tab-content">
            <h2>Orders</h2>
            
            <?php if (isset($_SESSION['alert_message'])): ?>
                <div class="alert alert-<?= $_SESSION['alert_type'] ?>">
                    <?= $_SESSION['alert_message'] ?>
                </div>
                <?php 
                unset($_SESSION['alert_message']);
                unset($_SESSION['alert_type']);
                ?>
            <?php endif; ?>
            
            <div id="orders-types">
                <div class="empty-message" id="orders-empty-message" style="<?= count($orders) > 0 ? 'display: none;' : '' ?>">
                    <p><span id="orders-count"><?= count($orders) ?></span> orders found.</p>
                </div>
                <button id="add-order-btn">Add New Order</button>
                <div class="orders-list" id="orders-list">
                    <?php foreach ($orders as $order): ?>
                    <div class="order-type <?= strtolower($order['Status']) ?>">
                        <h4>Order #<?= $order['Order_Id'] ?></h4>
                        <p>Date: <?= date('M d, Y', strtotime($order['Order_Date'])) ?></p>
                        <p>Customer: <?= htmlspecialchars($order['Customer_Name'] ?? 'N/A') ?></p>
                        <p><span class="status-badge status-<?= strtolower($order['Status']) ?>"><?= htmlspecialchars($order['Status']) ?></span></p>
                        <p>Total: $<?= number_format($order['Total_Amount'], 2) ?></p>
                        <td>
                            <button class="view-order-btn" onclick="window.location.href='order-details.php?order_id=<?= $order['Order_Id'] ?>'">View Details</button>

                            <?php if ($isManager): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete_order">
                                    <input type="hidden" name="order_id" value="<?= $order['Order_Id'] ?>">
                                    <button type="submit" class="remove-order-btn">Remove</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div id="analytics" class="tab-content">
            <h2>Analytics Dashboard</h2>
            
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>Total Inventory</h3>
                    <div class="metric">
                        <?php
                        $stmt = $conn->prepare("SELECT SUM(Stock_Quantity) FROM Product WHERE Company_Code = :company_code");
                        $stmt->execute([':company_code' => $company_code]);
                        $totalStock = $stmt->fetchColumn() ?: 0;
                        echo number_format($totalStock);
                        ?>
                    </div>
                    <p>items in stock</p>
                </div>
                
                <div class="analytics-card">
                    <h3>Total Orders</h3>
                    <div class="metric">
                        <?php
                        $stmt = $conn->prepare("
                            SELECT COUNT(*) FROM Orders o
                            JOIN Users u ON o.Users_User_Id = u.User_Id
                            WHERE u.Company_Code = :company_code
                        ");
                        $stmt->execute([':company_code' => $company_code]);
                        $totalOrders = $stmt->fetchColumn() ?: 0;
                        echo number_format($totalOrders);
                        ?>
                    </div>
                    <p>orders processed</p>
                </div>
                
                <div class="analytics-card">
                    <h3>Total Revenue</h3>
                    <div class="metric">$
                        <?php
                        $stmt = $conn->prepare("
                            SELECT SUM(Total_Amount) FROM Orders o
                            JOIN Users u ON o.Users_User_Id = u.User_Id
                            WHERE u.Company_Code = :company_code
                        ");
                        $stmt->execute([':company_code' => $company_code]);
                        $totalRevenue = $stmt->fetchColumn() ?: 0;
                        echo number_format($totalRevenue, 2);
                        ?>
                    </div>
                    <p>total revenue</p>
                </div>
                
                <div class="analytics-card">
                    <h3>Low Stock Items</h3>
                    <div class="metric">
                        <?php
                        $stmt = $conn->prepare("
                            SELECT COUNT(*) FROM Product 
                            WHERE Company_Code = :company_code AND Stock_Quantity < 10
                        ");
                        $stmt->execute([':company_code' => $company_code]);
                        $lowStockCount = $stmt->fetchColumn() ?: 0;
                        echo number_format($lowStockCount);
                        ?>
                    </div>
                    <p>items below 10 units</p>
                </div>
            </div>
            
            <div class="analytics-tables">
                <div class="table-container">
                    <h3>Inventory by Category</h3>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Number of Products</th>
                                <th>Total Stock</th>
                                <th>Average Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("
                                SELECT 
                                    c.Name as CategoryName,
                                    COUNT(p.Product_Id) as ProductCount,
                                    SUM(p.Stock_Quantity) as TotalStock,
                                    AVG(p.Price) as AveragePrice
                                FROM Category c
                                JOIN Product p ON c.Category_Id = p.Category_Category_Id
                                WHERE p.Company_Code = :company_code
                                GROUP BY c.Category_Id
                                ORDER BY TotalStock DESC
                            ");
                            $stmt->execute([':company_code' => $company_code]);
                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($categories) > 0):
                                foreach ($categories as $category):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($category['CategoryName']) ?></td>
                                <td><?= number_format($category['ProductCount']) ?></td>
                                <td><?= number_format($category['TotalStock']) ?></td>
                                <td>$<?= number_format($category['AveragePrice'], 2) ?></td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="empty-message">No categories found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-container">
                    <h3>Top Selling Products</h3>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Units Sold</th>
                                <th>Revenue</th>
                                <th>Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("
                                SELECT 
                                    p.Name, 
                                    p.Stock_Quantity,
                                    SUM(op.Quantity) as TotalSold, 
                                    SUM(op.Quantity * op.Price_At_Time) as Revenue
                                FROM Order_Products op
                                JOIN Product p ON op.Product_Product_Id = p.Product_Id
                                JOIN Orders o ON op.Orders_Order_Id = o.Order_Id
                                JOIN Users u ON o.Users_User_Id = u.User_Id
                                WHERE u.Company_Code = :company_code
                                GROUP BY p.Product_Id
                                ORDER BY TotalSold DESC
                                LIMIT 10
                            ");
                            $stmt->execute([':company_code' => $company_code]);
                            $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($topProducts) > 0):
                                foreach ($topProducts as $product):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($product['Name']) ?></td>
                                <td><?= number_format($product['TotalSold']) ?></td>
                                <td>$<?= number_format($product['Revenue'], 2) ?></td>
                                <td><?= number_format($product['Stock_Quantity']) ?></td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="empty-message">No sales data available</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-container">
                    <h3>Low Stock Items</h3>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Current Stock</th>
                                <th>Category</th>
                                <th>Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("
                                SELECT 
                                    p.Name, 
                                    p.Stock_Quantity, 
                                    c.Name as CategoryName,
                                    p.Price
                                FROM Product p
                                JOIN Category c ON p.Category_Category_Id = c.Category_Id
                                WHERE p.Company_Code = :company_code
                                ORDER BY p.Stock_Quantity ASC
                                LIMIT 10
                            ");
                            $stmt->execute([':company_code' => $company_code]);
                            $lowStockItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($lowStockItems) > 0):
                                foreach ($lowStockItems as $item):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($item['Name']) ?></td>
                                <td><?= number_format($item['Stock_Quantity']) ?></td>
                                <td><?= htmlspecialchars($item['CategoryName']) ?></td>
                                <td>$<?= number_format($item['Price'], 2) ?></td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="empty-message">No products found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="table-container">
                    <h3>Monthly Orders Summary</h3>
                    <table class="analytics-table">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Orders</th>
                                <th>Revenue</th>
                                <th>Avg. Order Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $conn->prepare("
                                SELECT 
                                    DATE_FORMAT(o.Order_Date, '%b %Y') as Month,
                                    COUNT(*) as OrderCount,
                                    SUM(o.Total_Amount) as TotalRevenue,
                                    AVG(o.Total_Amount) as AverageOrderValue
                                FROM Orders o
                                JOIN Users u ON o.Users_User_Id = u.User_Id
                                WHERE u.Company_Code = :company_code
                                AND o.Order_Date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                                GROUP BY Month
                                ORDER BY MIN(o.Order_Date) DESC
                                LIMIT 6
                            ");
                            $stmt->execute([':company_code' => $company_code]);
                            $monthlyOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (count($monthlyOrders) > 0):
                                foreach ($monthlyOrders as $month):
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($month['Month']) ?></td>
                                <td><?= number_format($month['OrderCount']) ?></td>
                                <td>$<?= number_format($month['TotalRevenue'], 2) ?></td>
                                <td>$<?= number_format($month['AverageOrderValue'], 2) ?></td>
                            </tr>
                            <?php
                                endforeach;
                            else:
                            ?>
                            <tr>
                                <td colspan="4" class="empty-message">No orders in the last 6 months</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>


                        
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="add-inventory-modal" class="modal">
        <div class="modal-content">
            <h3>Add New Inventory Type</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_inventory">
                <div class="form-group">
                    <label for="inventory-type-name">Inventory Type Name:</label>
                    <input type="text" id="inventory-type-name" name="name" placeholder="Enter inventory type name" maxlength="15" required />
                </div>
                <div class="form-group">
                    <label for="inventory-description">Description:</label>
                    <textarea id="inventory-description" name="description" placeholder="Enter inventory description" maxlength="30" required></textarea>
                </div>
                <div class="form-group buttons">
                    <button type="submit" id="save-inventory-type">Save</button>
                    <button type="button" id="close-modal">Close</button>
                </div>
            </form>
        </div>
    </div>
   
    <div id="add-order-modal" class="modal">
        <div class="modal-content">
            <h3>Add New Order</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_order">
            
                <div class="form-group">
                    <label for="order-customer-name">Customer Name:</label>
                    <input type="text" id="order-customer-name" name="customer_name" placeholder="Enter customer name" maxlength="20" required />
                </div>
            
                <div class="form-group">
                    <label for="order-date">Order Date:</label>
                    <input type="date" id="order-date" name="order_date" required>
                </div>
            
                <div class="form-group">
                    <label for="order-status">Status:</label>
                    <select id="order-status" name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            
                <div class="form-group">
                    <label for="order-payment">Payment Status:</label>
                    <select id="order-payment" name="payment_status" required>
                        <option value="Pending">Pending</option>
                        <option value="Paid">Paid</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
            
                <div class="form-group buttons">
                    <button type="submit" id="save-order">Create Order</button>
                    <button type="button" id="close-order-modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-order-modal" class="modal">
        <div class="modal-content">
            <h3>Edit Order</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_order">
                <input type="hidden" id="edit-order-id" name="order_id">
                <div class="form-group">
                    <label for="edit-customer-name">Customer Name:</label>
                    <input type="text" id="edit-customer-name" name="customer_name" placeholder="Enter customer name" required />
                </div>
                <div class="form-group">
                    <label for="edit-order-status">Status:</label>
                    <select id="edit-order-status" name="status" required>
                        <option value="Pending">Pending</option>
                        <option value="Processing">Processing</option>
                        <option value="Shipped">Shipped</option>
                        <option value="Delivered">Delivered</option>
                        <option value="Cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-order-amount">Total Amount:</label>
                    <input type="number" id="edit-order-amount" name="total_amount" step="0.01" min="0" placeholder="Enter total amount" required />
                </div>
                <div class="form-group buttons">
                    <button type="submit" id="update-order">Update</button>
                    <button type="button" id="close-edit-order-modal">Close</button>
                </div>
            </form>
        </div>
    </div>
   
    <div id="help-modal" class="modal">
        <div class="modal-content" style="width: 600px; max-width: 90%;">
            <h3>Contact System Administrators</h3>
            <p>If you need assistance, you can contact any of the following system administrators:</p>
            
            <div class="admin-contacts">
                <?php
                $userCompanyCode = $_SESSION['company_code'] ?? '';
                
                $stmt = $conn->prepare("
                    SELECT Name, Email 
                    FROM Users 
                    WHERE Role = 1  
                    AND Approval_Status = 1
                    AND Company_Code = :company_code
                    ORDER BY Name ASC
                ");
                $stmt->execute([':company_code' => $userCompanyCode]);
                $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($admins) > 0):
                ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?= htmlspecialchars($admin['Name']) ?></td>
                                <td><a href="mailto:<?= htmlspecialchars($admin['Email']) ?>"><?= htmlspecialchars($admin['Email']) ?></a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p class="no-admin-message">No system administrators found for your company.</p>
                <?php endif; ?>
            </div>
            
            <div class="form-group buttons">
                <button type="button" id="close-help-modal" onclick="document.getElementById('help-modal').style.display='none';" class="btn-danger">Close</button>
            </div>
        </div>
    </div>
   
    <script src="First.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var addOrderBtn = document.getElementById('add-order-btn');
        var addOrderModal = document.getElementById('add-order-modal');
        
        if (addOrderBtn) {
            console.log('Add order button found');
            addOrderBtn.onclick = function() {
                console.log('Add order button clicked');
                if (addOrderModal) {
                    addOrderModal.style.display = 'block';
                    console.log('Setting modal display to block');
                } else {
                    console.log('Modal not found');
                }
            };
        } else {
            console.log('Add order button not found');
        }
    });
    </script>
    <script>
      document.addEventListener('click', function(event) {
        if (event.target.classList.contains('cancel-btn') || 
            event.target.id === 'close-order-modal' ||
            (event.target.tagName.toLowerCase() === 'button' && 
             event.target.textContent.trim() === 'Cancel')) {
      
          console.log('Cancel button clicked!');
      
          const modal = event.target.closest('.modal');
          if (modal) {
            console.log('Modal found, closing...');
            modal.style.display = 'none';
          }
        }
      });
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('order-date');
        
        if (dateInput) {
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
            
            dateInput.min = formatDate(minDate);
            dateInput.max = formatDate(maxDate);
            
            dateInput.value = formatDate(today);
        }
    });
    </script>
</body>
</html>
