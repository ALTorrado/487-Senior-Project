<?php
session_start();
require_once 'db_connect.php';

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM Users WHERE Email = :email");
    $stmt->execute([':email' => $email]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['Password'])) {
            // Check approval status
            if ($user['Approval_Status'] == 0) {
                $error_message = "Your account is pending approval. Please check back later.";
            } else if ($user['Approval_Status'] == 2) {
                $error_message = "Your account has been rejected. Please contact support.";
            } else {
                // Approved user - set session and redirect
                $_SESSION['user_id'] = $user['User_Id'];
                $_SESSION['name'] = $user['Name'];
                $_SESSION['email'] = $user['Email'];
                $_SESSION['role'] = $user['Role'];
                $_SESSION['company_code'] = $user['Company_Code'];
                
                // Redirect based on role
                if ($user['Role'] == 1) { // Admin
                    header("Location: admin_dashboard.php");
                } else { // Regular user
                    header("Location: First.php");
                }
                exit();
            }
        } else {
            $error_message = "Invalid email or password";
        }
    } else {
        $error_message = "Invalid email or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Warehouse Management</title>
    <link rel="stylesheet" href="login.css">
</head>
<body>
    <div class="login-container">
        <h1>Login</h1>
        
        <?php if ($error_message): ?>
            <div class="error-message"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="login-btn">Login</button>
        </form>
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register</a></p>
        </div>
    </div>
</body>
</html>
