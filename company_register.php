<?php
require_once 'db_connect.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $company_name = $_POST['company_name'];
    $company_email = $_POST['company_email'];
    $company_code = bin2hex(random_bytes(8));
    
    try {
        $conn->beginTransaction();
        
        $sql = "INSERT INTO Company (Name, Company_Email, Company_Code) VALUES (:name, :email, :code)";
        
        $stmt = $conn->prepare($sql);
        $stmt->execute([
            ':name' => $company_name,
            ':email' => $company_email,
            ':code' => $company_code
        ]);
        
        $conn->commit();
      
        
        $_SESSION['company_code'] = $company_code;
        
      
        header("Location: login.php");
        exit();
      
    } catch(PDOException $e) {
        $conn->rollback();
        $error_message = "Registration failed: " . $e->getMessage();
       
    }
}?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Company - Warehouse Management</title>
    <link rel="stylesheet" href="company_register.css">
</head>
<body>
    <div class="company-register-container">
        <h1>Register Your Company</h1>
        <form method="POST" action="">
            <div class="form-group">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" required>
            </div>
            <div class="form-group">
                <label for="company_email">Company Email</label>
                <input type="email" id="company_email" name="company_email" required>
            </div>
            <button type="submit" class="register-btn">Register Company</button>
        </form>
        <div class="user-link">
            <p>Already have a company code? <a href="register.php">Register as user</a></p>
        </div>
    </div>
</body>
</html>

<script src="company_register.js"></script>
