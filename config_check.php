<?php
// 测试配置文件加载
echo "<h2>配置测试</h2>";

// 加载管理员配置
function loadAdminConfig() {
    $configFile = __DIR__ . '/private/admin_config.json';
    try {
        if (file_exists($configFile)) {
            $content = file_get_contents($configFile);
            if ($content === false) {
                echo "<p style='color: red;'>无法读取配置文件: " . $configFile . "</p>";
                return [];
            }
            $config = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo "<p style='color: red;'>配置文件JSON格式错误: " . json_last_error_msg() . "</p>";
                return [];
            }
            return $config ?: [];
        } else {
            echo "<p style='color: orange;'>配置文件不存在: " . $configFile . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>加载配置文件异常: " . $e->getMessage() . "</p>";
    }
    return [];
}

$adminConfig = loadAdminConfig();

echo "<h3>配置内容:</h3>";
echo "<pre>" . print_r($adminConfig, true) . "</pre>";

echo "<h3>变量值:</h3>";
$ADMIN_USERNAME = $adminConfig['admin_username'] ?? 'admin';
$ADMIN_PASSWORD = $adminConfig['admin_password'] ?? '123456';
$ADMIN_PATH = $adminConfig['admin_path'] ?? 'admin';

echo "<p>ADMIN_USERNAME: " . $ADMIN_USERNAME . "</p>";
echo "<p>ADMIN_PASSWORD: " . $ADMIN_PASSWORD . "</p>";
echo "<p>ADMIN_PATH: " . $ADMIN_PATH . "</p>";

echo "<h3>文件信息:</h3>";
$configFile = __DIR__ . '/private/admin_config.json';
echo "<p>配置文件路径: " . $configFile . "</p>";
echo "<p>文件存在: " . (file_exists($configFile) ? '是' : '否') . "</p>";
if (file_exists($configFile)) {
    echo "<p>文件大小: " . filesize($configFile) . " 字节</p>";
    echo "<p>文件权限: " . substr(sprintf('%o', fileperms($configFile)), -4) . "</p>";
    echo "<p>文件内容:</p>";
    echo "<pre>" . htmlspecialchars(file_get_contents($configFile)) . "</pre>";
}
?>