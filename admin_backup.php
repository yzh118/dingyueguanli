<?php
session_start();

// 加载管理员配置
function loadAdminConfig() {
    $configFile = __DIR__ . '/private/admin_config.json';
    if (file_exists($configFile)) {
        $config = json_decode(file_get_contents($configFile), true);
        return $config ?: [];
    }
    return [];
}

$adminConfig = loadAdminConfig();

// 后台登录信息（从配置文件读取）
$ADMIN_USERNAME = $adminConfig['admin_username'] ?? 'admin';
$ADMIN_PASSWORD = $adminConfig['admin_password'] ?? '123456';
$ADMIN_PATH = $adminConfig['admin_path'] ?? 'admin';

// 检查安全路径
$currentPath = basename($_SERVER['PHP_SELF'], '.php');
if ($currentPath !== $ADMIN_PATH) {
    // 如果不是正确的安全路径，重定向到正确的路径
    $correctUrl = $ADMIN_PATH . '.php';
    if (file_exists(__DIR__ . '/' . $correctUrl)) {
        header('Location: ' . $correctUrl);
        exit;
    }
}

// 检查登录状态
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // 如果是POST请求，验证登录
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if ($username === $ADMIN_USERNAME && $password === $ADMIN_PASSWORD) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $username;
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $error = "用户名或密码错误";
        }
    }
    
    // 如果未登录，显示登录表单
        ?>
        <!DOCTYPE html>
        <html lang="zh-CN">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>登录 - 订阅管理系统</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
            <style>
                body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                    display: flex;
                    align-items: center;
                justify-content: center;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                }
                .login-container {
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(10px);
                border-radius: 20px;
                padding: 40px;
                box-shadow: 0 20px 40px rgba(0,0,0,0.1);
                width: 400px;
            }
            .login-header {
                text-align: center;
                margin-bottom: 30px;
            }
            .login-header h2 {
                color: #333;
                font-weight: 600;
                margin-bottom: 10px;
            }
            .form-floating {
                margin-bottom: 20px;
            }
            .btn-login {
                    width: 100%;
                padding: 12px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border: none;
                border-radius: 10px;
                color: white;
                font-weight: 600;
                font-size: 16px;
                transition: all 0.3s ease;
            }
            .btn-login:hover {
                transform: translateY(-2px);
                box-shadow: 0 10px 20px rgba(0,0,0,0.2);
            }
            .alert {
                border-radius: 10px;
                margin-bottom: 20px;
                }
            </style>
        </head>
        <body>
            <div class="login-container">
            <div class="login-header">
                <h2><i class="bi bi-shield-lock"></i> 订阅管理系统</h2>
                <p class="text-muted">请输入管理员凭据</p>
            </div>
                <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
                <form method="post">
                <div class="form-floating">
                    <input type="text" class="form-control" id="username" name="username" placeholder="用户名" required>
                    <label for="username"><i class="bi bi-person"></i> 用户名</label>
                    </div>
                <div class="form-floating">
                    <input type="password" class="form-control" id="password" name="password" placeholder="密码" required>
                    <label for="password"><i class="bi bi-key"></i> 密码</label>
                    </div>
                <button type="submit" class="btn btn-login">
                    <i class="bi bi-box-arrow-in-right"></i> 登录
                </button>
                </form>
            </div>
        </body>
        </html>
        <?php
        exit;
}

// 处理退出登录
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台管理 - 订阅管理系统</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --dark-color: #2c3e50;
            --light-color: #f8f9fa;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        
        /* 侧边栏样式 */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            width: 250px;
            background: linear-gradient(180deg, var(--dark-color) 0%, #34495e 100%);
            color: white;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h4 {
            margin: 0;
            font-weight: 600;
            color: white;
        }
        
        .nav-menu {
            padding: 20px 0;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover, .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            border-left-color: var(--primary-color);
        }
        
        .nav-link i {
            margin-right: 10px;
            width: 20px;
        }
        
        /* 主内容区样式 */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }
        
        .navbar {
            background: white;
            padding: 15px 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        /* 卡片样式 */
        .content-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* 统计卡片样式 */
        .stats-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .stats-number {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            opacity: 0.9;
            font-size: 0.9em;
        }
        
        /* 表格样式 */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            background-color: var(--light-color);
            border: none;
            font-weight: 600;
        }
        
        .table td {
            vertical-align: middle;
        }
        
        /* 按钮样式 */
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        
        /* 模态框样式 */
        .modal-content {
            border-radius: 15px;
            border: none;
        }
        
        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            border-radius: 15px 15px 0 0;
            border: none;
        }
        
        .modal-footer {
            border-top: none;
        }
        
        /* 表单样式 */
        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 10px 15px;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102,126,234,0.25);
        }
        
        /* 标签页样式 */
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        /* 用户信息样式 */
        .user-info {
            color: rgba(255,255,255,0.8);
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9em;
        }
        
        .user-info a {
            color: white;
            text-decoration: none;
            display: inline-block;
            margin-top: 10px;
        }
        
        .user-info a:hover {
            color: var(--primary-color);
        }
        
        /* 刷新按钮动画 */
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .spin {
            animation: spin 1s linear infinite;
        }
    </style>
</head>
<body>
    <!-- 侧边栏 -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h4><i class="bi bi-shield-lock"></i> 订阅管理</h4>
        </div>
        <div class="nav-menu">
            <div class="nav-item">
                <a href="#dashboard" class="nav-link active" data-tab="dashboard">
                    <i class="bi bi-speedometer2"></i> 仪表盘
                </a>
            </div>
            <div class="nav-item">
                <a href="#sources" class="nav-link" data-tab="sources">
                    <i class="bi bi-list-ul"></i> 订阅源管理
                </a>
            </div>
            <div class="nav-item">
                <a href="#cards" class="nav-link" data-tab="cards">
                    <i class="bi bi-credit-card"></i> 卡密管理
                </a>
            </div>
            <div class="nav-item">
                <a href="#user-groups" class="nav-link" data-tab="user-groups">
                    <i class="bi bi-people"></i> 用户组管理
                </a>
            </div>
            <div class="nav-item">
                <a href="#settings" class="nav-link" data-tab="settings">
                    <i class="bi bi-gear"></i> 系统设置
                </a>
            </div>
            <div class="nav-item">
                <a href="#seo" class="nav-link" data-tab="seo">
                    <i class="bi bi-search"></i> SEO设置
                </a>
            </div>
            <div class="nav-item">
                <a href="#docs" class="nav-link" data-tab="docs">
                    <i class="bi bi-file-text"></i> 说明文档
                </a>
            </div>
        </div>
        <div class="user-info">
            <i class="bi bi-person-circle"></i> <?php echo ADMIN_USERNAME; ?>
            <br>
            <a href="?logout=1">
                <i class="bi bi-box-arrow-right"></i> 退出登录
            </a>
        </div>
        </div>
        
    <!-- 主内容区 -->
    <div class="main-content">
        <!-- 顶部导航栏 -->
        <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
            <div class="container-fluid">
                <h4 class="mb-0" id="page-title">
                    <i class="bi bi-speedometer2"></i> 仪表盘
                </h4>
                <div class="d-flex align-items-center">
                    <div class="me-2">
                        <small class="text-muted" id="debug-info">加载中...</small>
                    </div>
                    <button class="btn btn-outline-primary btn-sm me-2" onclick="testClick()">
                        <i class="bi bi-bug"></i> 测试
                    </button>
                    <a href="?logout=1" class="btn btn-outline-danger btn-sm">
                        <i class="bi bi-box-arrow-right"></i> 退出
                    </a>
                </div>
            </div>
        </nav>

        <!-- 仪表盘 -->
        <div id="dashboard" class="tab-content active">
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number" id="total-sources">0</div>
                        <div class="stats-label">订阅源总数</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number" id="active-sources">0</div>
                        <div class="stats-label">启用订阅源</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number" id="total-cards">0</div>
                        <div class="stats-label">卡密总数</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="stats-number" id="active-cards">0</div>
                        <div class="stats-label">有效卡密</div>
                    </div>
                </div>
            </div>
            
            <div class="content-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> 系统状态</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>当前模式</h6>
                            <p id="current-mode">加载中...</p>
                        </div>
                        <div class="col-md-6">
                            <h6>全局卡密验证</h6>
                            <p id="global-card-status">加载中...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 订阅源管理 -->
        <div id="sources" class="tab-content">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> 订阅源管理</h5>
                    <div>
                        <button class="btn btn-light btn-sm me-2" onclick="refreshSources()">
                            <i class="bi bi-arrow-clockwise"></i> 刷新
                        </button>
                        <button class="btn btn-light btn-sm" onclick="showAddSourceModal()">
                            <i class="bi bi-plus"></i> 新增订阅源
                        </button>
            </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                <thead>
                    <tr>
                        <th>名称</th>
                        <th>URL</th>
                                    <th>处理方式</th>
                        <th>状态</th>
                                    <th>卡密验证</th>
                        <th>操作</th>
                    </tr>
                </thead>
                            <tbody id="sources-tbody">
                                <!-- 动态加载 -->
                            </tbody>
                        </table>
                            </div>
                </div>
            </div>
        </div>

        <!-- 卡密管理 -->
        <div id="cards" class="tab-content">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-credit-card"></i> 卡密管理</h5>
                    <button class="btn btn-light btn-sm" onclick="showAddCardModal()">
                        <i class="bi bi-plus"></i> 新增卡密
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>卡密</th>
                                    <th>名称</th>
                                    <th>用户组</th>
                                    <th>允许的订阅源</th>
                                    <th>状态</th>
                                    <th>创建时间</th>
                                    <th>使用时间</th>
                                    <th>使用者</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="cards-tbody">
                                <!-- 动态加载 -->
                </tbody>
            </table>
                            </div>
                </div>
            </div>
        </div>

        <!-- 用户组管理 -->
        <div id="user-groups" class="tab-content">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-people"></i> 用户组管理</h5>
                    <button class="btn btn-light btn-sm" onclick="showAddUserGroupModal()">
                        <i class="bi bi-plus"></i> 新增用户组
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>用户组名称</th>
                                    <th>描述</th>
                                    <th>允许的订阅源</th>
                                    <th>每日请求限制</th>
                                    <th>创建时间</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody id="user-groups-tbody">
                                <!-- 动态加载 -->
                </tbody>
            </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- 系统设置 -->
        <div id="settings" class="tab-content">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> 系统设置</h5>
                </div>
                <div class="card-body">
                    <form id="settings-form">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>管理员账号设置</h6>
                                <div class="mb-3">
                                    <label for="admin-username" class="form-label">管理员用户名</label>
                                    <input type="text" class="form-control" id="admin-username" name="admin_username" placeholder="请输入管理员用户名">
                                    <div class="form-text">支持中文用户名</div>
                                </div>
                                <div class="mb-3">
                                    <label for="admin-password" class="form-label">管理员密码</label>
                                    <input type="password" class="form-control" id="admin-password" name="admin_password" placeholder="请输入新密码">
                                    <div class="form-text">支持中文密码，留空则不修改</div>
                                </div>
                                <div class="mb-3">
                                    <label for="admin-path" class="form-label">后台安全路径</label>
                                    <input type="text" class="form-control" id="admin-path" name="admin_path" placeholder="请输入后台访问路径">
                                    <div class="form-text">例如：admin、manage、backend，建议使用复杂路径</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>多源管理模式</h6>
                                <div class="mb-3">
                                    <select class="form-select" name="multi_source_mode" id="multi_source_mode">
                                        <option value="single">单一源模式</option>
                                        <option value="load_balance">负载均衡模式</option>
                                        <option value="user_choice">用户选择模式</option>
                                    </select>
                    </div>
                    
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="load_balancing" id="load_balancing">
                                        <label class="form-check-label" for="load_balancing">
                                            启用负载均衡
                        </label>
                                    </div>
                    </div>
                    
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="user_choice_enabled" id="user_choice_enabled">
                                        <label class="form-check-label" for="user_choice_enabled">
                                            启用用户选择功能
                        </label>
                    </div>
                </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <h6>卡密系统设置</h6>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="global_card_required" id="global_card_required">
                                <label class="form-check-label" for="global_card_required">
                                    全局强制卡密认证
                        </label>
                    </div>
                </div>
                    </div>
                </div>
                
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> 保存设置
                        </button>
            </form>
                </div>
            </div>
        </div>

        <!-- SEO信息设置 -->
        <div id="seo" class="tab-content">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-search"></i> SEO信息设置</h5>
                </div>
                <div class="card-body">
                    <form id="seo-form">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>基础SEO信息</h6>
                                <div class="mb-3">
                                    <label for="seo-title" class="form-label">网站标题 (Title)</label>
                                    <input type="text" class="form-control" id="seo-title" name="seo_title" placeholder="请输入网站标题">
                                    <div class="form-text">建议长度：50-60个字符</div>
                                </div>
                                <div class="mb-3">
                                    <label for="seo-description" class="form-label">网站描述 (Description)</label>
                                    <textarea class="form-control" id="seo-description" name="seo_description" rows="3" placeholder="请输入网站描述"></textarea>
                                    <div class="form-text">建议长度：150-160个字符</div>
                                </div>
                                <div class="mb-3">
                                    <label for="seo-keywords" class="form-label">关键词 (Keywords)</label>
                                    <input type="text" class="form-control" id="seo-keywords" name="seo_keywords" placeholder="关键词1,关键词2,关键词3">
                                    <div class="form-text">多个关键词用逗号分隔</div>
                                </div>
                                <div class="mb-3">
                                    <label for="seo-author" class="form-label">作者 (Author)</label>
                                    <input type="text" class="form-control" id="seo-author" name="seo_author" placeholder="请输入作者信息">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Open Graph信息</h6>
                                <div class="mb-3">
                                    <label for="og-title" class="form-label">OG标题</label>
                                    <input type="text" class="form-control" id="og-title" name="og_title" placeholder="请输入OG标题">
                                </div>
                                <div class="mb-3">
                                    <label for="og-description" class="form-label">OG描述</label>
                                    <textarea class="form-control" id="og-description" name="og_description" rows="3" placeholder="请输入OG描述"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="og-image" class="form-label">OG图片URL</label>
                                    <input type="url" class="form-control" id="og-image" name="og_image" placeholder="https://example.com/image.jpg">
                                    <div class="form-text">建议尺寸：1200x630像素</div>
                                </div>
                                <div class="mb-3">
                                    <label for="og-type" class="form-label">OG类型</label>
                                    <select class="form-select" id="og-type" name="og_type">
                                        <option value="website">网站</option>
                                        <option value="article">文章</option>
                                        <option value="product">产品</option>
                                        <option value="profile">个人资料</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>站点图标设置</h6>
                                <div class="mb-3">
                                    <label for="favicon-url" class="form-label">Favicon图标URL</label>
                                    <input type="url" class="form-control" id="favicon-url" name="favicon_url" placeholder="https://example.com/favicon.ico">
                                    <div class="form-text">建议尺寸：16x16、32x32像素</div>
                                </div>
                                <div class="mb-3">
                                    <label for="apple-touch-icon" class="form-label">Apple Touch Icon URL</label>
                                    <input type="url" class="form-control" id="apple-touch-icon" name="apple_touch_icon" placeholder="https://example.com/apple-touch-icon.png">
                                    <div class="form-text">建议尺寸：180x180像素</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6>Bing搜索引擎优化</h6>
                                <div class="mb-3">
                                    <label for="bing-verification" class="form-label">Bing验证码</label>
                                    <input type="text" class="form-control" id="bing-verification" name="bing_verification" placeholder="Bing验证码">
                                    <div class="form-text">从Bing Webmaster Tools获取</div>
                                </div>
                                <div class="mb-3">
                                    <label for="google-verification" class="form-label">Google验证码</label>
                                    <input type="text" class="form-control" id="google-verification" name="google_verification" placeholder="Google验证码">
                                    <div class="form-text">从Google Search Console获取</div>
                                </div>
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="enable-sitemap" name="enable_sitemap">
                                        <label class="form-check-label" for="enable-sitemap">
                                            启用自动生成Sitemap
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> 保存SEO设置
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- 说明文档 -->
        <div id="docs" class="tab-content">
            <div class="content-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-file-text"></i> 说明文档编辑</h5>
                </div>
                <div class="card-body">
                    <form id="docs-form">
                        <div class="mb-3">
                            <div class="btn-toolbar mb-2" role="toolbar">
                                <div class="btn-group me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('bold')">
                                        <i class="bi bi-type-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('italic')">
                                        <i class="bi bi-type-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('strike')">
                                        <i class="bi bi-type-strikethrough"></i>
                                    </button>
                                </div>
                                <div class="btn-group me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('h1')">H1</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('h2')">H2</button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('h3')">H3</button>
                                </div>
                                <div class="btn-group me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('link')">
                                        <i class="bi bi-link-45deg"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('image')">
                                        <i class="bi bi-image"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('code')">
                                        <i class="bi bi-code-slash"></i>
                                    </button>
                                </div>
                                <div class="btn-group me-2" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('ul')">
                                        <i class="bi bi-list-ul"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('ol')">
                                        <i class="bi bi-list-ol"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('task')">
                                        <i class="bi bi-check2-square"></i>
                                    </button>
                                </div>
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('quote')">
                                        <i class="bi bi-quote"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="insertMarkdown('hr')">
                                        <i class="bi bi-hr"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <textarea id="markdown-editor" name="markdown_content" class="form-control" rows="20" style="font-family: monospace;"></textarea>
                                </div>
                                <div class="col-md-6">
                                    <div id="markdown-preview" class="border rounded p-3" style="height: 100%; min-height: 400px; overflow-y: auto;"></div>
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check"></i> 保存文档
                        </button>
            </form>
                </div>
            </div>
        </div>
    </div>

    <!-- 新增订阅源模态框 -->
    <div class="modal fade" id="addSourceModal" tabindex="-1">
        <div class="modal-dialog">
        <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> 新增订阅源</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="add-source-form">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="source-name" class="form-label">订阅源名称</label>
                            <input type="text" class="form-control" id="source-name" name="name" required>
                </div>
                        <div class="mb-3">
                            <label for="source-url" class="form-label">订阅源URL</label>
                            <input type="url" class="form-control" id="source-url" name="url" required>
                        </div>
                        <div class="mb-3">
                            <label for="source-decode-type" class="form-label">处理方式</label>
                            <select class="form-select" id="source-decode-type" name="decode_type" required>
                        <option value="none">不处理</option>
                        <option value="base64">Base64解码</option>
                        <option value="base64_encode">Base64编码</option>
                    </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="source-enabled" name="enabled" checked>
                                <label class="form-check-label" for="source-enabled">启用</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="source-card-required" name="card_required">
                                <label class="form-check-label" for="source-card-required">需要卡密验证</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">添加</button>
                    </div>
                </form>
            </div>
        </div>
                </div>
                
    <!-- 编辑订阅源模态框 -->
    <div class="modal fade" id="editSourceModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> 编辑订阅源</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="edit-source-form">
                    <input type="hidden" name="source_id" id="edit-source-id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit-source-name" class="form-label">订阅源名称</label>
                            <input type="text" class="form-control" id="edit-source-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-source-url" class="form-label">订阅源URL</label>
                            <input type="url" class="form-control" id="edit-source-url" name="url" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-source-decode-type" class="form-label">处理方式</label>
                            <select class="form-select" id="edit-source-decode-type" name="decode_type" required>
                        <option value="none">不处理</option>
                        <option value="base64">Base64解码</option>
                        <option value="base64_encode">Base64编码</option>
                    </select>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-source-enabled" name="enabled">
                                <label class="form-check-label" for="edit-source-enabled">启用</label>
                            </div>
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-source-card-required" name="card_required">
                                <label class="form-check-label" for="edit-source-card-required">需要卡密验证</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
                    </div>
                </div>
                
    <!-- 新增卡密模态框 -->
    <div class="modal fade" id="addCardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> 新增卡密</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="add-card-form">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="card-code" class="form-label">卡密</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="card-code" name="code" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="generateRandomCode()">
                                    <i class="bi bi-shuffle"></i> 随机生成
                                </button>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="card-name" class="form-label">名称</label>
                            <input type="text" class="form-control" id="card-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="card-user-group" class="form-label">用户组</label>
                            <select class="form-select" id="card-user-group" name="user_group" required>
                                <option value="">请选择用户组</option>
                                <!-- 动态加载用户组选项 -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">允许访问的订阅源</label>
                            <div id="card-allowed-sources-container">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="card-all-sources" name="allowed_sources[]" value="all" checked>
                                    <label class="form-check-label" for="card-all-sources">所有订阅源</label>
                                </div>
                                <div id="card-specific-sources" style="display: none;">
                                    <!-- 动态加载订阅源选项 -->
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="card-expire-days" class="form-label">有效期（天）</label>
                            <input type="number" class="form-control" id="card-expire-days" name="expire_days" value="30" min="-1" max="3650" required>
                            <div class="form-text">-1表示永久有效，最高可设置3650天（约10年）</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">添加</button>
                </div>
            </form>
            </div>
                    </div>
                </div>
                
    <!-- 新增用户组模态框 -->
    <div class="modal fade" id="addUserGroupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> 新增用户组</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="add-user-group-form">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="user-group-name" class="form-label">用户组名称</label>
                                    <input type="text" class="form-control" id="user-group-name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="user-group-description" class="form-label">描述</label>
                                    <textarea class="form-control" id="user-group-description" name="description" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="max-daily-requests" class="form-label">每日请求限制</label>
                                    <input type="number" class="form-control" id="max-daily-requests" name="permissions[max_daily_requests]" value="100" min="0">
                                    <div class="form-text">0表示无限制</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">允许访问的订阅源</label>
                                    <div id="allowed-sources-container">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="all-sources" name="permissions[allowed_sources][]" value="all" checked>
                                            <label class="form-check-label" for="all-sources">所有订阅源</label>
                                        </div>
                                        <div id="specific-sources" style="display: none;">
                                            <!-- 动态加载订阅源选项 -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">添加</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑用户组模态框 -->
    <div class="modal fade" id="editUserGroupModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> 编辑用户组</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="edit-user-group-form">
                    <input type="hidden" name="group_id" id="edit-group-id">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit-user-group-name" class="form-label">用户组名称</label>
                                    <input type="text" class="form-control" id="edit-user-group-name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-user-group-description" class="form-label">描述</label>
                                    <textarea class="form-control" id="edit-user-group-description" name="description" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-max-daily-requests" class="form-label">每日请求限制</label>
                                    <input type="number" class="form-control" id="edit-max-daily-requests" name="permissions[max_daily_requests]" min="0">
                                    <div class="form-text">0表示无限制</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">允许访问的订阅源</label>
                                    <div id="edit-allowed-sources-container">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="edit-all-sources" name="permissions[allowed_sources][]" value="all">
                                            <label class="form-check-label" for="edit-all-sources">所有订阅源</label>
                                        </div>
                                        <div id="edit-specific-sources" style="display: none;">
                                            <!-- 动态加载订阅源选项 -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- 编辑卡密模态框 -->
    <div class="modal fade" id="editCardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> 编辑卡密</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="edit-card-form">
                    <input type="hidden" name="card_id" id="edit-card-id">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit-card-code" class="form-label">卡密</label>
                            <input type="text" class="form-control" id="edit-card-code" name="code" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit-card-user-group" class="form-label">用户组</label>
                            <select class="form-select" id="edit-card-user-group" name="user_group" required>
                                <option value="">请选择用户组</option>
                                <!-- 动态加载用户组选项 -->
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">允许访问的订阅源</label>
                            <div id="edit-card-allowed-sources-container">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit-card-all-sources" name="allowed_sources[]" value="all">
                                    <label class="form-check-label" for="edit-card-all-sources">所有订阅源</label>
                                </div>
                                <div id="edit-card-specific-sources" style="display: none;">
                                    <!-- 动态加载订阅源选项 -->
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="edit-card-status" class="form-label">状态</label>
                            <select class="form-select" id="edit-card-status" name="status" required>
                                <option value="active">有效</option>
                                <option value="inactive">无效</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript依赖 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        // 全局错误处理
        window.addEventListener('error', function(e) {
            console.error('JavaScript错误:', e.error);
            console.error('错误文件:', e.filename);
            console.error('错误行号:', e.lineno);
            console.error('错误列号:', e.colno);
            showAlert('JavaScript错误: ' + e.error.message, 'danger');
        });
        
        // 全局变量
        let sources = [];
        let cards = [];
        let userGroups = [];
        let settings = {};
        
        // 初始化Bootstrap组件
        const modals = {};
        
        // 安全地初始化模态框
        function initModals() {
            const modalElements = {
                addSource: 'addSourceModal',
                editSource: 'editSourceModal',
                addCard: 'addCardModal',
                editCard: 'editCardModal',
                addUserGroup: 'addUserGroupModal',
                editUserGroup: 'editUserGroupModal'
            };
            
            Object.keys(modalElements).forEach(key => {
                const element = document.getElementById(modalElements[key]);
                if (element) {
                    modals[key] = new bootstrap.Modal(element);
                } else {
                    console.warn(`Modal element ${modalElements[key]} not found`);
                }
            });
        }
        
        // 页面加载完成后执行
        document.addEventListener('DOMContentLoaded', () => {
            console.log('页面加载完成，开始初始化...');
            updateDebugInfo('页面加载完成');
            
            // 添加一个简单的测试
            setTimeout(() => {
                console.log('测试：页面元素检查');
                console.log('sources-tbody:', document.getElementById('sources-tbody'));
                console.log('cards-tbody:', document.getElementById('cards-tbody'));
                console.log('user-groups-tbody:', document.getElementById('user-groups-tbody'));
                console.log('addSourceModal:', document.getElementById('addSourceModal'));
                console.log('modals对象:', modals);
                updateDebugInfo('元素检查完成');
            }, 1000);
            
            try {
                // 初始化模态框
                initModals();
                console.log('模态框初始化完成');
                updateDebugInfo('模态框初始化完成');
                
                // 初始化标签页切换
                initTabSwitching();
                console.log('标签页切换初始化完成');
                updateDebugInfo('标签页切换初始化完成');
                
                // 加载数据
                loadDashboardData();
                loadSources();
                loadCards();
                loadUserGroups();
                loadSettings();
                loadSeoSettings();
                loadDocs();
                console.log('数据加载完成');
                updateDebugInfo('数据加载完成');
                
                // 绑定表单提交事件
                bindFormSubmitEvents();
                console.log('表单事件绑定完成');
                updateDebugInfo('表单事件绑定完成');
                
                console.log('页面初始化完成');
                updateDebugInfo('页面初始化完成');
            } catch (error) {
                console.error('页面初始化失败:', error);
                updateDebugInfo('页面初始化失败: ' + error.message);
                showAlert('页面初始化失败: ' + error.message, 'danger');
            }
        });
        
        // 更新调试信息
        function updateDebugInfo(message) {
            const debugElement = document.getElementById('debug-info');
            if (debugElement) {
                debugElement.textContent = message;
            }
        }
        
        // 初始化标签页切换
        function initTabSwitching() {
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    
                    // 更新导航项状态
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                    
                    // 更新标题
                    document.getElementById('page-title').textContent = link.textContent.trim();
                    
                    // 显示对应内容
                    const tabId = link.getAttribute('data-tab');
                    document.querySelectorAll('.tab-content').forEach(tab => {
                        tab.classList.remove('active');
                    });
                    document.getElementById(tabId).classList.add('active');
                });
            });
        }
        
        // 加载仪表盘数据
        async function loadDashboardData() {
            try {
                const response = await fetch('api.php?action=get_stats');
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('total-sources').textContent = data.stats.total_sources;
                    document.getElementById('active-sources').textContent = data.stats.active_sources;
                    document.getElementById('total-cards').textContent = data.stats.total_cards;
                    document.getElementById('active-cards').textContent = data.stats.active_cards;
                    document.getElementById('current-mode').textContent = data.stats.current_mode;
                    document.getElementById('global-card-status').textContent = data.stats.global_card_required ? '已启用' : '未启用';
                }
            } catch (error) {
                console.error('加载仪表盘数据失败:', error);
                showAlert('加载仪表盘数据失败', 'danger');
            }
        }
        
        // 加载订阅源列表
        async function loadSources() {
            try {
                const response = await fetch('api.php?action=get_sources');
                const data = await response.json();
                
                if (data.success && data.sources) {
                    sources = data.sources;
                    renderSourcesTable();
                } else {
                    throw new Error(data.error || '获取订阅源列表失败');
                }
            } catch (error) {
                console.error('加载订阅源失败:', error);
                showAlert('加载订阅源失败', 'danger');
            }
        }
        
        // 刷新订阅源列表
        async function refreshSources() {
            const button = document.querySelector('button[onclick="refreshSources()"]');
            const icon = button.querySelector('i');
            button.disabled = true;
            icon.classList.add('spin');
            
            try {
                await loadSources();
                showAlert('刷新成功', 'success');
            } catch (error) {
                console.error('刷新失败:', error);
                showAlert('刷新失败', 'danger');
            } finally {
                button.disabled = false;
                icon.classList.remove('spin');
            }
        }
        
        // 渲染订阅源表格
        function renderSourcesTable() {
            const tbody = document.getElementById('sources-tbody');
            tbody.innerHTML = '';
            
            sources.forEach(source => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${source.name}</td>
                    <td>${source.url}</td>
                    <td>${getDecodeTypeText(source.decode_type)}</td>
                    <td>
                        <span class="badge bg-${source.enabled ? 'success' : 'danger'}">
                            ${source.enabled ? '启用' : '禁用'}
                        </span>
                    </td>
                    <td>
                        <span class="badge bg-${source.card_required ? 'primary' : 'secondary'}">
                            ${source.card_required ? '需要' : '不需要'}
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editSource('${source.id}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteSource('${source.id}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        // 获取处理方式文本
        function getDecodeTypeText(type) {
            const types = {
                'none': '不处理',
                'base64': 'Base64解码',
                'base64_encode': 'Base64编码'
            };
            return types[type] || type;
        }
        
        // 加载卡密列表
        async function loadCards() {
            try {
                const response = await fetch('api.php?action=get_cards');
                const data = await response.json();
                
                if (data.success && data.cards) {
                    cards = data.cards;
                    renderCardsTable();
                } else {
                    throw new Error(data.error || '获取卡密列表失败');
                }
            } catch (error) {
                console.error('加载卡密失败:', error);
                showAlert('加载卡密失败', 'danger');
            }
        }
        
        // 渲染卡密表格
        function renderCardsTable() {
            const tbody = document.getElementById('cards-tbody');
            tbody.innerHTML = '';
            
            cards.forEach(card => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${card.card || card.code}</td>
                    <td>${card.name || '-'}</td>
                    <td>${getUserGroupName(card.user_group)}</td>
                    <td>${getAllowedSourcesText(card.allowed_sources)}</td>
                    <td>
                        <span class="badge bg-${card.status === 'active' ? 'success' : 'danger'}">
                            ${card.status === 'active' ? '有效' : '无效'}
                        </span>
                    </td>
                    <td>${formatDate(card.created_at)}</td>
                    <td>${card.used_at ? formatDate(card.used_at) : '-'}</td>
                    <td>${card.used_by || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editCard('${card.id}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteCard('${card.id}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        // 获取用户组名称
        function getUserGroupName(userGroupId) {
            // 这里需要从全局变量中获取用户组信息
            // 暂时返回ID，后续可以优化
            return userGroupId || '-';
        }
        
        // 获取允许的订阅源文本
        function getAllowedSourcesText(allowedSources) {
            if (!allowedSources || allowedSources.length === 0) {
                return '无';
            }
            if (allowedSources.includes('all')) {
                return '所有订阅源';
            }
            return allowedSources.join(', ');
        }
        
        // 加载系统设置
        async function loadSettings() {
            try {
                // 加载管理员配置
                const adminResponse = await fetch('api.php?action=get_admin_config');
                const adminData = await adminResponse.json();
                
                if (adminData.success && adminData.admin_config) {
                    const adminConfig = adminData.admin_config;
                    document.getElementById('admin-username').value = adminConfig.admin_username || '';
                    document.getElementById('admin-password').value = ''; // 密码不显示
                    document.getElementById('admin-path').value = adminConfig.admin_path || '';
                }
                
                // 加载订阅源设置
                const sourceResponse = await fetch('api.php?action=get_settings');
                const sourceData = await sourceResponse.json();
                
                if (sourceData.success) {
                    settings = sourceData.settings;
                    document.getElementById('multi_source_mode').value = settings.multi_source_mode;
                    document.getElementById('load_balancing').checked = settings.load_balancing;
                    document.getElementById('user_choice_enabled').checked = settings.user_choice_enabled;
                }
                
                // 加载卡密设置
                const cardResponse = await fetch('api.php?action=get_cards');
                const cardData = await cardResponse.json();
                
                if (cardData.settings) {
                    document.getElementById('global_card_required').checked = cardData.settings.global_card_required;
                }
            } catch (error) {
                console.error('加载设置失败:', error);
                showAlert('加载设置失败', 'danger');
            }
        }
        
        // 加载SEO设置
        async function loadSeoSettings() {
            try {
                const response = await fetch('api.php?action=get_seo_settings');
                const data = await response.json();
                
                if (data.success && data.seo_settings) {
                    const seoSettings = data.seo_settings;
                    
                    // 填充表单字段
                    document.getElementById('seo-title').value = seoSettings.seo_title || '';
                    document.getElementById('seo-description').value = seoSettings.seo_description || '';
                    document.getElementById('seo-keywords').value = seoSettings.seo_keywords || '';
                    document.getElementById('seo-author').value = seoSettings.seo_author || '';
                    document.getElementById('og-title').value = seoSettings.og_title || '';
                    document.getElementById('og-description').value = seoSettings.og_description || '';
                    document.getElementById('og-image').value = seoSettings.og_image || '';
                    document.getElementById('og-type').value = seoSettings.og_type || 'website';
                    document.getElementById('favicon-url').value = seoSettings.favicon_url || '';
                    document.getElementById('apple-touch-icon').value = seoSettings.apple_touch_icon || '';
                    document.getElementById('bing-verification').value = seoSettings.bing_verification || '';
                    document.getElementById('google-verification').value = seoSettings.google_verification || '';
                    document.getElementById('enable-sitemap').checked = seoSettings.enable_sitemap || false;
                }
            } catch (error) {
                console.error('加载SEO设置失败:', error);
                showAlert('加载SEO设置失败', 'danger');
            }
        }
        
        // 加载说明文档
        async function loadDocs() {
            try {
                const response = await fetch('api.php?action=get_docs');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const text = await response.text();
                console.log('Response text:', text); // 调试输出
                console.log('Response headers:', response.headers);
                console.log('Response status:', response.status);
                console.log('Response type:', response.type);
                
                // 检查返回的内容是否为空
                if (!text.trim()) {
                    throw new Error('返回内容为空');
                }
                
                let data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    // 尝试检查是否有BOM头
                    if (text.charCodeAt(0) === 0xFEFF) {
                        try {
                            data = JSON.parse(text.slice(1));
                        } catch (e2) {
                            console.error('JSON解析失败(包含BOM):', e2);
                            throw new Error('返回数据格式错误(BOM)');
                        }
                    } else {
                        console.error('JSON解析失败:', e);
                        console.error('原始文本(前100字符):', text.substring(0, 100));
                        console.error('文本长度:', text.length);
                        console.error('文本前10个字符的ASCII码:', Array.from(text.substring(0, 10)).map(c => c.charCodeAt(0)));
                        throw new Error('返回数据格式错误，请查看控制台');
                    }
                }
                
                if (data.success) {
                    const editor = document.getElementById('markdown-editor');
                    editor.value = data.content;
                    updateMarkdownPreview();
                    
                    // 添加实时预览
                    editor.removeEventListener('input', updateMarkdownPreview); // 先移除旧的
                    editor.addEventListener('input', updateMarkdownPreview);
                } else {
                    throw new Error(data.message || '加载失败');
                }
            } catch (error) {
                console.error('加载说明文档失败:', error);
                showAlert('加载说明文档失败: ' + error.message, 'danger');
            }
        }
        
        // 更新Markdown预览
        function updateMarkdownPreview() {
            const content = document.getElementById('markdown-editor').value;
            const preview = document.getElementById('markdown-preview');
            preview.innerHTML = marked.parse(content);
        }
        
        // Markdown编辑器功能
        function insertMarkdown(type) {
            const editor = document.getElementById('markdown-editor');
            const start = editor.selectionStart;
            const end = editor.selectionEnd;
            const text = editor.value;
            const selection = text.substring(start, end);
            let insertion = '';
            
            switch (type) {
                case 'bold':
                    insertion = `**${selection || '粗体文本'}**`;
                    break;
                case 'italic':
                    insertion = `*${selection || '斜体文本'}*`;
                    break;
                case 'strike':
                    insertion = `~~${selection || '删除线文本'}~~`;
                    break;
                case 'h1':
                    insertion = `\n# ${selection || '一级标题'}\n`;
                    break;
                case 'h2':
                    insertion = `\n## ${selection || '二级标题'}\n`;
                    break;
                case 'h3':
                    insertion = `\n### ${selection || '三级标题'}\n`;
                    break;
                case 'link':
                    insertion = `[${selection || '链接文本'}](url)`;
                    break;
                case 'image':
                    insertion = `![${selection || '图片描述'}](url)`;
                    break;
                case 'code':
                    insertion = selection.includes('\n') 
                        ? `\n\`\`\`\n${selection || '代码块'}\n\`\`\`\n`
                        : `\`${selection || '行内代码'}\``;
                    break;
                case 'ul':
                    insertion = selection
                        ? selection.split('\n').map(line => `- ${line}`).join('\n')
                        : '- 列表项';
                    break;
                case 'ol':
                    insertion = selection
                        ? selection.split('\n').map((line, i) => `${i + 1}. ${line}`).join('\n')
                        : '1. 列表项';
                    break;
                case 'task':
                    insertion = selection
                        ? selection.split('\n').map(line => `- [ ] ${line}`).join('\n')
                        : '- [ ] 任务项';
                    break;
                case 'quote':
                    insertion = selection
                        ? selection.split('\n').map(line => `> ${line}`).join('\n')
                        : '> 引用文本';
                    break;
                case 'hr':
                    insertion = '\n---\n';
                    break;
            }
            
            editor.value = text.substring(0, start) + insertion + text.substring(end);
            editor.focus();
            const newCursorPos = start + insertion.length;
            editor.setSelectionRange(newCursorPos, newCursorPos);
            
            // 更新预览
            updateMarkdownPreview();
        }
        
        // 绑定表单提交事件
        function bindFormSubmitEvents() {
            // 绑定订阅源选择逻辑
            bindSourceSelectionEvents();
            
            // 添加订阅源
            document.getElementById('add-source-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formDataObj = {};
                formData.forEach((value, key) => {
                    formDataObj[key] = value;
                });
                
                const requestData = {
                    action: 'add_source',
                    name: formDataObj.name,
                    url: formDataObj.url,
                    decode_type: formDataObj.decode_type,
                    enabled: formDataObj.enabled === 'on',
                    card_required: formDataObj.card_required === 'on'
                };
                
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        hideModal('addSource');
                        e.target.reset();
                        loadSources();
                        loadDashboardData();
                        showAlert('添加订阅源成功', 'success');
                    } else {
                        showAlert(data.error || '添加订阅源失败', 'danger');
                    }
                } catch (error) {
                    console.error('添加订阅源失败:', error);
                    showAlert('添加订阅源失败', 'danger');
                }
            });
            
            // 编辑订阅源
            document.getElementById('edit-source-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formDataObj = {};
                formData.forEach((value, key) => {
                    formDataObj[key] = value;
                });
                
                const requestData = {
                    action: 'edit_source',
                    source_id: formDataObj.source_id,
                    name: formDataObj.name,
                    url: formDataObj.url,
                    decode_type: formDataObj.decode_type,
                    enabled: formDataObj.enabled === 'on',
                    card_required: formDataObj.card_required === 'on'
                };
                
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        hideModal('editSource');
                        loadSources();
                        loadDashboardData();
                        showAlert('编辑订阅源成功', 'success');
                    } else {
                        showAlert(data.error || '编辑订阅源失败', 'danger');
                    }
                } catch (error) {
                    console.error('编辑订阅源失败:', error);
                    showAlert('编辑订阅源失败', 'danger');
                }
            });
            
            // 添加卡密
            document.getElementById('add-card-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formDataObj = {};
                formData.forEach((value, key) => {
                    formDataObj[key] = value;
                });
                
                // 处理允许的订阅源
                const allowedSources = formDataObj['allowed_sources[]'] || ['all'];
                
                const requestData = {
                    action: 'add_card',
                    card: formDataObj.code,
                    name: formDataObj.name,
                    user_group: formDataObj.user_group,
                    allowed_sources: allowedSources,
                    expire_days: parseInt(formDataObj.expire_days) || 30
                };
                
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        hideModal('addCard');
                        e.target.reset();
                        loadCards();
                        loadDashboardData();
                        showAlert('添加卡密成功', 'success');
                    } else {
                        showAlert(data.error || '添加卡密失败', 'danger');
                    }
                } catch (error) {
                    console.error('添加卡密失败:', error);
                    showAlert('添加卡密失败', 'danger');
                }
            });
            
            // 保存设置
            document.getElementById('settings-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formDataObj = {};
                formData.forEach((value, key) => {
                    formDataObj[key] = value;
                });
                
                try {
                    // 保存管理员配置
                    const adminRequestData = {
                        action: 'update_admin_config',
                        admin_username: formDataObj.admin_username,
                        admin_password: formDataObj.admin_password,
                        admin_path: formDataObj.admin_path
                    };
                    
                    const adminResponse = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(adminRequestData)
                    });
                    const adminData = await adminResponse.json();
                    
                    // 保存订阅源设置
                    const sourceRequestData = {
                        action: 'update_settings',
                        multi_source_mode: formDataObj.multi_source_mode,
                        load_balancing: formDataObj.load_balancing === 'on',
                        user_choice_enabled: formDataObj.user_choice_enabled === 'on'
                    };
                    
                    const sourceResponse = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(sourceRequestData)
                    });
                    const sourceData = await sourceResponse.json();
                    
                    // 保存卡密设置
                    const cardRequestData = {
                        action: 'update_card_settings',
                        global_card_required: formDataObj.global_card_required === 'on'
                    };
                    
                    const cardResponse = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(cardRequestData)
                    });
                    const cardData = await cardResponse.json();
                    
                    if (adminData.success && sourceData.success && cardData.success) {
                        loadSettings();
                        loadDashboardData();
                        showAlert('保存设置成功', 'success');
                    } else {
                        showAlert(adminData.error || sourceData.error || cardData.error || '保存设置失败', 'danger');
                    }
                } catch (error) {
                    console.error('保存设置失败:', error);
                    showAlert('保存设置失败', 'danger');
                }
            });
            
            // 保存说明文档
            document.getElementById('docs-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const markdownContent = formData.get('markdown_content') || '';
                
                const requestData = {
                    action: 'save_docs',
                    markdown_content: markdownContent
                };
                
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        showAlert('保存文档成功', 'success');
                    } else {
                        showAlert(data.error || '保存文档失败', 'danger');
                    }
                } catch (error) {
                    console.error('保存文档失败:', error);
                    showAlert('保存文档失败', 'danger');
                }
            });
            
            // 保存SEO设置
            document.getElementById('seo-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formDataObj = {};
                formData.forEach((value, key) => {
                    formDataObj[key] = value;
                });
                
                const requestData = {
                    action: 'save_seo_settings',
                    seo_title: formDataObj.seo_title,
                    seo_description: formDataObj.seo_description,
                    seo_keywords: formDataObj.seo_keywords,
                    seo_author: formDataObj.seo_author,
                    og_title: formDataObj.og_title,
                    og_description: formDataObj.og_description,
                    og_image: formDataObj.og_image,
                    og_type: formDataObj.og_type,
                    favicon_url: formDataObj.favicon_url,
                    apple_touch_icon: formDataObj.apple_touch_icon,
                    bing_verification: formDataObj.bing_verification,
                    google_verification: formDataObj.google_verification,
                    enable_sitemap: formDataObj.enable_sitemap === 'on'
                };
                
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        showAlert('保存SEO设置成功', 'success');
                    } else {
                        showAlert(data.error || '保存SEO设置失败', 'danger');
                    }
                } catch (error) {
                    console.error('保存SEO设置失败:', error);
                    showAlert('保存SEO设置失败', 'danger');
                }
            });
            
            // 添加用户组
            document.getElementById('add-user-group-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formDataObj = {};
                formData.forEach((value, key) => {
                    formDataObj[key] = value;
                });
                
                // 处理权限数据
                const permissions = {
                    max_daily_requests: parseInt(formDataObj['permissions[max_daily_requests]']) || 0,
                    allowed_sources: formDataObj['permissions[allowed_sources][]'] || ['all']
                };
                
                const requestData = {
                    action: 'add_user_group',
                    name: formDataObj.name,
                    description: formDataObj.description,
                    permissions: permissions
                };
                
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        hideModal('addUserGroup');
                        e.target.reset();
                        loadUserGroups();
                        showAlert('添加用户组成功', 'success');
                    } else {
                        showAlert(data.error || '添加用户组失败', 'danger');
                    }
                } catch (error) {
                    console.error('添加用户组失败:', error);
                    showAlert('添加用户组失败', 'danger');
                }
            });
            
            // 编辑用户组
            document.getElementById('edit-user-group-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formDataObj = {};
                formData.forEach((value, key) => {
                    formDataObj[key] = value;
                });
                
                // 处理权限数据
                const permissions = {
                    max_daily_requests: parseInt(formDataObj['permissions[max_daily_requests]']) || 0,
                    allowed_sources: formDataObj['permissions[allowed_sources][]'] || ['all']
                };
                
                const requestData = {
                    action: 'update_user_group',
                    id: formDataObj.group_id,
                    name: formDataObj.name,
                    description: formDataObj.description,
                    permissions: permissions
                };
                
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        hideModal('editUserGroup');
                        loadUserGroups();
                        showAlert('更新用户组成功', 'success');
                    } else {
                        showAlert(data.error || '更新用户组失败', 'danger');
                    }
                } catch (error) {
                    console.error('更新用户组失败:', error);
                    showAlert('更新用户组失败', 'danger');
                }
            });
            
            // 编辑卡密
            document.getElementById('edit-card-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                const formDataObj = {};
                formData.forEach((value, key) => {
                    formDataObj[key] = value;
                });
                
                // 处理允许的订阅源
                const allowedSources = formDataObj['allowed_sources[]'] || ['all'];
                
                const requestData = {
                    action: 'update_card',
                    id: formDataObj.card_id,
                    card: formDataObj.code,
                    user_group: formDataObj.user_group,
                    allowed_sources: allowedSources,
                    status: formDataObj.status
                };
                
                try {
                    const response = await fetch('api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify(requestData)
                    });
                    const data = await response.json();
                    
                    if (data.success) {
                        hideModal('editCard');
                        loadCards();
                        loadDashboardData();
                        showAlert('更新卡密成功', 'success');
                    } else {
                        showAlert(data.error || '更新卡密失败', 'danger');
                    }
                } catch (error) {
                    console.error('更新卡密失败:', error);
                    showAlert('更新卡密失败', 'danger');
                }
            });
        }
        
        // 安全地显示模态框
        function showModal(modalName) {
            if (modals[modalName]) {
                modals[modalName].show();
                // 重新绑定订阅源选择事件
                setTimeout(() => {
                    bindSourceSelectionEvents();
                }, 100);
            } else {
                console.error(`Modal ${modalName} not found`);
                showAlert('模态框初始化失败', 'danger');
            }
        }
        
        // 安全地隐藏模态框
        function hideModal(modalName) {
            if (modals[modalName]) {
                modals[modalName].hide();
            } else {
                console.error(`Modal ${modalName} not found`);
            }
        }
        
        // 显示添加订阅源模态框
        function showAddSourceModal() {
            showModal('addSource');
        }
        
        // 编辑订阅源
        function editSource(sourceId) {
            const source = sources.find(s => s.id === sourceId);
            if (!source) return;
            
            document.getElementById('edit-source-id').value = source.id;
            document.getElementById('edit-source-name').value = source.name;
            document.getElementById('edit-source-url').value = source.url;
            document.getElementById('edit-source-decode-type').value = source.decode_type;
            document.getElementById('edit-source-enabled').checked = source.enabled;
            document.getElementById('edit-source-card-required').checked = source.card_required;
            
            showModal('editSource');
        }
        
        // 删除订阅源
        async function deleteSource(sourceId) {
            if (!confirm('确定要删除这个订阅源吗？')) return;
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_source',
                        source_id: sourceId
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    loadSources();
                    loadDashboardData();
                    showAlert('删除订阅源成功', 'success');
                } else {
                    showAlert(data.error || '删除订阅源失败', 'danger');
                }
            } catch (error) {
                console.error('删除订阅源失败:', error);
                showAlert('删除订阅源失败', 'danger');
            }
        }
        
        // 显示添加卡密模态框
        function showAddCardModal() {
            // 加载用户组和订阅源到模态框
            loadUserGroupsForCardModal();
            loadSourcesForCardModal();
            showModal('addCard');
        }
        
        // 生成随机卡密
        function generateRandomCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = '';
            for (let i = 0; i < 16; i++) {
                if (i > 0 && i % 4 === 0) code += '-';
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            document.getElementById('card-code').value = code;
        }
        
        // 删除卡密
        async function deleteCard(code) {
            if (!confirm('确定要删除这个卡密吗？')) return;
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_card',
                        id: code
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    loadCards();
                    loadDashboardData();
                    showAlert('删除卡密成功', 'success');
                } else {
                    showAlert(data.error || '删除卡密失败', 'danger');
                }
            } catch (error) {
                console.error('删除卡密失败:', error);
                showAlert('删除卡密失败', 'danger');
            }
        }
        
        // 格式化日期
        function formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleString('zh-CN', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }
        
        // 加载用户组列表
        async function loadUserGroups() {
            try {
                const response = await fetch('api.php?action=get_user_groups');
                const data = await response.json();
                
                if (data.success && data.user_groups) {
                    userGroups = data.user_groups; // 保存到全局变量
                    renderUserGroupsTable(userGroups);
                } else {
                    throw new Error(data.error || '获取用户组列表失败');
                }
            } catch (error) {
                console.error('加载用户组失败:', error);
                showAlert('加载用户组失败', 'danger');
            }
        }
        
        // 渲染用户组表格
        function renderUserGroupsTable(userGroups) {
            const tbody = document.getElementById('user-groups-tbody');
            tbody.innerHTML = '';
            
            userGroups.forEach(group => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${group.name}</td>
                    <td>${group.description || '-'}</td>
                    <td>${group.permissions.allowed_sources.includes('all') ? '所有订阅源' : group.permissions.allowed_sources.join(', ')}</td>
                    <td>${group.permissions.max_daily_requests === 0 ? '无限制' : group.permissions.max_daily_requests}</td>
                    <td>${formatDate(group.created_at)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="editUserGroup('${group.id}')">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteUserGroup('${group.id}')">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }
        
        // 显示添加用户组模态框
        function showAddUserGroupModal() {
            // 加载订阅源到模态框
            loadSourcesForModal();
            showModal('addUserGroup');
        }
        
        // 编辑用户组
        function editUserGroup(groupId) {
            const group = userGroups.find(g => g.id === groupId);
            if (!group) return;
            
            // 加载订阅源到编辑模态框
            loadSourcesForEditModal();
            
            document.getElementById('edit-group-id').value = group.id;
            document.getElementById('edit-user-group-name').value = group.name;
            document.getElementById('edit-user-group-description').value = group.description || '';
            document.getElementById('edit-max-daily-requests').value = group.permissions?.max_daily_requests || 100;
            
            // 设置允许的订阅源
            setTimeout(() => {
                const allowedSourcesCheckboxes = document.querySelectorAll('#edit-specific-sources input[type="checkbox"]');
                allowedSourcesCheckboxes.forEach(checkbox => {
                    checkbox.checked = group.permissions?.allowed_sources && group.permissions.allowed_sources.includes(checkbox.value);
                });
                
                // 设置"所有订阅源"复选框
                const allSourcesCheckbox = document.getElementById('edit-all-sources');
                if (allSourcesCheckbox) {
                    allSourcesCheckbox.checked = group.permissions?.allowed_sources && group.permissions.allowed_sources.includes('all');
                    
                    // 根据"所有订阅源"的状态显示/隐藏具体订阅源选项
                    const specificSources = document.getElementById('edit-specific-sources');
                    if (allSourcesCheckbox.checked) {
                        specificSources.style.display = 'none';
                    } else {
                        specificSources.style.display = 'block';
                    }
                }
            }, 100);
            
            showModal('editUserGroup');
        }
        
        // 删除用户组
        async function deleteUserGroup(groupId) {
            if (!confirm('确定要删除这个用户组吗？')) return;
            
            try {
                const response = await fetch('api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete_user_group',
                        id: groupId
                    })
                });
                const data = await response.json();
                
                if (data.success) {
                    loadUserGroups();
                    showAlert('删除用户组成功', 'success');
                } else {
                    showAlert(data.error || '删除用户组失败', 'danger');
                }
            } catch (error) {
                console.error('删除用户组失败:', error);
                showAlert('删除用户组失败', 'danger');
            }
        }
        
        // 加载订阅源到模态框
        async function loadSourcesForModal() {
            try {
                const response = await fetch('api.php?action=get_sources');
                const data = await response.json();
                
                if (data.success && data.sources) {
                    const container = document.getElementById('specific-sources');
                    container.innerHTML = '';
                    
                    data.sources.forEach(source => {
                        const div = document.createElement('div');
                        div.className = 'form-check';
                        div.innerHTML = `
                            <input class="form-check-input" type="checkbox" name="permissions[allowed_sources][]" value="${source.id}" id="source-${source.id}">
                            <label class="form-check-label" for="source-${source.id}">${source.name}</label>
                        `;
                        container.appendChild(div);
                    });
                }
            } catch (error) {
                console.error('加载订阅源失败:', error);
            }
        }
        
        // 加载订阅源到编辑模态框
        async function loadSourcesForEditModal() {
            try {
                const response = await fetch('api.php?action=get_sources');
                const data = await response.json();
                
                if (data.success && data.sources) {
                    const container = document.getElementById('edit-specific-sources');
                    container.innerHTML = '';
                    
                    data.sources.forEach(source => {
                        const div = document.createElement('div');
                        div.className = 'form-check';
                        div.innerHTML = `
                            <input class="form-check-input" type="checkbox" name="permissions[allowed_sources][]" value="${source.id}" id="edit-source-${source.id}">
                            <label class="form-check-label" for="edit-source-${source.id}">${source.name}</label>
                        `;
                        container.appendChild(div);
                    });
                }
            } catch (error) {
                console.error('加载订阅源失败:', error);
            }
        }
        
        // 加载用户组到卡密模态框
        async function loadUserGroupsForCardModal() {
            try {
                const response = await fetch('api.php?action=get_user_groups');
                const data = await response.json();
                
                if (data.success && data.user_groups) {
                    const select = document.getElementById('card-user-group');
                    select.innerHTML = '<option value="">请选择用户组</option>';
                    
                    data.user_groups.forEach(group => {
                        const option = document.createElement('option');
                        option.value = group.id;
                        option.textContent = group.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('加载用户组失败:', error);
            }
        }
        
        // 加载订阅源到卡密模态框
        async function loadSourcesForCardModal() {
            try {
                const response = await fetch('api.php?action=get_sources');
                const data = await response.json();
                
                if (data.success && data.sources) {
                    const container = document.getElementById('card-specific-sources');
                    container.innerHTML = '';
                    
                    data.sources.forEach(source => {
                        const div = document.createElement('div');
                        div.className = 'form-check';
                        div.innerHTML = `
                            <input class="form-check-input" type="checkbox" name="allowed_sources[]" value="${source.id}" id="card-source-${source.id}">
                            <label class="form-check-label" for="card-source-${source.id}">${source.name}</label>
                        `;
                        container.appendChild(div);
                    });
                }
            } catch (error) {
                console.error('加载订阅源失败:', error);
            }
        }
        
        // 显示提示信息
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 end-0 m-3`;
            alertDiv.style.zIndex = '9999';
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
        
        // 编辑卡密
        function editCard(cardId) {
            const card = cards.find(c => c.id === cardId);
            if (!card) return;
            
            // 加载用户组和订阅源到编辑模态框
            loadUserGroupsForEditCardModal();
            loadSourcesForEditCardModal();
            
            document.getElementById('edit-card-id').value = card.id;
            document.getElementById('edit-card-code').value = card.card || card.code;
            document.getElementById('edit-card-user-group').value = card.user_group || '';
            
            // 设置允许的订阅源
            setTimeout(() => {
                const allowedSourcesCheckboxes = document.querySelectorAll('#edit-card-specific-sources input[type="checkbox"]');
                allowedSourcesCheckboxes.forEach(checkbox => {
                    checkbox.checked = card.allowed_sources && card.allowed_sources.includes(checkbox.value);
                });
                
                // 设置"所有订阅源"复选框
                const allSourcesCheckbox = document.getElementById('edit-card-all-sources');
                if (allSourcesCheckbox) {
                    allSourcesCheckbox.checked = card.allowed_sources && card.allowed_sources.includes('all');
                }
            }, 100);
            
            document.getElementById('edit-card-status').value = card.status || 'active';
            
            showModal('editCard');
        }
        
        // 加载用户组到编辑卡密模态框
        async function loadUserGroupsForEditCardModal() {
            try {
                const response = await fetch('api.php?action=get_user_groups');
                const data = await response.json();
                
                if (data.success && data.user_groups) {
                    const select = document.getElementById('edit-card-user-group');
                    select.innerHTML = '<option value="">请选择用户组</option>';
                    
                    data.user_groups.forEach(group => {
                        const option = document.createElement('option');
                        option.value = group.id;
                        option.textContent = group.name;
                        select.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('加载用户组失败:', error);
            }
        }
        
        // 加载订阅源到编辑卡密模态框
        async function loadSourcesForEditCardModal() {
            try {
                const response = await fetch('api.php?action=get_sources');
                const data = await response.json();
                
                if (data.success && data.sources) {
                    const container = document.getElementById('edit-card-specific-sources');
                    container.innerHTML = '';
                    
                    data.sources.forEach(source => {
                        const div = document.createElement('div');
                        div.className = 'form-check';
                        div.innerHTML = `
                            <input class="form-check-input" type="checkbox" name="allowed_sources[]" value="${source.id}" id="edit-card-source-${source.id}">
                            <label class="form-check-label" for="edit-card-source-${source.id}">${source.name}</label>
                        `;
                        container.appendChild(div);
                    });
                }
            } catch (error) {
                console.error('加载订阅源失败:', error);
            }
        }
        
        // 测试函数
        function testClick() {
            console.log('测试点击成功！');
            console.log('当前模态框状态:', modals);
            console.log('当前数据状态:', { sources, cards, settings });
            
            // 测试API
            testAPI();
            
            // 测试模态框
            if (modals.addSource) {
                console.log('addSource模态框存在');
                showAlert('addSource模态框存在，点击测试成功！', 'success');
            } else {
                console.log('addSource模态框不存在');
                showAlert('addSource模态框不存在，初始化可能失败', 'warning');
            }
        }
        
        // 测试API
        async function testAPI() {
            try {
                const response = await fetch('api.php?action=test');
                const data = await response.json();
                console.log('API测试结果:', data);
                
                if (data.success) {
                    showAlert('API测试成功: ' + data.message, 'success');
                } else {
                    showAlert('API测试失败', 'danger');
                }
            } catch (error) {
                console.error('API测试失败:', error);
                showAlert('API测试失败: ' + error.message, 'danger');
            }
        }
        
        // 绑定订阅源选择事件
        function bindSourceSelectionEvents() {
            // 新增用户组模态框中的订阅源选择
            const allSourcesCheckbox = document.getElementById('all-sources');
            if (allSourcesCheckbox) {
                allSourcesCheckbox.addEventListener('change', function() {
                    const specificSources = document.getElementById('specific-sources');
                    const specificCheckboxes = specificSources.querySelectorAll('input[type="checkbox"]');
                    
                    if (this.checked) {
                        // 选中"所有订阅源"时，隐藏具体订阅源选项
                        specificSources.style.display = 'none';
                        specificCheckboxes.forEach(checkbox => {
                            checkbox.checked = false;
                        });
                    } else {
                        // 取消"所有订阅源"时，显示具体订阅源选项
                        specificSources.style.display = 'block';
                    }
                });
            }
            
            // 编辑用户组模态框中的订阅源选择
            const editAllSourcesCheckbox = document.getElementById('edit-all-sources');
            if (editAllSourcesCheckbox) {
                editAllSourcesCheckbox.addEventListener('change', function() {
                    const specificSources = document.getElementById('edit-specific-sources');
                    const specificCheckboxes = specificSources.querySelectorAll('input[type="checkbox"]');
                    
                    if (this.checked) {
                        // 选中"所有订阅源"时，隐藏具体订阅源选项
                        specificSources.style.display = 'none';
                        specificCheckboxes.forEach(checkbox => {
                            checkbox.checked = false;
                        });
                    } else {
                        // 取消"所有订阅源"时，显示具体订阅源选项
                        specificSources.style.display = 'block';
                    }
                });
            }
            
            // 卡密模态框中的订阅源选择
            const cardAllSourcesCheckbox = document.getElementById('card-all-sources');
            if (cardAllSourcesCheckbox) {
                cardAllSourcesCheckbox.addEventListener('change', function() {
                    const specificSources = document.getElementById('card-specific-sources');
                    const specificCheckboxes = specificSources.querySelectorAll('input[type="checkbox"]');
                    
                    if (this.checked) {
                        // 选中"所有订阅源"时，隐藏具体订阅源选项
                        specificSources.style.display = 'none';
                        specificCheckboxes.forEach(checkbox => {
                            checkbox.checked = false;
                        });
                    } else {
                        // 取消"所有订阅源"时，显示具体订阅源选项
                        specificSources.style.display = 'block';
                    }
                });
            }
            
            // 编辑卡密模态框中的订阅源选择
            const editCardAllSourcesCheckbox = document.getElementById('edit-card-all-sources');
            if (editCardAllSourcesCheckbox) {
                editCardAllSourcesCheckbox.addEventListener('change', function() {
                    const specificSources = document.getElementById('edit-card-specific-sources');
                    const specificCheckboxes = specificSources.querySelectorAll('input[type="checkbox"]');
                    
                    if (this.checked) {
                        // 选中"所有订阅源"时，隐藏具体订阅源选项
                        specificSources.style.display = 'none';
                        specificCheckboxes.forEach(checkbox => {
                            checkbox.checked = false;
                        });
                    } else {
                        // 取消"所有订阅源"时，显示具体订阅源选项
                        specificSources.style.display = 'block';
                    }
                });
            }
        }
    </script>
</body>
</html> 
</html> 