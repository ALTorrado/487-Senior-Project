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
    
      if (strlen($name) > 40) {
          $error_message = "Name cannot exceed 40 characters.";
      } else if (strlen($email) > 40) {
          $error_message = "Email cannot exceed 40 characters.";
      } else if (strlen($password) < 6 || strlen($password) > 40) {
          $error_message = "Password must be between 6 and 40 characters.";
      } else {
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
}?>

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
                    <input type="text" id="name" name="name" maxlength="40" required>
                    <small class="char-count">0/40 characters</small>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" maxlength="40" required>
                    <small class="char-count">0/40 characters</small>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="6" maxlength="40" required>
                    <small class="char-count">0/40 characters</small>
                </div>
                <div class="form-group">
                    <label for="company_code">Company Code</label>
                    <input type="text" id="company_code" name="company_code" required>
                </div>                <button type="submit" class="register-btn">Register</button>
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
        const inputs = document.querySelectorAll('input[maxlength]');
        
        inputs.forEach(input => {
            const counter = input.nextElementSibling;
            if (counter && counter.classList.contains('char-count')) {
                const maxLength = input.getAttribute('maxlength');
                
                counter.textContent = `${input.value.length}/${maxLength} characters`;
                
                input.addEventListener('input', function() {
                    counter.textContent = `${this.value.length}/${maxLength} characters`;
                    
                    if (this.value.length > maxLength * 0.8) {
                        counter.style.color = '#e74c3c';
                    } else {
                        counter.style.color = '';
                    }
                });
            }
        });
        
        const passwordField = document.getElementById('password');
        if (passwordField) {
            const counter = passwordField.nextElementSibling;
            
            passwordField.addEventListener('input', function() {
                const length = this.value.length;
                const maxLength = this.getAttribute('maxlength');
                
                counter.textContent = `${length}/${maxLength} characters`;
                
                if (length < 6) {
                    counter.style.color = '#e74c3c';
                } else if (length < 10) {
                    counter.style.color = '#f39c12'; 
                } else {
                    counter.style.color = '#27ae60'; 
                }
            });
        }
    });    </script></body>
</html>
