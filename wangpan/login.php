<?php
require_once __DIR__ . '/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '请填写用户名和密码';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, password FROM users WHERE username = :username");
        $stmt->bindValue(':username', $username, SQLITE3_TEXT);
        $result = $stmt->execute();
        $user = $result->fetchArray(SQLITE3_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $username;
            header('Location: /index.php');
            exit;
        } else {
            $error = '用户名或密码错误';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 极简网盘</title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Noto Sans SC', 'Space Grotesk', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bg-grid {
            position: fixed;
            inset: 0;
            background-image: linear-gradient(rgba(255,255,255,0.02) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
        }
        .bg-gradient {
            position: fixed;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            pointer-events: none;
        }
        .card {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 24px;
            padding: 48px;
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 1;
            animation: fadeIn 0.8s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .logo {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 28px;
            font-weight: 600;
            text-align: center;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }
        .logo span { color: var(--text-secondary); font-weight: 400; }
        .subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 14px;
            margin-bottom: 40px;
        }
        .form-group { margin-bottom: 24px; }
        .form-group label {
            display: block;
            font-size: 13px;
            color: var(--text-secondary);
            margin-bottom: 8px;
            font-weight: 400;
        }
        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 12px;
            color: var(--text-primary);
            font-size: 15px;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-group input:focus {
            border-color: var(--text-secondary);
            background: var(--bg-secondary);
        }
        .form-group input::placeholder {
            color: var(--text-secondary);
            opacity: 0.6;
        }
        .btn {
            width: 100%;
            padding: 14px 24px;
            background: var(--text-primary);
            color: var(--bg-primary);
            border: none;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            font-family: inherit;
        }
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 24px rgba(255,255,255,0.15);
        }
        .btn:active { transform: translateY(0); }
        .link {
            display: block;
            text-align: center;
            margin-top: 24px;
            font-size: 14px;
            color: var(--text-secondary);
        }
        .link a {
            color: var(--text-primary);
            text-decoration: none;
            border-bottom: 1px solid var(--text-secondary);
            padding-bottom: 2px;
            transition: all 0.2s ease;
        }
        .link a:hover {
            color: var(--accent-hover);
            border-color: var(--accent-hover);
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
    </style>
</head>
<body>
    <div class="bg-grid"></div>
    <div class="bg-gradient"></div>
    <div class="card">
        <div class="logo">Cloud<span>Box</span></div>
        <p class="subtitle">极简云端图床</p>
        <?php if (!empty($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" action="">
            <div class="form-group">
                <label>用户名</label>
                <input type="text" name="username" placeholder="请输入用户名" required>
            </div>
            <div class="form-group">
                <label>密码</label>
                <input type="password" name="password" placeholder="请输入密码" required>
            </div>
            <button type="submit" class="btn">登录</button>
        </form>
        <p class="link">没有账号？<a href="register.php">立即注册</a></p>
    </div>
</body>
</html>