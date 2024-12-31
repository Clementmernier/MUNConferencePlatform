<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);

try {
    if (!isset($_POST['user_id'])) {
        throw new Exception('User ID is required');
    }

    $userId = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    if (!$userId) {
        throw new Exception('Invalid user ID');
    }

    // Prevent modification of user ID 1
    if ($userId == 1 && isset($_POST['role']) && $_POST['role'] !== 'admin') {
        throw new Exception('Cannot modify primary administrator role');
    }

    // Prepare update fields
    $updateFields = [];
    $params = [];

    // Firstname
    if (isset($_POST['firstname']) && !empty($_POST['firstname'])) {
        $updateFields[] = 'firstname = :firstname';
        $params[':firstname'] = $_POST['firstname'];
    }

    // Lastname
    if (isset($_POST['lastname']) && !empty($_POST['lastname'])) {
        $updateFields[] = 'lastname = :lastname';
        $params[':lastname'] = $_POST['lastname'];
    }

    // Email
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        if (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        // Check if email exists for another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$_POST['email'], $userId]);
        if ($stmt->fetch()) {
            throw new Exception('Email already exists');
        }
        $updateFields[] = 'email = :email';
        $params[':email'] = $_POST['email'];
    }

    // Role
    if (isset($_POST['role'])) {
        $allowedRoles = ['delegate', 'chair', 'admin'];
        if (!in_array($_POST['role'], $allowedRoles)) {
            throw new Exception('Invalid role');
        }
        $updateFields[] = 'role = :role';
        $params[':role'] = $_POST['role'];
    }

    // Country
    if (isset($_POST['country_code'])) {
        if (!empty($_POST['country_code'])) {
            $stmt = $pdo->prepare("SELECT code FROM countries WHERE code = ?");
            $stmt->execute([$_POST['country_code']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid country');
            }
        }
        $updateFields[] = 'country_code = :country_code';
        $params[':country_code'] = empty($_POST['country_code']) ? null : $_POST['country_code'];
    }

    // Committee
    if (isset($_POST['committee_id'])) {
        if (!empty($_POST['committee_id'])) {
            $stmt = $pdo->prepare("SELECT id FROM committees WHERE id = ?");
            $stmt->execute([$_POST['committee_id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Invalid committee');
            }
        }
        $updateFields[] = 'committee_id = :committee_id';
        $params[':committee_id'] = empty($_POST['committee_id']) ? null : $_POST['committee_id'];
    }

    if (empty($updateFields)) {
        throw new Exception('No data to update');
    }

    // Build and execute query
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = :id";
    $params[':id'] = $userId;

    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($params);

    if ($result) {
        echo json_encode(['success' => true, 'message' => 'User updated successfully']);
    } else {
        throw new Exception('Failed to update user');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
