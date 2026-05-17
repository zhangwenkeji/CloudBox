<?php
require_once __DIR__ . '/functions.php';
requireLogin();

$user = getCurrentUser();
$db = getDB();

$category_id = $_GET['category'] ?? null;

$sql = "SELECT * FROM categories WHERE user_id = :user_id ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
$result = $stmt->execute();
$categories = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $categories[] = $row;
}

$sql = "SELECT * FROM images WHERE user_id = :user_id";
if ($category_id) {
    $sql .= " AND category_id = :category_id";
}
$sql .= " ORDER BY created_at DESC";
$stmt = $db->prepare($sql);
$stmt->bindValue(':user_id', $_SESSION['user_id'], SQLITE3_INTEGER);
if ($category_id) {
    $stmt->bindValue(':category_id', $category_id, SQLITE3_INTEGER);
}
$result = $stmt->execute();
$images = [];
while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $images[] = $row;
}

$base_url = getBaseUrl();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的图床 - 极简网盘</title>
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
        .nav .active {
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
        [data-theme="light"] .header {
            background: rgba(255, 255, 255, 0.8);
            border-color: var(--border);
        }
        [data-theme="dark"] .header {
            background: rgba(10, 10, 11, 0.8);
            border-color: var(--border);
        }
        [data-theme="light"] .sun-icon { display: block; }
        [data-theme="light"] .moon-icon { display: none; }
        [data-theme="dark"] .sun-icon { display: none; }
        [data-theme="dark"] .moon-icon { display: block; }
        .sun-icon, .moon-icon { visibility: visible; }
        [data-theme="light"] .bg-gradient {
            background: radial-gradient(circle, rgba(0,0,0,0.03) 0%, transparent 70%);
        }
        [data-theme="dark"] .bg-gradient {
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, transparent 70%);
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 24px;
            position: relative;
            z-index: 1;
        }
        .filters {
            display: flex;
            gap: 12px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }
        .filter-tag {
            padding: 8px 16px;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            border-radius: 20px;
            font-size: 13px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .filter-tag:hover {
            border-color: var(--text-secondary);
            color: var(--text-primary);
        }
        .filter-tag.active {
            background: var(--text-primary);
            color: var(--bg-primary);
            border-color: var(--text-primary);
        }
        .upload-zone {
            background: var(--bg-secondary);
            border: 2px dashed var(--border);
            border-radius: 16px;
            padding: 48px;
            text-align: center;
            margin-bottom: 32px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--text-secondary);
            background: var(--bg-tertiary);
        }
        .upload-zone input {
            display: none;
        }
        .upload-icon {
            width: 48px;
            height: 48px;
            margin: 0 auto 16px;
            opacity: 0.6;
        }
        .upload-text {
            font-size: 15px;
            color: var(--text-secondary);
            margin-bottom: 8px;
        }
        .upload-hint {
            font-size: 13px;
            color: var(--text-secondary);
            opacity: 0.6;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 16px;
        }
        .gallery-item {
            position: relative;
            aspect-ratio: 1;
            border-radius: 12px;
            overflow: hidden;
            background: var(--bg-secondary);
            border: 1px solid var(--border);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .gallery-item:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
        }
        .gallery-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            background: var(--bg-tertiary);
        }
        .gallery-item .overlay {
            position: absolute;
            inset: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            opacity: 0;
            transition: all 0.2s ease;
        }
        .gallery-item:hover .overlay {
            opacity: 1;
        }
        .overlay-btn {
            padding: 10px 16px;
            background: var(--text-primary);
            color: var(--bg-primary);
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .overlay-btn:hover {
            transform: scale(1.05);
        }
        .overlay-btn.delete {
            background: rgba(239, 68, 68, 0.9);
            color: white;
        }
        .empty {
            text-align: center;
            padding: 80px 20px;
            color: var(--text-secondary);
        }
        .empty-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 24px;
            opacity: 0.3;
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
            <a href="category.php">分类管理</a>
            <a href="profile.php">修改密码</a>
            <a href="logout.php">登出</a>
        </nav>
    </header>
    
    <div class="container">
        <div class="filters">
            <a href="index.php" class="filter-tag <?= !$category_id ? 'active' : '' ?>">全部</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?category=<?= $cat['id'] ?>" class="filter-tag <?= $category_id == $cat['id'] ? 'active' : '' ?>"><?= htmlspecialchars($cat['name']) ?></a>
            <?php endforeach; ?>
        </div>
        
        <div class="upload-zone" id="uploadZone">
            <input type="file" id="fileInput" accept="image/*" multiple>
            <input type="file" id="pasteInput" accept="image/*">
            <svg class="upload-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M4 16v4a2 2 0 002 2h12a2 2 0 002-2v-4M12 4v12M8 8l4-4 4 4"/>
            </svg>
            <p class="upload-text">点击上传图片 或 粘贴截图</p>
            <p class="upload-hint">支持 Ctrl+V 粘贴上传 / 拖拽上传</p>
        </div>
        
        <?php if (empty($images)): ?>
            <div class="empty">
                <svg class="empty-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                    <circle cx="8.5" cy="8.5" r="1.5"/>
                    <path d="M21 15l-5-5L5 21"/>
                </svg>
                <p>还没有图片，上传第一张吧</p>
            </div>
        <?php else: ?>
            <div class="gallery">
                <?php foreach ($images as $img): ?>
                    <div class="gallery-item" data-id="<?= $img['id'] ?>" data-src="<?= $base_url ?>/storage/images/<?= $img['user_id'] ?>/<?= $img['category_id'] ?>/<?= $img['filename'] ?>">
                        <img src="<?= $base_url ?>/storage/images/<?= $img['user_id'] ?>/<?= $img['category_id'] ?>/<?= $img['filename'] ?>" alt="">
                        <div class="overlay">
                            <button class="overlay-btn copy-link" data-link="<?= $base_url ?>/storage/images/<?= $img['user_id'] ?>/<?= $img['category_id'] ?>/<?= $img['filename'] ?>">复制链接</button>
                            <button class="overlay-btn delete" data-id="<?= $img['id'] ?>">删除</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
            
            document.querySelectorAll('.gallery-item').forEach(item => {
                item.style.boxShadow = next === 'light' ? '0 12px 40px rgba(0,0,0,0.15)' : '';
            });
        });
        
        const uploadZone = document.getElementById('uploadZone');
        const fileInput = document.getElementById('fileInput');
        const pasteInput = document.getElementById('pasteInput');
        const toast = document.getElementById('toast');
        function showToast(msg) {
            toast.textContent = msg;
            toast.classList.add('show');
            setTimeout(() => toast.classList.remove('show'), 2500);
        }
        
        uploadZone.addEventListener('click', () => fileInput.click());
        
        uploadZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadZone.classList.add('dragover');
        });
        
        uploadZone.addEventListener('dragleave', () => {
            uploadZone.classList.remove('dragover');
        });
        
        uploadZone.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadZone.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length) handleFiles(files);
        });
        
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) handleFiles(fileInput.files);
        });
        
        document.addEventListener('paste', (e) => {
            const items = e.clipboardData?.items;
            if (items) {
                for (let item of items) {
                    if (item.type.indexOf('image') !== -1) {
                        const file = item.getAsFile();
                        if (file) handleFiles([file]);
                    }
                }
            }
        });
        
        async function handleFiles(files) {
            for (let file of files) {
                if (!file.type.startsWith('image/')) {
                    showToast('只支持图片文件');
                    continue;
                }
                await uploadFile(file);
            }
        }
        
        async function uploadFile(file) {
            const formData = new FormData();
            formData.append('image', file);
            <?php if ($category_id): ?>
            formData.append('category_id', '<?= $category_id ?>');
            <?php endif; ?>
            
            try {
                const res = await fetch('upload.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data.success) {
                    showToast('上传成功');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.error || '上传失败');
                }
            } catch (e) {
                showToast('上传失败');
            }
        }
        
        document.querySelectorAll('.copy-link').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const link = btn.dataset.link;
                copyText(link);
            });
        });

        function copyText(text) {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.left = '-9999px';
            ta.style.top = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                showToast('链接已复制');
            } catch (e) {
                showToast('复制失败，请手动复制');
            }
            document.body.removeChild(ta);
        }
        
        document.querySelectorAll('.gallery-item').forEach(item => {
            item.addEventListener('click', (e) => {
                if (e.target.classList.contains('delete') || e.target.classList.contains('copy-link')) return;
                window.open(item.dataset.src, '_blank');
            });
        });
        
        document.querySelectorAll('.delete').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                if (!confirm('确定要删除这张图片吗？')) return;
                const id = btn.dataset.id;
                try {
                    const res = await fetch('api.php?action=delete_image&id=' + id);
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