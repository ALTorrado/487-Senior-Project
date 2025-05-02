<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connect.php';
require_once 'session.php';

requireAdmin();

$isSuperAdmin = empty($_SESSION['company_code']);
$userCompanyCode = $_SESSION['company_code'];

if (!isset($_GET['company_code'])) {
    header("Location: admin_dashboard.php");
    exit;
}

$company_code = $_GET['company_code'];

if (!$isSuperAdmin && $company_code !== $userCompanyCode) {
    $_SESSION['message'] = "Access denied: You can only view your own company.";
    $_SESSION['message_type'] = "error";
    header("Location: admin_dashboard.php");
    exit;
}

$companyStmt = $conn->prepare("SELECT * FROM Company WHERE Company_Code = :code");
$companyStmt->execute([':code' => $company_code]);
$company = $companyStmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header("Location: admin_dashboard.php");
    exit;
}


$employeesStmt = $conn->prepare("
    SELECT User_Id, Name, Email, Role, Approval_Status 
    FROM Users 
    WHERE Company_Code = :code
    ORDER BY Role DESC, Name
");
$employeesStmt->execute([':code' => $company_code]);
$employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    switch($action) {
        case 'add_employee':
            $name = $_POST['name'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT); 
            $role = $_POST['role'];
            $approval_status = $_POST['approval_status'];
            
            try {
                $stmt = $conn->prepare("
                    INSERT INTO Users (Name, Email, Password, Role, Company_Code, Approval_Status) 
                    VALUES (:name, :email, :password, :role, :company_code, :approval_status)
                ");
                $result = $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $password,
                    ':role' => $role,
                    ':company_code' => $company_code,
                    ':approval_status' => $approval_status
                ]);
                
                if ($result) {
                    $_SESSION['message'] = "Employee added successfully!";
                    $_SESSION['message_type'] = "success";
                } else {
                    $_SESSION['message'] = "Failed to add employee.";
                    $_SESSION['message_type'] = "error";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && 
                    strpos($e->getMessage(), 'Email') !== false) {
                    $_SESSION['message'] = "An employee with this email already exists.";
                } else {
                    $_SESSION['message'] = "Error adding employee: " . $e->getMessage();
                }
                $_SESSION['message_type'] = "error";
            }
            header("Location: view_employees.php?company_code=$company_code");
            exit;
            
        case 'update_employee':
            $user_id = $_POST['user_id'];
            $name = $_POST['name'];
            $email = $_POST['email'];
            $role = $_POST['role'];
            $approval_status = $_POST['approval_status'];
            
            try {
                
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Users WHERE Email = :email AND User_Id != :user_id");
                $checkStmt->execute([':email' => $email, ':user_id' => $user_id]);
                $emailExists = (int)$checkStmt->fetchColumn();
                
                if ($emailExists > 0) {
                    $_SESSION['message'] = "An employee with this email already exists.";
                    $_SESSION['message_type'] = "error";
                } else {
                    
                    if (!empty($_POST['password'])) {
                        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("
                            UPDATE Users 
                            SET Name = :name, Email = :email, Password = :password, Role = :role, Approval_Status = :approval_status
                            WHERE User_Id = :user_id
                        ");
                        $result = $stmt->execute([
                            ':name' => $name,
                            ':email' => $email,
                            ':password' => $password,
                            ':role' => $role,
                            ':approval_status' => $approval_status,
                            ':user_id' => $user_id
                        ]);
                    } else {
                        $stmt = $conn->prepare("
                            UPDATE Users 
                            SET Name = :name, Email = :email, Role = :role, Approval_Status = :approval_status
                            WHERE User_Id = :user_id
                        ");
                        $result = $stmt->execute([
                            ':name' => $name,
                            ':email' => $email,
                            ':role' => $role,
                            ':approval_status' => $approval_status,
                            ':user_id' => $user_id
                        ]);
                    }
                    
                    if ($result) {
                        $_SESSION['message'] = "Employee updated successfully!";
                        $_SESSION['message_type'] = "success";
                    } else {
                        $_SESSION['message'] = "Failed to update employee.";
                        $_SESSION['message_type'] = "error";
                    }
                }
            } catch (PDOException $e) {
                $_SESSION['message'] = "Error updating employee: " . $e->getMessage();
                $_SESSION['message_type'] = "error";
            }
            header("Location: view_employees.php?company_code=$company_code");
            exit;
            
        case 'delete_employee':
            $user_id = $_POST['user_id'];
            $is_self_delete = ($user_id == $_SESSION['user_id']);
            
            try {
                $conn->beginTransaction();
                
                $stmt = $conn->prepare("
                    DELETE FROM Order_Products 
                    WHERE Orders_Order_Id IN (
                        SELECT Order_Id FROM Orders WHERE Users_User_Id = :user_id
                    )
                ");
                $stmt->execute([':user_id' => $user_id]);
                
                $stmt = $conn->prepare("DELETE FROM Orders WHERE Users_User_Id = :user_id");
                $stmt->execute([':user_id' => $user_id]);
                
                $stmt = $conn->prepare("DELETE FROM Users WHERE User_Id = :user_id");
                $result = $stmt->execute([':user_id' => $user_id]);
                
                $conn->commit();
                
                if ($result) {
                    if ($is_self_delete) {
                        session_destroy();
                        header("Location: login.php?message=self_deleted");
                        exit;
                    } else {
                        $_SESSION['message'] = "Employee deleted successfully!";
                        $_SESSION['message_type'] = "success";
                    }
                } else {
                    $_SESSION['message'] = "Failed to delete employee.";
                    $_SESSION['message_type'] = "error";
                }
            } catch (PDOException $e) {
                $conn->rollBack();
                $_SESSION['message'] = "Error deleting employee: " . $e->getMessage();
                $_SESSION['message_type'] = "error";
            }
            
            if (!$is_self_delete) {
                header("Location: view_employees.php?company_code=$company_code");
            }
            exit;
    }
}


function getRoleName($roleId) {
    switch($roleId) {
        case 0:
            return "Employee";
        case 1:
            return "Admin";
        case 2:
            return "Manager";
        default:
            return "Unknown";
    }
}


function getRoleBadgeClass($roleId) {
    switch($roleId) {
        case 0:
            return "user";
        case 1:
            return "admin";
        case 2:
            return "manager";
        default:
            return "user";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Employees - <?= htmlspecialchars($company['Name']) ?></title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <link rel="stylesheet" href="view_employees.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body data-current-user-id="<?= $_SESSION['user_id'] ?>">
    <div class="top-info-bar">
        <h1>Admin Dashboard</h1>
    </div>

    <div class="main-content" style="margin-left: 0; width: 100%;">
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
        
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        
        <div class="employee-details">
            <div class="company-header">
                <div class="company-info">
                    <h1><?= htmlspecialchars($company['Name']) ?></h1>
                    <p>Company Code: <?= htmlspecialchars($company['Company_Code']) ?></p>
                    <p>Email: <?= htmlspecialchars($company['Company_Email']) ?></p>
                </div>
                <div>
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
                </div>
            </div>
            
            <div class="header-container">
                <h2>Employees</h2>
                <button id="add-employee-btn" class="action-btn">Add Employee</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($employees) > 0): ?>
                        <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td class="employee-name"><?= htmlspecialchars($employee['Name']) ?></td>
                            <td class="employee-email"><?= htmlspecialchars($employee['Email']) ?></td>
                            <td>
                                <?php 
                                $role = (int)$employee['Role'];
                                $roleBadgeClass = getRoleBadgeClass($role);
                                $roleName = getRoleName($role);
                                echo "<span class='role-badge {$roleBadgeClass}'>{$roleName}</span>";
                                ?>
                            </td>
                            <td>
                                <?php 
                                $status = (int)$employee['Approval_Status'];
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
                                <button class="edit-employee action-btn" 
                                        data-id="<?= $employee['User_Id'] ?>"
                                        data-name="<?= htmlspecialchars($employee['Name']) ?>"
                                        data-email="<?= htmlspecialchars($employee['Email']) ?>"
                                        data-role="<?= $employee['Role'] ?>"
                                        data-status="<?= $employee['Approval_Status'] ?>">
                                    Edit
                                </button>
                                <button class="delete-employee action-btn" data-id="<?= $employee['User_Id'] ?>">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="empty-message">No employees found for this company</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="add-employee-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Add New Employee</h3>
                <button class="close-modal">×</button>
            </div>
            <form id="add-employee-form" method="POST">
                <input type="hidden" name="action" value="add_employee">
                <div class="form-group">
                    <label for="employee-name">Name</label>
                    <input type="text" id="employee-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="employee-email">Email</label>
                    <input type="email" id="employee-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="employee-password">Password</label>
                    <input type="password" id="employee-password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="employee-role">Role</label>
                    <select id="employee-role" name="role">
                        <option value="0">Employee</option>
                        <option value="2">Manager</option>
                        <option value="1">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="employee-status">Status</label>
                    <select id="employee-status" name="approval_status">
                        <option value="0">Pending</option>
                        <option value="1">Approved</option>
                        <option value="2">Rejected</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="confirm-btn">Add Employee</button>
                </div>
            </form>
        </div>
    </div>

    <div id="edit-employee-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Employee</h3>
                <button class="close-modal">×</button>
            </div>
            <form id="edit-employee-form" method="POST">
                <input type="hidden" name="action" value="update_employee">
                <input type="hidden" id="edit-employee-id" name="user_id">
                <div class="form-group">
                    <label for="edit-employee-name">Name</label>
                    <input type="text" id="edit-employee-name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="edit-employee-email">Email</label>
                    <input type="email" id="edit-employee-email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="edit-employee-password">Password (leave blank to keep current)</label>
                    <input type="password" id="edit-employee-password" name="password">
                </div>
                <div class="form-group">
                    <label for="edit-employee-role">Role</label>
                    <select id="edit-employee-role" name="role">
                        <option value="0">Employee</option>
                        <option value="2">Manager</option>
                        <option value="1">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit-employee-status">Status</label>
                    <select id="edit-employee-status" name="approval_status">
                        <option value="0">Pending</option>
                        <option value="1">Approved</option>
                        <option value="2">Rejected</option>
                    </select>
                </div>
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="confirm-btn">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <div id="delete-employee-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Employee</h3>
                <button class="close-modal">×</button>
            </div>
            <p>Are you sure you want to delete this employee? This action cannot be undone.</p>
            <form id="delete-employee-form" method="POST">
                <input type="hidden" name="action" value="delete_employee">
                <input type="hidden" id="delete-employee-id" name="user_id">
                <div class="modal-buttons">
                    <button type="button" class="cancel-btn">Cancel</button>
                    <button type="submit" class="confirm-btn">Delete Employee</button>
                </div>
            </form>
        </div>
    </div>

    <script src="view_employees.js"></script>
</body>
</html>
