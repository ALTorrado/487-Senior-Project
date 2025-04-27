<?php
require_once 'db_connect.php';
session_start();

if (isset($_SESSION['role']) && $_SESSION['role'] == 1) {
    $company_code = $_GET['company_code'];
    
    $stmt = $conn->prepare("SELECT * FROM Users WHERE Company_Code = :code");
    $stmt->execute([':code' => $company_code]);
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    header('Content-Type: application/json');
    echo json_encode($employees);
}
