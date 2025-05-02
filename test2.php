<?php
session_start();
require '/home/altorrad/public_html/connect.php';

try {
    $stmt = $conn->query("SELECT 1");
    echo "Database connection successful!";
} catch(PDOException $e) {
    echo "Testing connection...";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Test Page</title>
</head>
<body>
    <h1>Test Page Working</h1>
    <p>If you can see this, the PHP file is working correctly.</p>
</body>
</html>
