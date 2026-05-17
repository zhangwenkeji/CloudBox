<?php
require_once __DIR__ . '/functions.php';
requireLogin();

header('Content-Type: application/json');
$response = ['success' => false, 'error' => ''];

$config = require __DIR__ . '/config.php';

if (empty($_FILES['image']['name'])) {
    $response['error'] = '请选择图片文件';
    echo json_encode($response);
    exit;
}

$file = $_FILES['image'];
$originalName = $file['name'];
$tmpName = $file['tmp_name'];
$error = $file['error'];
$size = $file['size'];

if ($error !== UPLOAD_ERR_OK) {
    $response['error'] = '文件上传错误';
    echo json_encode($response);
    exit;
}

if ($size > $config['max_size']) {
    $response['error'] = '文件大小超过限制';
    echo json_encode($response);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($tmpName);

if (!in_array($mimeType, $config['allowed_types'])) {
    $response['error'] = '不支持的文件类型';
    echo json_encode($response);
    exit;
}

$ext = pathinfo($originalName, PATHINFO_EXTENSION);
$ext = strtolower($ext);
if (!in_array($ext, $config['allowed_exts'])) {
    $response['error'] = '不支持的文件扩展名';
    echo json_encode($response);
    exit;
}

$category_id = $_POST['category_id'] ?? null;
if (!$category_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM categories WHERE user_id = :user_id ORDER BY created_at ASC LIMIT 1");
    $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
    $result = $stmt->execute();
    $firstCat = $result->fetchArray(SQLITE3_ASSOC);
    
    if ($firstCat) {
        $category_id = $firstCat['id'];
    } else {
        $stmt = $db->prepare("INSERT INTO categories (user_id, name) VALUES (:user_id, :name)");
        $stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $stmt->bindValue(':name', '默认分类', SQLITE3_TEXT);
        $stmt->execute();
        $category_id = $db->lastInsertRowID();
    }
}

$dir = $config['storage_path'] . '/' . $_SESSION['user_id'] . '/' . $category_id;
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$filename = uniqid() . '.' . $ext;
$targetPath = $dir . '/' . $filename;

if (!move_uploaded_file($tmpName, $targetPath)) {
    $response['error'] = '文件保存失败';
    echo json_encode($response);
    exit;
}

$db = getDB();
$stmt = $db->prepare("INSERT INTO images (user_id, category_id, filename, original_name) VALUES (:user_id, :category_id, :filename, :original_name)");
$stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
$stmt->bindValue(':filename', $filename, SQLITE3_TEXT);
$stmt->bindValue(':original_name', $originalName, SQLITE3_TEXT);
$stmt->execute();

$response['success'] = true;
$response['filename'] = $filename;

echo json_encode($response);