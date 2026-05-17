<?php
require_once __DIR__ . '/functions.php';
requireLogin();

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add_category') {
        $name = trim($_POST['name'] ?? '');
        if (empty($name)) {
            $response['error'] = '请输入分类名称';
        } else {
            $db = getDB();
            $stmt = $db->prepare("INSERT INTO categories (user_id, name) VALUES (:user_id, :name)");
            $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->execute();
            $response['success'] = true;
        }
    }
    
    if ($action === 'update_category') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        
        if (empty($id) || empty($name)) {
            $response['error'] = '参数错误';
        } else {
            $db = getDB();
            $stmt = $db->prepare("UPDATE categories SET name = :name WHERE id = :id AND user_id = :user_id");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->execute();
            $response['success'] = true;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($action === 'delete_category') {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $response['error'] = '参数错误';
        } else {
            $db = getDB();
            $stmt = $db->prepare("DELETE FROM categories WHERE id = :id AND user_id = :user_id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->execute();
            $response['success'] = true;
        }
    }
    
    if ($action === 'delete_image') {
        $id = $_GET['id'] ?? '';
        
        if (empty($id)) {
            $response['error'] = '参数错误';
        } else {
            $db = getDB();
            $stmt = $db->prepare("SELECT * FROM images WHERE id = :id AND user_id = :user_id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $result = $stmt->execute();
            $image = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($image) {
                $config = require __DIR__ . '/config.php';
                $filePath = $config['storage_path'] . '/' . $image['user_id'] . '/' . $image['category_id'] . '/' . $image['filename'];
                
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                
                $stmt = $db->prepare("DELETE FROM images WHERE id = :id AND user_id = :user_id");
                $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
                $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
                $stmt->execute();
                $response['success'] = true;
            } else {
                $response['error'] = '图片不存在';
            }
        }
    }
}

echo json_encode($response);