<?php
return [
    'db_path' => __DIR__ . '/data/database.db',
    'storage_path' => __DIR__ . '/storage/images',
    'base_url' => 'http://你的域名.com',  // 修改为你的服务器域名
    'allowed_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'allowed_exts' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'max_size' => 10 * 1024 * 1024,
];