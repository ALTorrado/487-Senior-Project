<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connect.php';
session_start();

$isAdmin = isset($_SESSION['role']) && $_SESSION['role'] == 1;


$stmt = $conn->query("SELECT * FROM Company");
$companies = $stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_company_status') {
    $code = $_POST['code'];
    $status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE Company SET Status = :status WHERE Company_Code = :code");
        $result = $stmt->execute([':status' => $status, ':code' => $code]);
        
        if ($result) {
            $_SESSION['message'] = "Company status updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to update company status.";
            $_SESSION['message_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error updating company status: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
  
    header("Location: admin_dashboard.php" . (isset($_POST['refresh_tab']) ? "#".$_POST['refresh_tab'] : ""));
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_company') {
    $code = $_POST['code'];
    
    try {
       
        $stmt = $conn->prepare("SELECT COUNT(*) FROM Users WHERE Company_Code = :code");
        $stmt->execute([':code' => $code]);
        $userCount = $stmt->fetchColumn();
        
        if ($userCount > 0) {
            
            $stmt = $conn->prepare("DELETE FROM Users WHERE Company_Code = :code");
            $stmt->execute([':code' => $code]);
        }
        
        
        $stmt = $conn->prepare("DELETE FROM Company WHERE Company_Code = :code");
        $result = $stmt->execute([':code' => $code]);
        
        if ($result) {
            $_SESSION['message'] = "Company deleted successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to delete company.";
            $_SESSION['message_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error deleting company: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
    
    header("Location: admin_dashboard.php#companies");
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_company') {
    $code = $_POST['code'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    
    try {
       
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Company WHERE Company_Email = :email AND Company_Code != :code");
        $checkStmt->execute([':email' => $email, ':code' => $code]);
        $emailExists = (int)$checkStmt->fetchColumn();
        
        if ($emailExists > 0) {
            $_SESSION['message'] = "A company with this email already exists.";
            $_SESSION['message_type'] = "error";
        } else {
           
            $stmt = $conn->prepare("UPDATE Company SET Name = :name, Company_Email = :email WHERE Company_Code = :code");
            $result = $stmt->execute([':name' => $name, ':email' => $email, ':code' => $code]);
            
            if ($result) {
                $_SESSION['message'] = "Company updated successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to update company.";
                $_SESSION['message_type'] = "error";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error updating company: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
   
    header("Location: admin_dashboard.php#companies");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_employee_status') {
    $userId = $_POST['user_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $conn->prepare("UPDATE Users SET Approval_Status = :status WHERE User_Id = :id");
        $result = $stmt->execute([':status' => $status, ':id' => $userId]);
        
        if ($result) {
            $_SESSION['message'] = "Employee status updated successfully!";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Failed to update employee status.";
            $_SESSION['message_type'] = "error";
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error updating employee status: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
    
    if (isset($_POST['refresh_tab']) && $_POST['refresh_tab'] === 'pending') {
        header("Location: admin_dashboard.php#pending");
        exit;
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_company') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $code = bin2hex(random_bytes(8));
    $status = isset($_POST['status']) ? $_POST['status'] : 0; 
    
    try {
        
        $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Company WHERE Company_Email = :email");
        $checkStmt->execute([':email' => $email]);
        $emailExists = (int)$checkStmt->fetchColumn();
        
        if ($emailExists > 0) {
            $_SESSION['message'] = "A company with this email already exists.";
            $_SESSION['message_type'] = "error";
        } else {
           
            $stmt = $conn->prepare("INSERT INTO Company (Name, Company_Email, Company_Code, Status) 
                           VALUES (:name, :email, :code, :status)");
            $result = $stmt->execute([
                ':name' => $name, 
                ':email' => $email, 
                ':code' => $code,
                ':status' => $status
            ]);
            
            if ($result) {
                $_SESSION['message'] = "Company added successfully!";
                $_SESSION['message_type'] = "success";
            } else {
                $_SESSION['message'] = "Failed to add company.";
                $_SESSION['message_type'] = "error";
            }
        }
    } catch (PDOException $e) {
        $_SESSION['message'] = "Error adding company: " . $e->getMessage();
        $_SESSION['message_type'] = "error";
    }
    
    
    header("Location: admin_dashboard.php#companies");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert-container">
                <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                    <?= $_SESSION['message'] ?>
                    <span class="close-alert" onclick="this.parentElement.parentElement.style.display='none';">×</span>
                </div>
            </div>
            <?php 
            
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
            ?>
        <?php endif; ?>

        <div class="top-info-bar">
            <h1>Admin Dashboard</h1>
        </div>

        <nav class="admin-nav">
            <ul class="menu">
                <li class="active" data-tab="companies">
                    <i class="fas fa-building"></i>
                    <span>Companies</span>
                </li>
                <li data-tab="pending">
                    <i class="fas fa-clock"></i>
                    <span>Pending Approvals</span>
                </li>
                <li data-tab="admins">
                    <i class="fas fa-user-shield"></i>
                    <span>Admins</span>
                </li>
            </ul>
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </nav>

        <div class="main-content">
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert-container">
                    <div class="alert alert-<?= $_SESSION['message_type'] ?>">
                        <?= $_SESSION['message'] ?>
                        <span class="close-alert" onclick="this.parentElement.parentElement.style.display='none';">×</span>
                    </div>
                </div>
                <?php 
                
                unset($_SESSION['message']);
                unset($_SESSION['message_type']);
                ?>
            <?php endif; ?>

            <section id="companies" class="tab-content active">
                <div class="header-container">
                    <h2>Companies</h2>
                    <button id="add-company-btn" class="action-btn">Add Company</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company Code</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($companies as $company): ?>
                        <tr>
                            <td class="company-name"><?= htmlspecialchars($company['Name']) ?></td>
                            <td class="company-email"><?= htmlspecialchars($company['Company_Email']) ?></td>
                            <td><?= htmlspecialchars($company['Company_Code']) ?></td>
                            <td>
                                <?php 
                                $status = isset($company['Status']) ? (int)$company['Status'] : 0;
                                if ($status === 0) {
                                    echo '<span class="status-badge pending">Pending</span>';
                                } else if ($status === 1) {
                                    echo '<span class="status-badge approved">Approved</span>';
                                } else if ($status === 2) {
                                    echo '<span class="status-badge rejected">Rejected</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <button class="view-employees" data-code="<?= $company['Company_Code'] ?>">View Employees</button>
                                <button class="edit-company" data-code="<?= $company['Company_Code'] ?>">Edit</button>
                                <button class="delete-company" data-code="<?= $company['Company_Code'] ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
                    
            <section id="pending" class="tab-content">
                <div class="header-container">
                    <h2>Pending Approvals</h2>
                </div>
                <div class="approval-sections">
                    <div class="approval-box">
                        <h3>Companies Pending Approval</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Company Name</th>
                                    <th>Email</th>
                                    <th>Company Code</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                
                                $pendingStmt = $conn->query("SELECT * FROM Company WHERE Status = 0");
                                $pendingCompanies = $pendingStmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($pendingCompanies as $company): 
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($company['Name']) ?></td>
                                    <td><?= htmlspecialchars($company['Company_Email']) ?></td>
                                    <td><?= htmlspecialchars($company['Company_Code']) ?></td>
                                    <td>
                                        <button class="approve-company-pending action-btn" data-code="<?= $company['Company_Code'] ?>">Approve</button>
                                        <button class="reject-company-pending action-btn" data-code="<?= $company['Company_Code'] ?>">Reject</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($pendingCompanies) === 0): ?>
                                <tr>
                                    <td colspan="4" class="empty-message">No companies pending approval</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="approval-box">
                        <h3>Employees Pending Approval</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Company</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                
                                $pendingEmployeesStmt = $conn->query("
                                    SELECT u.User_Id, u.Name, u.Email, u.Company_Code, c.Name as CompanyName 
                                    FROM Users u
                                    LEFT JOIN Company c ON u.Company_Code = c.Company_Code
                                    WHERE u.Approval_Status = 0
                                ");
                                $pendingEmployees = $pendingEmployeesStmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                if (count($pendingEmployees) > 0):
                                    foreach ($pendingEmployees as $employee): 
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($employee['Name']) ?></td>
                                    <td><?= htmlspecialchars($employee['Email']) ?></td>
                                    <td>
                                        <?php if ($employee['CompanyName']): ?>
                                            <?= htmlspecialchars($employee['CompanyName']) ?>
                                        <?php else: ?>
                                            <span class="no-company">No Company</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="approve-employee action-btn" data-id="<?= $employee['User_Id'] ?>">Approve</button>
                                        <button class="reject-employee action-btn" data-id="<?= $employee['User_Id'] ?>">Reject</button>
                                    </td>
                                </tr>
                                <?php 
                                    endforeach;
                                else:
                                ?>
                                <tr>
                                    <td colspan="4" class="empty-message">No employees pending approval</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="admins" class="tab-content">
                <div class="header-container">
                    <h2>System Administrators</h2>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Company</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        
                        $adminsStmt = $conn->query("
                            SELECT u.User_Id, u.Name, u.Email, u.Approval_Status, c.Name as CompanyName, c.Company_Code 
                            FROM Users u
                            LEFT JOIN Company c ON u.Company_Code = c.Company_Code
                            WHERE u.Role = 1
                            ORDER BY c.Name, u.Name
                        ");
                        $admins = $adminsStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($admins) > 0):
                            foreach ($admins as $admin): 
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($admin['Name']) ?></td>
                            <td><?= htmlspecialchars($admin['Email']) ?></td>
                            <td>
                                <?php if ($admin['Company_Code']): ?>
                                    <a href="view_employees.php?company_code=<?= $admin['Company_Code'] ?>" class="company-link">
                                        <?= htmlspecialchars($admin['CompanyName']) ?>
                                    </a>
                                <?php else: ?>
                                    <span class="no-company">No Company</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $status = (int)$admin['Approval_Status'];
                                if ($status === 0) {
                                    echo '<span class="status-badge pending">Pending</span>';
                                } else if ($status === 1) {
                                    echo '<span class="status-badge approved">Approved</span>';
                                } else if ($status === 2) {
                                    echo '<span class="status-badge rejected">Rejected</span>';
                                }
                                ?>
                            </td>
                        </tr>
                        <?php 
                            endforeach; 
                        else:
                        ?>
                        <tr>
                            <td colspan="4" class="empty-message">No administrators found</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </div>
    </div>

    <!-- Add Company Modal -->
    <div id="add-company-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Company</h3>
                <button class="close-modal">×</button>
            </div>
            <form id="add-company-form" method="POST" action="admin_dashboard.php">
                <input type="hidden" name="action" value="add_company">
                <div class="form-group">
                    <label for="company-name">Company Name</label>
                    <input type="text" id="company-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="company-email">Company Email</label>
                    <input type="email" id="company-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="company-status">Status</label>
                    <select id="company-status" name="status">
                        <option value="0">Pending</option>
                        <option value="1">Approved</option>
                        <option value="2">Rejected</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="confirm-btn">Add Company</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Company Modal -->
    <div id="edit-company-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Company</h3>
                <button class="close-modal">×</button>
            </div>
            <form id="edit-company-form" method="POST" action="admin_dashboard.php">
                <input type="hidden" name="action" value="edit_company">
                <div class="form-group">
                    <label for="edit-company-name">Company Name</label>
                    <input type="text" id="edit-company-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit-company-email">Company Email</label>
                    <input type="email" id="edit-company-email" name="email" required>
                </div>
                <input type="hidden" id="edit-company-code" name="code">
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="confirm-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Company Modal -->
    <div id="delete-company-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Company</h3>
                <button class="close-modal">×</button>
            </div>
            <p>Are you sure you want to delete this company? This action cannot be undone.</p>
            <form id="delete-company-form" method="POST" action="admin_dashboard.php">
                <input type="hidden" name="action" value="delete_company">
                <input type="hidden" id="delete-company-code" name="code" value="">
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="confirm-btn">Delete Company</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Include external JavaScript file -->
    <script src="admin_dashboard.js"></script>
</body>
</html>
