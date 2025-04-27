<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connect.php';
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $response = ['success' => false, 'message' => ''];
    
    switch($action) {
        case 'delete_company':
            $code = $_POST['code'];
            try {
                $stmt = $conn->prepare("DELETE FROM Company WHERE Company_Code = :code");
                $stmt->execute([':code' => $code]);
                
                $response['success'] = true;
                $response['message'] = 'Company deleted successfully!';
            } catch (PDOException $e) {
                $response['message'] = "Error deleting company: " . $e->getMessage();
            }
            break;
            
        case 'add_company':
            $name = $_POST['name'];
            $email = $_POST['email'];
            $code = bin2hex(random_bytes(8));
            $status = isset($_POST['status']) ? $_POST['status'] : 0;
            
            try {
                $stmt = $conn->prepare("INSERT INTO Company (Name, Company_Email, Company_Code, Status) 
                               VALUES (:name, :email, :code, :status)");
                $stmt->execute([
                    ':name' => $name, 
                    ':email' => $email, 
                    ':code' => $code,
                    ':status' => $status
                ]);
                
                $response['success'] = true;
                $response['message'] = 'Company added successfully!';
                $response['company'] = [
                    'name' => $name,
                    'email' => $email,
                    'code' => $code,
                    'status' => $status
                ];
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && 
                    strpos($e->getMessage(), 'Company_Email') !== false) {
                    $response['message'] = "A company with this email already exists.";
                } else {
                    $response['message'] = "Error adding company: " . $e->getMessage();
                }
            }
            break;
            
        case 'edit_company':
            $code = $_POST['code'];
            $name = $_POST['name'];
            $email = $_POST['email'];
            
            try {
                $stmt = $conn->prepare("UPDATE Company SET Name = :name, Company_Email = :email WHERE Company_Code = :code");
                $result = $stmt->execute([':name' => $name, ':email' => $email, ':code' => $code]);
                
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Company updated successfully!';
                } else {
                    $response['message'] = "Failed to update company.";
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000 && strpos($e->getMessage(), 'Duplicate entry') !== false && 
                    strpos($e->getMessage(), 'Company_Email') !== false) {
                    $response['message'] = "A company with this email already exists.";
                } else {
                    $response['message'] = "Error updating company: " . $e->getMessage();
                }
            }
            break;
            
        case 'update_company_status':
            $code = isset($_POST['code']) ? $_POST['code'] : '';
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 0;
            
            if (empty($code)) {
                $response['message'] = "Company code is required";
                break;
            }
            
            try {
              
                $checkStmt = $conn->prepare("SELECT COUNT(*) FROM Company WHERE Company_Code = :code");
                $checkStmt->execute([':code' => $code]);
                $companyExists = (int)$checkStmt->fetchColumn();
                
                if ($companyExists === 0) {
                    $response['message'] = "Company not found";
                    break;
                }
                
               
                $stmt = $conn->prepare("UPDATE Company SET Status = :status WHERE Company_Code = :code");
                $result = $stmt->execute([':status' => $status, ':code' => $code]);
                
                if ($result) {
                    $response['success'] = true;
                    $response['message'] = 'Company status updated successfully!';
                } else {
                    $response['message'] = "Failed to update company status.";
                }
            } catch (PDOException $e) {
                $response['message'] = "Error updating company status: " . $e->getMessage();
            }
            break;
            
        case 'update_employee_status':
            $userId = $_POST['user_id'];
            $status = $_POST['status'];
            
            try {
                $stmt = $conn->prepare("UPDATE Users SET Approval_Status = :status WHERE User_Id = :id");
                $stmt->execute([':status' => $status, ':id' => $userId]);
                
                $response['success'] = true;
                $response['message'] = 'Employee status updated successfully!';
            } catch (PDOException $e) {
                $response['message'] = "Error updating employee status: " . $e->getMessage();
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}


echo json_encode(['success' => false, 'message' => 'Invalid request']);
