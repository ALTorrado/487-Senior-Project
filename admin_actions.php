<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'db_connect.php';
require_once 'session.php';

requireAdmin();

$isSuperAdmin = empty($_SESSION['company_code']);
$userCompanyCode = $_SESSION['company_code'];

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $response = ['success' => false, 'message' => ''];
    
    if (in_array($action, ['delete_company', 'add_company', 'edit_company', 'update_company_status']) && !$isSuperAdmin) {
        $response['message'] = "Access denied: Only super administrators can perform this action.";
        echo json_encode($response);
        exit;
    }
    
    if (in_array($action, ['update_employee_status', 'delete_employee']) && !$isSuperAdmin) {
        $userId = isset($_POST['user_id']) ? $_POST['user_id'] : '';
        
        if ($userId) {
            $stmt = $conn->prepare("SELECT Company_Code FROM Users WHERE User_Id = :user_id");
            $stmt->execute([':user_id' => $userId]);
            $employeeCompany = $stmt->fetchColumn();
            
            if ($employeeCompany !== $userCompanyCode && $employeeCompany !== null) {
                $response['message'] = "Access denied: You can only manage employees from your company.";
                echo json_encode($response);
                exit;
            }
        }
    }
    
    switch($action) {
        case 'delete_company':
            $code = $_POST['code'];
            try {
                $conn->beginTransaction();
                
                $userStmt = $conn->prepare("SELECT User_Id FROM Users WHERE Company_Code = :code");
                $userStmt->execute([':code' => $code]);
                $userIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($userIds)) {
                    $userIdsStr = implode(',', $userIds);
                    
                    $orderStmt = $conn->prepare("DELETE FROM Order_Products WHERE Orders_Order_Id IN 
                                                (SELECT Order_Id FROM Orders WHERE Users_User_Id IN ($userIdsStr))");
                    $orderStmt->execute();
                    
                    $orderStmt = $conn->prepare("DELETE FROM Orders WHERE Users_User_Id IN ($userIdsStr)");
                    $orderStmt->execute();
                    
                    $userStmt = $conn->prepare("DELETE FROM Users WHERE Company_Code = :code");
                    $userStmt->execute([':code' => $code]);
                }
                
                $stmt = $conn->prepare("DELETE FROM Company WHERE Company_Code = :code");
                $stmt->execute([':code' => $code]);
                
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Company deleted successfully!';
            } catch (PDOException $e) {
                $conn->rollback();
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
            
        case 'delete_employee':
            $userId = $_POST['user_id'];
            try {
                $conn->beginTransaction();
                
                $orderStmt = $conn->prepare("DELETE FROM Order_Products WHERE Orders_Order_Id IN 
                                                (SELECT Order_Id FROM Orders WHERE Users_User_Id = :user_id)");
                $orderStmt->execute([':user_id' => $userId]);
                
                $orderStmt = $conn->prepare("DELETE FROM Orders WHERE Users_User_Id = :user_id");
                $orderStmt->execute([':user_id' => $userId]);
                
                $stmt = $conn->prepare("DELETE FROM Users WHERE User_Id = :id");
                $stmt->execute([':id' => $userId]);
                
                $conn->commit();
                $response['success'] = true;
                $response['message'] = 'Employee deleted successfully!';
            } catch (PDOException $e) {
                $conn->rollback();
                $response['message'] = "Error deleting employee: " . $e->getMessage();
            }
            break;
    }
    
    echo json_encode($response);
    exit;
}


echo json_encode(['success' => false, 'message' => 'Invalid request']);
