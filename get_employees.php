<?php
require_once 'db_connect.php';
require_once 'session.php'; 

if (isAdmin()) { 
    $company_code = $_GET['company_code'];
    
    $stmt = $conn->prepare("SELECT * FROM Users WHERE Company_Code = :code");
    $stmt->execute([':code' => $company_code]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($employees);
}
