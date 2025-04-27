<?php
require_once 'db_connect.php';
session_start();

// Security check - ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_GET['category_id']) || empty($_GET['category_id'])) {
    echo json_encode([]);
    exit;
}

$category_id = $_GET['category_id'];
$company_code = $_SESSION['company_code'];

try {
    $stmt = $conn->prepare("
        SELECT * FROM Product 
        WHERE Category_Category_Id = :category_id 
        AND Company_Code = :company_code 
        AND Stock_Quantity > 0
        ORDER BY Name
    ");
    
    $stmt->execute([
        ':category_id' => $category_id,
        ':company_code' => $company_code
    ]);
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($products);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}