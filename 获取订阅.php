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
<!DOCTYPE html>
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
            height: 120px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
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
            box-shadow: 0 2px 4px rgba(33, 150, 243, 0.1);
        }
        .source-info h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #1565c0;
            font-weight: bold;
        }
        .source-info p {
            margin: 8px 0;
            font-size: 14px;
            color: #1976d2;
            line-height: 1.6;
        }
        .source-info a {
            color: #1976d2;
            text-decoration: none;
        }
        .source-info a:hover {
            text-decoration: underline;
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
            border-radius: 6px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 1000;
            width: 100%;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 5px;
        }
        .source-dropdown-content.show {
            display: block;
        }
        .source-dropdown-item {
            padding: 12px 15px;
            cursor: pointer;
            transition: all 0.2s;
            border-bottom: 1px solid #f1f3f4;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: #495057;
        }
        .source-dropdown-item:hover:not(.disabled) {
            background-color: #e3f2fd;
            color: #1976d2;
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
            opacity: 0.7;
            cursor: not-allowed;
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .source-dropdown-item i {
            font-size: 14px;
            color: #dc3545;
        }
        .source-dropdown-item.disabled i {
            color: #6c757d;
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

        .error-message {
            color: #dc3545;
            padding: 15px;
            background-color: #fff;
            border-radius: 6px;
            margin-top: 10px;
            border: 1px solid #dc3545;
        }

        .error-message p {
            margin: 0 0 10px 0;
        }

        .error-message .refresh-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .error-message .refresh-btn:hover {
            background-color: #c82333;
        }

        /* 添加加载动画 */
        @keyframes loading {
            0% { opacity: 0.3; }
            50% { opacity: 1; }
            100% { opacity: 0.3; }
        }

        .loading-text {
            animation: loading 1.5s infinite;
            text-align: center;
            color: #1976d2;
            font-size: 16px;
            padding: 20px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>
        let currentSourceId = null;
        let currentSourceName = null;
        let isCardVerified = <?php echo isset($_SESSION['card_code']) ? 'true' : 'false'; ?>;

        // 验证卡密
        function verifyCard() {
            const cardInput = document.getElementById('cardInput');
            const card = cardInput.value.trim();
            
            if (!card) {
                alert('请输入卡密');
                return;
            }
            
            fetch('frontend_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'verify_card',
                    card: card
                }),
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    const cardSection = document.getElementById('cardSection');
                    if (cardSection) {
                        cardSection.style.display = 'none';
                    }
                    isCardVerified = true;
                    initializeContent();
                } else {
                    throw new Error(data.error || '卡密验证失败');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(error.message);
            });
        }

        // 初始化页面
        document.addEventListener('DOMContentLoaded', function() {
            initializeContent();
            
            // 添加回车键验证功能
            const cardInput = document.getElementById('cardInput');
            if (cardInput) {
                cardInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        verifyCard();
                    }
                });
            }

            // 加载并渲染Markdown内容
            fetch('docs.md')
                .then(response => response.text())
                .then(markdown => {
                    const markdownElement = document.getElementById('markdownContent');
                    if (markdownElement) {
                        // 配置marked选项
                        marked.setOptions({
                            breaks: true,  // 支持GitHub风格的换行
                            gfm: true,     // 启用GitHub风格的Markdown
                            headerIds: true // 为标题添加id
                        });
                        markdownElement.innerHTML = marked.parse(markdown);
                    }
                })
                .catch(error => {
                    console.error('Error loading markdown:', error);
                    const markdownElement = document.getElementById('markdownContent');
                    if (markdownElement) {
                        markdownElement.innerHTML = '加载说明文档失败: ' + error.message;
                    }
                });
        });

        // 初始化内容
        function initializeContent() {
            const contentArea = document.getElementById('contentSection');
            if (!contentArea) {
                console.error('Content section not found');
                return;
            }
            
            contentArea.innerHTML = '<div class="loading-text">正在加载内容...</div>';
            
            // 获取完整内容
            fetch('frontend_api.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'get_full_content'
                }),
                credentials: 'same-origin'
                })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // 更新页面内容
                    contentArea.innerHTML = data.html;
                    // 如果有可用的订阅源
                    if (data.sources && data.sources.length > 0) {
                        currentSourceId = data.sources[0].id;
                        currentSourceName = data.sources[0].name;
                        loadSourceContent(currentSourceId);
                    }
                } else {
                    throw new Error(data.error || '加载内容失败');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentArea.innerHTML = `<div class="error-message">${error.message}</div>`;
            });
        }

        // 加载订阅源内容
        function loadSourceContent(sourceId) {
            const contentElement = document.getElementById('decodedContent');
            if (!contentElement) return;
            
            contentElement.textContent = '正在加载订阅源内容...';
            let fetchOptions = {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            };
            if (typeof sourceId !== 'undefined') {
                fetchOptions.body = JSON.stringify({ source_id: sourceId });
            }
            fetch('get_source_content.php', fetchOptions)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    contentElement.textContent = data.content;
                    // 更新当前源信息
                    if (data.source) {
                        currentSourceId = data.source.id;
                        currentSourceName = data.source.name;
                        // 更新下拉按钮文本（如果在用户选择模式下）
                        const dropdownBtn = document.getElementById('dropdownBtn');
                        if (dropdownBtn) {
                            dropdownBtn.textContent = data.source.name;
                        }
                    }
                } else {
                    throw new Error(data.error || '加载内容失败');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                contentElement.innerHTML = `<div class="error-message">
                    <p>${error.message}</p>
                    <button class="refresh-btn" onclick="loadSourceContent(${sourceId ? `'${sourceId}'` : ''})">
                        <i class="bi bi-arrow-clockwise"></i> 重试
                    </button>
                </div>`;
            });
        }

        // 切换源
        function switchSource(sourceId, sourceName) {
            if (sourceId === currentSourceId) return;
            loadSourceContent(sourceId);
            // 动态更新下拉高亮
            setTimeout(() => {
                const items = document.querySelectorAll('.source-dropdown-item');
                items.forEach(item => {
                    if (item.getAttribute('data-source-id') === sourceId) {
                        item.classList.add('selected');
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }, 100);
        }

        // 复制内容
        function copyContent() {
            const content = document.getElementById('decodedContent');
            if (!content) return;
            
            const text = content.textContent;
            if (!text) {
                alert('没有可复制的内容');
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(() => {
                    const copyBtn = document.querySelector('.copy-btn');
                    copyBtn.classList.add('copied');
                    copyBtn.innerHTML = '<i class="bi bi-check"></i> 复制成功';
                    setTimeout(() => {
                        copyBtn.classList.remove('copied');
                        copyBtn.innerHTML = '<i class="bi bi-clipboard"></i> 复制最新节点';
                    }, 2000);
                }).catch(err => {
                    fallbackCopyTextToClipboard(text);
                });
            } else {
                fallbackCopyTextToClipboard(text);
            }
        }

        function fallbackCopyTextToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
                alert('已复制到剪贴板');
            } catch (err) {
                alert('复制失败，请手动复制');
            }
            document.body.removeChild(textarea);
        }

        // 切换下拉菜单
        function toggleDropdown() {
            const dropdownContent = document.getElementById('dropdownContent');
            const dropdownBtn = document.getElementById('dropdownBtn');
            if (!dropdownContent || !dropdownBtn) return;
            
            const isActive = dropdownContent.classList.contains('show');
            dropdownContent.classList.toggle('show');
            dropdownBtn.classList.toggle('active');
            
            if (!isActive) {
                // 添加点击外部关闭下拉菜单
                document.addEventListener('click', closeDropdown);
            }
        }

        // 关闭下拉菜单
        function closeDropdown(event) {
            const dropdownContent = document.getElementById('dropdownContent');
            const dropdownBtn = document.getElementById('dropdownBtn');
            if (!dropdownContent || !dropdownBtn) return;
            
            if (!event.target.closest('.source-dropdown')) {
                dropdownContent.classList.remove('show');
                dropdownBtn.classList.remove('active');
                document.removeEventListener('click', closeDropdown);
            }
        }

        // 显示卡密认证模态框
        function showCardAuthModal() {
            const cardSection = document.getElementById('cardSection');
            if (cardSection) {
                cardSection.style.display = 'block';
                document.getElementById('cardInput').focus();
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <?php if (!checkCardAccess()): ?>
        <div id="cardSection" class="card-auth-section">
            <h3>请输入卡密以访问完整内容</h3>
            <div class="input-group">
                <input type="text" id="cardInput" class="form-control" placeholder="请输入您的卡密">
                <button onclick="verifyCard()" class="btn">验证卡密</button>
            </div>
        </div>
        <?php endif; ?>

        <div id="contentSection" class="content-section">
            <!-- 内容将通过JavaScript动态加载 -->
        </div>

        <!-- 使用说明文档 -->
        <div class="section-title">📖 使用说明</div>
        <div class="markdown-body" id="markdownContent">
            正在加载说明文档...
        </div>
    </div>
</body>
</html> 