<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

$sql = "SELECT * FROM categories WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$categories = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>分类管理 - 极简网盘</title>
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
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
            position: relative;
            z-index: 1;
        }
        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 32px;
        }
        .add-form {
            display: flex;
            gap: 12px;
            margin-bottom: 32px;
        }
        .add-form input {
            flex: 1;
            padding: 12px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 15px;
            outline: none;
        }
        .add-form input:focus {
            border-color: var(--text-secondary);
        }
        .add-form button {
            padding: 12px 24px;
            background: var(--text-primary);
            color: var(--bg-primary);
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .add-form button:hover {
            transform: translateY(-1px);
        }
        .category-list {
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
        }
        .category-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 20px;
            border-bottom: 1px solid var(--border);
            transition: all 0.2s ease;
        }
        .category-item:last-child {
            border-bottom: none;
        }
        .category-item:hover {
            background: var(--bg-tertiary);
        }
        .category-name {
            font-size: 15px;
        }
        .category-actions {
            display: flex;
            gap: 8px;
        }
        .action-btn {
            padding: 6px 12px;
            background: var(--bg-tertiary);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text-secondary);
            font-size: 13px;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .action-btn:hover {
            color: var(--text-primary);
            border-color: var(--text-secondary);
        }
        .action-btn.delete:hover {
            background: rgba(239, 68, 68, 0.2);
            border-color: rgba(239, 68, 68, 0.5);
            color: #fca5a5;
        }
        .empty {
            text-align: center;
            padding: 48px;
            color: var(--text-secondary);
        }
        .toast {
            position: fixed;
            bottom: 24px;
            left: 50%;
            transform: translateX(-50%) translateY(100px);
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 14px;
            z-index: 300;
            opacity: 0;
            transition: all 0.3s ease;
        }
        .toast.show {
            transform: translateX(-50%) translateY(0);
            opacity: 1;
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
            <a href="profile.php">修改密码</a>
            <a href="logout.php">登出</a>
        </nav>
    </header>
    
    <div class="container">
        <h1 class="page-title">分类管理</h1>
        
        <form class="add-form" id="addForm">
            <input type="text" name="name" placeholder="输入新分类名称" required>
            <button type="submit">新增分类</button>
        </form>
        
        <div class="category-list" id="categoryList">
            <?php if (empty($categories)): ?>
                <div class="empty">还没有分类，添加第一个吧</div>
            <?php else: ?>
                <?php foreach ($categories as $cat): ?>
                    <div class="category-item" data-id="<?= $cat['id'] ?>">
                        <span class="category-name"><?= htmlspecialchars($cat['name']) ?></span>
                        <div class="category-actions">
                            <button class="action-btn edit">编辑</button>
                            <button class="action-btn delete">删除</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="toast" id="toast"></div>
    
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
        
        const toast = document.getElementById('toast');
        
        function showToast(msg) {
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }
        
        document.getElementById('addForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            try {
                const res = await fetch('api.php?action=add_category', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('添加成功');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.error || '添加失败');
                }
            } catch (e) {
                showToast('添加失败');
            }
        });
        
        document.querySelectorAll('.edit').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                const item = e.target.closest('.category-item');
                const id = item.dataset.id;
                const name = item.querySelector('.category-name').textContent;
                const newName = prompt('修改分类名称:', name);
                if (!newName || newName === name) return;
                
                const formData = new FormData();
                formData.append('id', id);
                formData.append('name', newName);
                
                try {
                    const res = await fetch('api.php?action=update_category', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    if (data.success) {
                        showToast('修改成功');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showToast(data.error || '修改失败');
                    }
                } catch (e) {
                    showToast('修改失败');
                }
            });
        });
        
        document.querySelectorAll('.delete').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                if (!confirm('确定要删除这个分类吗？')) return;
                const item = e.target.closest('.category-item');
                const id = item.dataset.id;
                
                try {
                    const res = await fetch('api.php?action=delete_category&id=' + id);
                    const data = await res.json();
                    if (data.success) {
                        showToast('已删除');
                        setTimeout(() => location.reload(), 800);
                    } else {
                        showToast(data.error || '删除失败');
                    }
                } catch (e) {
                    showToast('删除失败');
                }
            });
        });
    </script>
</body>
</html>