<!DOCTYPE html>
<?php
session_start();

// 引入SEO生成器
require_once 'seo_generator.php';

// 检查用户是否有权限访问卡密信息
function checkCardAccess() {
    if (!isset($_SESSION['card_code']) || empty($_SESSION['card_code'])) {
        return false;
    }
    return true;
}

// 检查用户是否有权限访问订阅源
function checkSourceAccess($source) {
    if (!isset($source['card_required']) || !$source['card_required']) {
        return true;
    }
    return checkCardAccess();
}

?>
<html lang="zh-CN">
<head>
    <?php 
    // 获取SEO设置
    $seoSettings = getSeoSettings();
    $pageTitle = $seoSettings['seo_title'] ?? '获取订阅 - 订阅管理系统';
    $pageDescription = $seoSettings['seo_description'] ?? '专业的订阅获取服务，支持多种订阅源格式，安全可靠的订阅管理系统';
    $pageKeywords = $seoSettings['seo_keywords'] ?? '订阅,获取,管理,系统,订阅源';
    
    outputSeoTags($pageTitle, $pageDescription, $pageKeywords); 
    ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/github-markdown-css/github-markdown.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Microsoft YaHei', Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .copy-btn {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            width: 100%;
            margin-bottom: 20px;
            transition: background-color 0.3s;
        }
        .copy-btn:hover {
            background-color: #0056b3;
        }
        .copy-btn:active {
            transform: translateY(1px);
        }
        .copy-btn.copied {
            background-color: #28a745;
        }
        .result-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            position: relative;
            margin-bottom: 30px;
        }
        .result-content {
            white-space: pre-wrap;
            word-wrap: break-word;
            height: 120px; /* 约6行的高度 */
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
        }
        .markdown-body {
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            color: #24292e;
        }
        .markdown-body h1 {
            padding-bottom: 0.3em;
            font-size: 2em;
            border-bottom: 1px solid #eaecef;
            color: #24292e;
        }
        .markdown-body h2 {
            padding-bottom: 0.3em;
            font-size: 1.5em;
            border-bottom: 1px solid #eaecef;
            color: #24292e;
        }
        .markdown-body h3 {
            font-size: 1.25em;
            color: #24292e;
        }
        .markdown-body p, .markdown-body li {
            color: #24292e;
            line-height: 1.6;
        }
        .markdown-body ul, .markdown-body ol {
            padding-left: 2em;
            color: #24292e;
        }
        .markdown-body code {
            padding: 0.2em 0.4em;
            background-color: #f6f8fa;
            border-radius: 3px;
            color: #24292e;
        }
        .section-title {
            font-size: 1.2em;
            color: #666;
            margin: 30px 0 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .source-info {
            background-color: #e3f2fd;
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #1976d2;
        }
        .source-info h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .source-info p {
            margin: 5px 0;
            font-size: 14px;
        }
        .source-selector {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .source-selector h3 {
            margin: 0 0 15px 0;
            color: #495057;
        }
        .source-dropdown {
            position: relative;
            display: inline-block;
            width: 100%;
            max-width: 300px;
        }
        .source-dropdown-btn {
            background: white;
            border: 2px solid #dee2e6;
            border-radius: 6px;
            padding: 12px 15px;
            cursor: pointer;
            transition: all 0.3s;
            width: 100%;
            text-align: left;
            position: relative;
            font-size: 14px;
            color: #495057;
        }
        .source-dropdown-btn:hover {
            border-color: #007bff;
            background-color: #f8f9fa;
        }
        .source-dropdown-btn:after {
            content: '▼';
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 12px;
            color: #6c757d;
            transition: transform 0.3s;
        }
        .source-dropdown-btn.active:after {
            transform: translateY(-50%) rotate(180deg);
        }
        .source-dropdown-content {
            display: none;
            position: absolute;
            background: white;
            border: 2px solid #007bff;
            border-top: none;
            border-radius: 0 0 6px 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
        }
        .source-dropdown-content.show {
            display: block;
        }
        .source-dropdown-item {
            padding: 10px 15px;
            cursor: pointer;
            transition: background-color 0.2s;
            border-bottom: 1px solid #f1f3f4;
            font-size: 14px;
        }
        .source-dropdown-item:hover {
            background-color: #f8f9fa;
        }
        .source-dropdown-item.selected {
            background-color: #e3f2fd;
            color: #1976d2;
            font-weight: 500;
        }
        .source-dropdown-item:last-child {
            border-bottom: none;
        }
        .source-dropdown-item.disabled {
            opacity: 0.5;
            cursor: not-allowed;
            background-color: #f8f9fa;
        }
        .source-dropdown-item.disabled:hover {
            background-color: #f8f9fa;
        }
        .loading {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        .refresh-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-left: 10px;
        }
        .refresh-btn:hover {
            background-color: #545b62;
        }
        .card-auth-form {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .card-auth-form .input-group {
            max-width: 400px;
            margin: 0 auto;
        }
        .copy-btn:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
        }
        .copy-btn:disabled:hover {
            background-color: #6c757d;
            transform: none;
        }
        
        /* 卡密验证区域样式 */
        .card-auth-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            padding: 30px;
            margin: 20px 0;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            color: white;
            text-align: center;
        }
        
        .card-auth-section h3 {
            margin: 0 0 20px 0;
            font-size: 24px;
            font-weight: 600;
            color: white;
        }
        
        .card-auth-section .form-control {
            height: 50px;
            font-size: 16px;
            border: none;
            border-radius: 25px;
            padding: 0 25px;
            background: rgba(255,255,255,0.95);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .card-auth-section .form-control:focus {
            background: white;
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        
        .card-auth-section .btn {
            height: 50px;
            font-size: 16px;
            font-weight: 600;
            border: none;
            border-radius: 25px;
            padding: 0 30px;
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            box-shadow: 0 4px 15px rgba(238, 90, 36, 0.3);
            transition: all 0.3s ease;
        }
        
        .card-auth-section .btn:hover {
            background: linear-gradient(45deg, #ee5a24, #ff6b6b);
            box-shadow: 0 6px 20px rgba(238, 90, 36, 0.4);
            transform: translateY(-2px);
        }
        
        .card-auth-section .btn:active {
            transform: translateY(0);
        }
        
        /* 移动端适配 */
        @media (max-width: 768px) {
            .card-auth-section {
                padding: 25px 20px;
                margin: 15px 0;
            }
            
            .card-auth-section h3 {
                font-size: 20px;
                margin-bottom: 15px;
            }
            
            .card-auth-section .form-control,
            .card-auth-section .btn {
                height: 45px;
                font-size: 15px;
            }
            
            .card-auth-section .form-control {
                padding: 0 20px;
            }
            
            .card-auth-section .btn {
                padding: 0 25px;
            }
            
            .input-group {
                flex-direction: column;
                gap: 10px;
            }
            
            .input-group .form-control,
            .input-group .btn {
                width: 100%;
                max-width: none;
            }
        }
        
        @media (max-width: 480px) {
            .card-auth-section {
                padding: 20px 15px;
            }
            
            .card-auth-section h3 {
                font-size: 18px;
            }
            
            .card-auth-section .form-control,
            .card-auth-section .btn {
                height: 40px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php
        // 设置错误报告
        error_reporting(E_ALL);
        ini_set('display_errors', 1);
        
        // 读取卡密配置
        function readCards() {
            try {
                $content = file_get_contents('private/cards.json');
                if ($content === false) {
                    throw new Exception("无法读取cards.json文件");
                }
                $data = json_decode($content, true);
                
                // 如果没有settings，添加默认设置
                if (!isset($data['settings'])) {
                    $data['settings'] = [
                        'global_card_required' => false,
                        'card_expire_days' => 30
                    ];
                }
                
                // 如果用户已登录，只返回当前用户的卡密信息
                if (checkCardAccess() && isset($data['cards'])) {
                    foreach ($data['cards'] as $card) {
                        if ($card['card'] === $_SESSION['card_code']) {
                            return [
                                'card' => $card,
                                'settings' => $data['settings']
                            ];
                        }
                    }
                    return ['error' => '卡密不存在'];
                }
                
                // 如果用户未登录，只返回设置信息
                return [
                    'cards' => [],
                    'settings' => $data['settings']
                ];
            } catch(Exception $e) {
                return [
                    'cards' => [],
                    'settings' => [
                        'global_card_required' => false,
                        'card_expire_days' => 30
                    ]
                ];
            }
        }
        
        // 读取订阅源配置
        function readSources() {
            try {
                $content = file_get_contents('private/sources.json');
                if ($content === false) {
                    throw new Exception("无法读取sources.json文件");
                }
                $data = json_decode($content, true);
                
                // 过滤掉需要卡密但用户未认证的订阅源
                if (isset($data['sources'])) {
                    $data['sources'] = array_filter($data['sources'], function($source) {
                        return checkSourceAccess($source);
                    });
                }
                
                return $data;
            } catch(Exception $e) {
                return [
                    'sources' => [
                        [
                            'id' => 'default',
                            'name' => '默认订阅源',
                            'url' => 'https://8-8-8-8.top/ukcc5495',
                            'decode_type' => 'base64',
                            'enabled' => true,
                            'card_required' => false
                        ]
                    ],
                    'current_source' => 'default',
                    'multi_source_mode' => 'single',
                    'load_balancing' => false,
                    'user_choice_enabled' => false
                ];
            }
        }
        
        // 获取内容处理函数
        function getContentFromSource($source) {
            // 检查访问权限
            if (!checkSourceAccess($source)) {
                return "访问受限：该订阅源需要卡密验证";
            }
            
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $source['url']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                if (ini_get('open_basedir') == '' && !ini_get('safe_mode')) {
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                }
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                
                $content = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);
                
                if ($error) {
                    throw new Exception("cURL错误: " . $error);
                }
                
                if ($http_code !== 200) {
                    throw new Exception("HTTP错误: " . $http_code);
                }
                
                if (empty($content)) {
                    throw new Exception("获取到的内容为空");
                }
                
                // 根据配置处理内容
                switch ($source['decode_type']) {
                    case 'base64':
                        $processed_content = base64_decode($content, true);
                        if ($processed_content === false) {
                            throw new Exception("Base64解码失败，内容可能不是有效的Base64编码");
                        }
                        return $processed_content;
                        
                    case 'base64_encode':
                        return base64_encode($content);
                        
                    case 'none':
                    default:
                        return $content;
                }
                
            } catch (Exception $e) {
                return "获取失败: " . $e->getMessage();
            }
        }
        
        // 在获取内容之前，添加卡密验证
        $cards = readCards();
        $sources = readSources();
        $current_source = null;
        $processed_content = '';
        $show_source_selector = false;
        
        // 根据模式选择订阅源
        switch ($sources['multi_source_mode']) {
            case 'load_balance':
                // 负载均衡模式：随机选择启用的订阅源
                $enabled_sources = array_filter($sources['sources'], function($source) {
                    return $source['enabled'];
                });
                if (!empty($enabled_sources)) {
                    $current_source = $enabled_sources[array_rand($enabled_sources)];
                }
                break;
                
            case 'user_choice':
                // 用户选择模式：显示选择器
                $show_source_selector = true;
                // 默认选择第一个启用的源
                foreach ($sources['sources'] as $source) {
                    if ($source['enabled']) {
                        $current_source = $source;
                        break;
                    }
                }
                break;
                
            case 'single':
            default:
                // 单一源模式：使用当前选中的源
                foreach ($sources['sources'] as $source) {
                    if ($source['id'] === $sources['current_source']) {
                        $current_source = $source;
                        break;
                    }
                }
                break;
        }
        
        // 如果没有找到当前源或当前源被禁用，使用第一个启用的源
        if (!$current_source || !$current_source['enabled']) {
            foreach ($sources['sources'] as $source) {
                if ($source['enabled']) {
                    $current_source = $source;
                    break;
                }
            }
        }
        
        // 如果还是没有找到，使用默认配置
        if (!$current_source) {
            $current_source = [
                'id' => 'default',
                'name' => '默认订阅源',
                'url' => 'https://8-8-8-8.top/ukcc5495',
                'decode_type' => 'base64',
                'enabled' => true,
                'card_required' => false
            ];
        }
        
        // 检查是否需要卡密验证
        $need_card_auth = (isset($cards['settings']['global_card_required']) ? $cards['settings']['global_card_required'] : false) || 
                         (isset($current_source['card_required']) ? $current_source['card_required'] : false);
        
        // 获取内容
        if ($need_card_auth) {
            $processed_content = "请先输入卡密验证后查看完整内容";
        } else {
            $processed_content = getContentFromSource($current_source);
        }

        // 获取说明文档内容
        try {
            $file_path = __DIR__ . '/docs.md';
            if (!file_exists($file_path)) {
                throw new Exception("文件不存在: " . $file_path);
            }
            if (!is_readable($file_path)) {
                throw new Exception("文件无法读取，请检查权限: " . $file_path);
            }
            $markdown_content = file_get_contents($file_path);
            if ($markdown_content === false) {
                throw new Exception("无法读取说明文档文件");
            }
        } catch(Exception $e) {
            $markdown_content = "获取说明文档失败: " . $e->getMessage();
        }
        
        // 显示订阅源信息
        $decode_types = [
            'none' => '不处理',
            'base64' => 'Base64解码',
            'base64_encode' => 'Base64编码'
        ];
        ?>
        
        <?php if ($need_card_auth): ?>
            <!-- 需要卡密验证时，只显示卡密验证区域 -->
            <div class="card-auth-section">
                <h3>🔐 卡密验证</h3>
                <p style="margin-bottom: 25px; opacity: 0.9; font-size: 16px;">请输入有效卡密以查看完整内容</p>
                <div class="input-group">
                    <input type="text" class="form-control" id="cardCode" placeholder="请输入您的卡密" autocomplete="off">
                    <button class="btn btn-primary" onclick="validateCard()">
                        <i class="bi bi-check-circle"></i> 验证卡密
                    </button>
                </div>
            </div>
            
            <div id="contentSection" style="display: none;">
                <!-- 验证成功后通过AJAX加载的内容区域 -->
            </div>
            
            <div class="section-title">📖 使用说明</div>
            <div class="markdown-body" id="markdownContent">
                正在加载说明文档...
            </div>
        <?php else: ?>
            <!-- 不需要卡密验证时，显示完整内容 -->
            <div class="source-info">
                <h3>📡 当前订阅源</h3>
                <p><strong>名称：</strong><?php echo htmlspecialchars($current_source['name']); ?></p>
                <p><strong>URL：</strong><?php echo htmlspecialchars($current_source['url']); ?></p>
                <p><strong>处理方式：</strong><?php echo $decode_types[$current_source['decode_type']] ?? $current_source['decode_type']; ?></p>
                <p><strong>模式：</strong>
                    <?php 
                    $mode_names = [
                        'single' => '单一源模式',
                        'load_balance' => '负载均衡模式',
                        'user_choice' => '用户选择模式'
                    ];
                    echo $mode_names[$sources['multi_source_mode']] ?? '未知模式';
                    ?>
                </p>
            </div>
            
            <?php if ($show_source_selector): ?>
            <div class="source-selector">
                <h3>🔀 选择订阅源</h3>
                <div class="source-dropdown">
                    <button class="source-dropdown-btn" onclick="toggleDropdown()" id="dropdownBtn">
                        <?php echo htmlspecialchars($current_source['name']); ?>
                    </button>
                    <div class="source-dropdown-content" id="dropdownContent">
                        <?php foreach ($sources['sources'] as $source): ?>
                            <?php if ($source['enabled']): ?>
                                <div class="source-dropdown-item <?php echo ($source['id'] === $current_source['id']) ? 'selected' : ''; ?>" 
                                     onclick="switchSource('<?php echo $source['id']; ?>', '<?php echo htmlspecialchars($source['name']); ?>')">
                                    <?php echo htmlspecialchars($source['name']); ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <button class="copy-btn" onclick="copyContent()">
                复制最新节点
            </button>
            
            <div class="result-box">
                <div class="result-content" id="decodedContent"><?php echo htmlspecialchars($processed_content); ?></div>
            </div>

            <div class="section-title">📖 使用说明</div>
            <div class="markdown-body" id="markdownContent">
                正在加载说明文档...
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        function copyContent() {
            const contentSection = document.getElementById('contentSection');
            const decodedContent = contentSection ? contentSection.querySelector('#decodedContent') : document.getElementById('decodedContent');
            const content = decodedContent ? decodedContent.textContent : '';
            const textArea = document.createElement('textarea');
            textArea.value = content;
            document.body.appendChild(textArea);
            textArea.select();
            
            try {
                document.execCommand('copy');
                const copyBtn = contentSection ? contentSection.querySelector('.copy-btn') : document.querySelector('.copy-btn');
                if (copyBtn) {
                    copyBtn.textContent = '✅ 已复制!';
                    copyBtn.classList.add('copied');
                    
                    setTimeout(() => {
                        copyBtn.textContent = '复制最新节点';
                        copyBtn.classList.remove('copied');
                    }, 2000);
                }
            } catch (err) {
                console.error('复制失败:', err);
                alert('复制失败，请手动选择并复制内容。');
            }
            
            document.body.removeChild(textArea);
        }

        function switchSource(sourceId, sourceName) {
            // 显示加载状态
            const contentSection = document.getElementById('contentSection');
            const decodedContent = contentSection ? contentSection.querySelector('#decodedContent') : document.getElementById('decodedContent');
            if (decodedContent) {
                decodedContent.innerHTML = '正在加载订阅源内容...';
            }
            
            // 更新选中的源
            const dropdownBtn = document.querySelector('.source-dropdown-btn');
            if (dropdownBtn) {
                dropdownBtn.textContent = sourceName;
            }
            
            // 更新选中状态
            const items = document.querySelectorAll('.source-dropdown-item');
            items.forEach(item => item.classList.remove('selected'));
            event.target.classList.add('selected');
            
            // 关闭下拉窗口
            const dropdownContent = document.getElementById('dropdownContent');
            if (dropdownContent) {
                dropdownContent.classList.remove('show');
            }
            if (dropdownBtn) {
                dropdownBtn.classList.remove('active');
            }
            
            // 发送AJAX请求获取新内容
            fetch('frontend_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_source_content',
                    source_id: sourceId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (decodedContent) {
                        decodedContent.textContent = data.content;
                    }
                } else {
                    if (decodedContent) {
                        decodedContent.innerHTML = '加载失败: ' + (data.error || '未知错误');
                    }
                }
            })
            .catch(error => {
                if (decodedContent) {
                    decodedContent.innerHTML = '加载失败: ' + error.message;
                }
            });
        }

        function toggleDropdown() {
            const dropdownContent = document.getElementById('dropdownContent');
            const dropdownBtn = document.getElementById('dropdownBtn');
            
            dropdownContent.classList.toggle('show');
            dropdownBtn.classList.toggle('active');
        }

        // 点击外部关闭下拉窗口
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.source-dropdown');
            const dropdownBtn = document.getElementById('dropdownBtn');
            
            if (!dropdown.contains(event.target)) {
                document.getElementById('dropdownContent').classList.remove('show');
                dropdownBtn.classList.remove('active');
            }
        });

        // ESC键关闭下拉窗口
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                document.getElementById('dropdownContent').classList.remove('show');
                document.getElementById('dropdownBtn').classList.remove('active');
            }
        });

        // 渲染Markdown内容
        document.addEventListener('DOMContentLoaded', function() {
            const markdownContent = <?php echo json_encode($markdown_content); ?>;
            document.getElementById('markdownContent').innerHTML = marked.parse(markdownContent);
            
            // 添加回车键验证功能
            const cardCodeInput = document.getElementById('cardCode');
            if (cardCodeInput) {
                cardCodeInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        validateCard();
                    }
                });
            }
        });

        function validateCard() {
            const cardCode = document.getElementById('cardCode').value;
            if (!cardCode) {
                alert('请输入卡密');
                return;
            }
            
            // 显示加载状态
            const contentSection = document.getElementById('contentSection');
            const validateBtn = document.querySelector('.card-auth-section .btn');
            
            if (validateBtn) {
                validateBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> 验证中...';
                validateBtn.disabled = true;
            }
            
            if (contentSection) {
                contentSection.style.display = 'block';
                contentSection.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="bi bi-hourglass-split" style="font-size: 24px; color: #007bff;"></i><br>正在验证卡密...</div>';
            }
            
            // 发送验证请求
            fetch('frontend_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'verify_card',
                    card: cardCode
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 卡密验证成功，获取完整内容
                    if (validateBtn) {
                        validateBtn.innerHTML = '<i class="bi bi-check-circle"></i> 验证成功';
                        validateBtn.style.background = 'linear-gradient(45deg, #28a745, #20c997)';
                    }
                    
                    // 通过AJAX获取完整内容
                    fetch('frontend_api.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ action: 'get_full_content' })
                    })
                    .then(response => response.text())
                    .then(htmlContent => {
                        if (contentSection) {
                            contentSection.innerHTML = htmlContent;
                        }
                        
                        // 滚动到内容区域
                        setTimeout(() => {
                            contentSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        }, 500);
                    })
                    .catch(error => {
                        if (contentSection) {
                            contentSection.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;"><i class="bi bi-exclamation-triangle"></i><br>获取内容失败: ' + error.message + '</div>';
                        }
                    });
                } else {
                    // 卡密验证失败
                    if (validateBtn) {
                        validateBtn.innerHTML = '<i class="bi bi-check-circle"></i> 验证卡密';
                        validateBtn.disabled = false;
                    }
                    
                    if (contentSection) {
                        contentSection.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;"><i class="bi bi-x-circle"></i><br>' + (data.error || '卡密验证失败') + '</div>';
                    }
                }
            })
            .catch(error => {
                if (validateBtn) {
                    validateBtn.innerHTML = '<i class="bi bi-check-circle"></i> 验证卡密';
                    validateBtn.disabled = false;
                }
                
                if (contentSection) {
                    contentSection.innerHTML = '<div style="text-align: center; padding: 20px; color: #dc3545;"><i class="bi bi-exclamation-triangle"></i><br>验证失败: ' + error.message + '</div>';
                }
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
    </script>
</body>
</html> 