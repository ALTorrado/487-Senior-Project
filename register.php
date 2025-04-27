<?php
require_once 'db_connect.php';
session_start();

$error_message = '';
$success_message = '';


try {
    $tableInfoStmt = $conn->query("DESCRIBE Users");
    $tableColumns = $tableInfoStmt->fetchAll(PDO::FETCH_COLUMN);
    
    
    $companyCodeColumn = null;
    foreach ($tableColumns as $column) {
        if (strtolower($column) === 'company_code') {
            $companyCodeColumn = 'Company_Code';
            break;
        } else if (strtolower($column) === 'companycode') {
            $companyCodeColumn = 'CompanyCode';
            break;
        } else if (strtolower($column) === 'company_id') {
            $companyCodeColumn = 'Company_Id';
            break;
        }
    }
    
    if (!$companyCodeColumn) {
        $error_message = "Database schema issue: Company code column not found in Users table. Please contact the administrator.";
    }
} catch(PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$error_message) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $company_code = $_POST['company_code'];
    
    
    $stmt = $conn->prepare("SELECT * FROM Company WHERE Company_Code = :code");
    $stmt->execute([':code' => $company_code]);
    
    if ($stmt->rowCount() == 0) {
        $error_message = "Invalid company code. Please try again.";
    } else {
        
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $conn->beginTransaction();
            
           
            if ($companyCodeColumn) {
                $sql = "INSERT INTO Users (Name, Email, Password, Role, $companyCodeColumn, Approval_Status) 
                        VALUES (:name, :email, :password, 0, :company_code, 0)";
                
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashed_password,
                    ':company_code' => $company_code
                ]);
                
                $conn->commit();
                
                $success_message = "Registration successful! Your account is pending approval by the administrator.";
            }
            
        } catch(PDOException $e) {
            $conn->rollback();
            $error_message = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Warehouse Management</title>
    <link rel="stylesheet" href="register.css">
</head>
<body>
    <div class="register-container">
        <h1>Register as Employee</h1>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="success-message"><?= htmlspecialchars($success_message) ?></div>
        <?php else: ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="company_code">Company Code</label>
                    <input type="text" id="company_code" name="company_code" required>
                </div>
                <button type="submit" class="register-btn">Register</button>
            </form>
        <?php endif; ?>
        
        <div class="login-link">
            <p>Already have an account? <a href="login.php">Login</a></p>
        </div>
        <div class="company-link">
            <p>Need to register a company? <a href="company_register.php">Register Company</a></p>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const password = document.getElementById('password').value;
                const companyCode = document.getElementById('company_code').value;
                
                if (password.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long');
                }
                
                if (companyCode.trim() === '') {
                    e.preventDefault();
                    alert('Company code is required');
                }
            });
        }
    });
    </script>
</body>
</html>
