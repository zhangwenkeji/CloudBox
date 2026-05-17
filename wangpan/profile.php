<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$user = getCurrentUser();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = '请填写所有字段';
    } elseif (strlen($newPassword) < 6) {
        $error = '新密码至少需要6个字符';
    } elseif ($newPassword !== $confirmPassword) {
        $error = '两次密码输入不一致';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
        $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
        $result = $stmt->execute();
        $userData = $result->fetchArray(SQLITE3_ASSOC);
        
        if (!password_verify($oldPassword, $userData['password'])) {
            $error = '原密码错误';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindValue(':password', $hashedPassword, SQLITE3_TEXT);
            $stmt->bindValue(':id', $_SESSION['user_id'], SQLITE3_INTEGER);
            $stmt->execute();
            
            $success = '密码修改成功';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>修改密码 - 极简网盘</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;600&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-primary: #0a0a0b;
            --bg-secondary: #141416;
            --bg-tertiary: #1c1c1f;
            --text-primary: #fafafa;
            --text-secondary: #a1a1aa;
            --accent: #e4e4e7;
            --accent-hover: #ffffff;
            --border: #27272a;
        }
        [data-theme="light"] {
            --bg-primary: #fafafa;
            --bg-secondary: #ffffff;
            --bg-tertiary: #f5f5f5;
            --text-primary: #18181b;
            --text-secondary: #71717a;
            --accent: #18181b;
            --accent-hover: #000000;
            --border: #e4e4e7;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans SC', 'Space Grotesk', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
        }
        [data-theme="light"] body {
            background: var(--bg-primary);
        }
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 0;
        }
        [data-theme="light"] .bg-grid {
            background-image: linear-gradient(rgba(0,0,0,0.05) 1px, transparent 1px), linear-gradient(90deg, rgba(0,0,0,0.05) 1px, transparent 1px);
        }
        }
        .header {
            position: sticky;
            top: 0;
            z-index: 100;
            background: var(--bg-secondary);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border);
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 22px;
            font-weight: 600;
        }
        .logo span { color: var(--text-secondary); font-weight: 400; }
        .nav { display: flex; gap: 16px; align-items: center; }
        .nav a, .nav button {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 14px;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            background: none;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .nav a:hover, .nav button:hover {
            background: var(--bg-tertiary);
            color: var(--text-primary);
        }
        .theme-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .theme-toggle svg {
            fill: none;
            stroke: var(--text-secondary);
        }
        [data-theme="light"] .header { background: rgba(255,255,255,0.8); }
        [data-theme="dark"] .header { background: rgba(10,10,11,0.8); }
        [data-theme="light"] .sun-icon { display: block; }
        [data-theme="light"] .moon-icon { display: none; }
        [data-theme="dark"] .sun-icon { display: none; }
        [data-theme="dark"] .moon-icon { display: block; }
        .sun-icon, .moon-icon { visibility: visible; }
        .container {
            max-width: 500px;
            margin: 0 auto;
            padding: 48px 24px;
            position: relative;
            z-index: 1;
        }
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .username-display {
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 32px;
        }
        .form-card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group:last-of-type { margin-bottom: 24px; }
        .form-group label {
            display: block;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 15px;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-group input:focus {
            border-color: var(--text-secondary);
            background: var(--bg-secondary);
        }
        .btn {
            width: 100%;
            padding: 14px 24px;
            background: var(--text-primary);
            color: var(--bg-primary);
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(255,255,255,0.15);
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 24px;
        }
        .success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 14px;
            margin-bottom: 24px;
        }
    </style>
</head>
<body data-theme="light">
    <div class="bg-grid"></div>
    
    <header class="header" data-theme="light">
        <div class="logo">Cloud<span>Box</span></div>
        <nav class="nav">
            <button class="theme-toggle" id="themeToggle" title="切换主题">
                <svg class="sun-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2">
                    <circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M1 12h2M21 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/>
                </svg>
                <svg class="moon-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#a1a1aa" stroke-width="2">
                    <path d="M21 12.79A9 9 0 1111.21 3 7 7 0 0021 12.79z"/>
                </svg>
            </button>
            <a href="index.php">返回图床</a>
            <a href="category.php">分类管理</a>
            <a href="logout.php">登出</a>
        </nav>
    </header>
    
    <div class="container">
        <h1 class="page-title">修改密码</h1>
        <p class="username-display">当前用户：<?= htmlspecialchars($user['username']) ?></p>
        
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST" action="">
                <div class="form-group">
                    <label>原密码</label>
                    <input type="password" name="old_password" placeholder="请输入原密码" required>
                </div>
                <div class="form-group">
                    <label>新密码</label>
                    <input type="password" name="new_password" placeholder="请输入新密码（至少6位）" required>
                </div>
                <div class="form-group">
                    <label>确认新密码</label>
                    <input type="password" name="confirm_password" placeholder="再次输入新密码" required>
                </div>
                <button type="submit" class="btn">保存修改</button>
            </form>
        </div>
    </div>
    
    <script>
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        
        const savedTheme = localStorage.getItem('theme') || 'dark';
        body.setAttribute('data-theme', savedTheme);
        
        themeToggle.addEventListener('click', () => {
            const current = body.getAttribute('data-theme');
            const next = current === 'light' ? 'dark' : 'light';
            body.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });
    </script>
</body>
</html>