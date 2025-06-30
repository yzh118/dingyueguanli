# 开发文档

## 📋 目录

- [技术架构](#技术架构)
- [API文档](#api文档)
- [数据库结构](#数据库结构)
- [开发环境搭建](#开发环境搭建)
- [代码规范](#代码规范)
- [扩展开发](#扩展开发)
- [测试指南](#测试指南)
- [部署指南](#部署指南)

## 🏗️ 技术架构

### 整体架构

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   前端页面      │    │   后台管理      │    │   API接口       │
│  (获取订阅.php) │    │   (admin.php)   │    │  (api.php)      │
└─────────────────┘    └─────────────────┘    └─────────────────┘
         │                       │                       │
         └───────────────────────┼───────────────────────┘
                                 │
                    ┌─────────────────┐
                    │   数据存储      │
                    │  (JSON文件)     │
                    └─────────────────┘
```

### 技术栈

- **后端**: PHP 7.4+
- **前端**: HTML5, CSS3, JavaScript (ES6+)
- **UI框架**: Bootstrap 5.1.3
- **图标**: Bootstrap Icons 1.7.2
- **数据存储**: JSON文件
- **SEO**: 自定义SEO生成器

### 核心模块

1. **认证模块** (`card_auth.php`)
   - 卡密验证
   - 用户组权限检查
   - Session管理

2. **订阅源模块** (`get_source_content.php`)
   - 订阅源内容获取
   - 多种格式解码
   - 负载均衡

3. **管理模块** (`admin.php`)
   - 后台管理界面
   - 数据统计
   - 配置管理

4. **API模块** (`api.php`)
   - RESTful API
   - 数据CRUD操作
   - 权限验证

## 📚 API文档

### 认证相关

#### 卡密验证
```
POST /card_auth.php
Content-Type: application/json

{
    "card": "卡密代码",
    "source_id": "订阅源ID"
}

Response:
{
    "success": true,
    "message": "验证成功",
    "user_group": "用户组ID",
    "permissions": {...}
}
```

#### 获取订阅内容
```
POST /frontend_api.php
Content-Type: application/json

{
    "action": "get_content",
    "card": "卡密代码",
    "source_id": "订阅源ID"
}

Response:
{
    "success": true,
    "content": "订阅内容",
    "source_info": {...}
}
```

### 管理后台API

#### 获取统计数据
```
GET /api.php?action=get_stats

Response:
{
    "success": true,
    "stats": {
        "total_sources": 5,
        "active_sources": 3,
        "total_cards": 100,
        "active_cards": 85,
        "current_mode": "single",
        "global_card_required": false
    }
}
```

#### 添加订阅源
```
POST /api.php
Content-Type: application/json

{
    "action": "add_source",
    "name": "订阅源名称",
    "url": "订阅源URL",
    "decode_type": "base64",
    "enabled": true,
    "card_required": false
}
```

#### 添加卡密
```
POST /api.php
Content-Type: application/json

{
    "action": "add_card",
    "card": "卡密代码",
    "name": "卡密名称",
    "user_group": "用户组ID",
    "allowed_sources": ["all"],
    "expire_days": 30
}
```

#### 保存SEO设置
```
POST /api.php
Content-Type: application/json

{
    "action": "save_seo_settings",
    "seo_title": "网站标题",
    "seo_description": "网站描述",
    "seo_keywords": "关键词",
    "og_title": "OG标题",
    "og_description": "OG描述",
    "favicon_url": "图标URL"
}
```

## 🗄️ 数据库结构

### JSON文件结构

#### admin_config.json
```json
{
    "admin_username": "admin",
    "admin_password": "123456",
    "admin_path": "admin",
    "security_settings": {
        "session_timeout": 3600,
        "max_login_attempts": 5,
        "lockout_duration": 300
    }
}
```

#### sources.json
```json
{
    "sources": [
        {
            "id": "unique_id",
            "name": "订阅源名称",
            "url": "订阅源URL",
            "decode_type": "base64",
            "enabled": true,
            "card_required": false
        }
    ],
    "current_source": "default",
    "multi_source_mode": "single",
    "load_balancing": false,
    "user_choice_enabled": false
}
```

#### cards.json
```json
{
    "cards": [
        {
            "id": "unique_id",
            "card": "卡密代码",
            "name": "卡密名称",
            "user_group": "用户组ID",
            "allowed_sources": ["all"],
            "status": "active",
            "created_at": "2024-01-01 00:00:00",
            "expires_at": null,
            "used_at": null,
            "used_by": null
        }
    ],
    "user_groups": [
        {
            "id": "unique_id",
            "name": "用户组名称",
            "description": "用户组描述",
            "permissions": {
                "max_daily_requests": 100,
                "allowed_sources": ["all"]
            },
            "created_at": "2024-01-01 00:00:00"
        }
    ],
    "settings": {
        "global_card_required": false,
        "card_expire_days": 30,
        "default_user_group": "default"
    },
    "seo_settings": {
        "seo_title": "网站标题",
        "seo_description": "网站描述",
        "seo_keywords": "关键词",
        "og_title": "OG标题",
        "og_description": "OG描述",
        "favicon_url": "图标URL"
    }
}
```

## 🛠️ 开发环境搭建

### 环境要求

```bash
# PHP扩展
php-json
php-curl (推荐)
php-mbstring
php-openssl

# Web服务器
Apache 2.4+ 或 Nginx 1.18+
```

### 本地开发

1. **克隆项目**
   ```bash
   git clone https://github.com/your-username/subscription-manager.git
   cd subscription-manager
   ```

2. **配置Web服务器**
   ```bash
   # Apache (httpd.conf)
   DocumentRoot "/path/to/subscription-manager"
   
   # Nginx (nginx.conf)
   root /path/to/subscription-manager;
   index 获取订阅.php;
   ```

3. **设置权限**
   ```bash
   chmod 755 private/
   chmod 644 private/*.json
   ```

4. **访问测试**
   ```
   http://localhost/获取订阅.php
   http://localhost/admin.php
   ```

### 开发工具推荐

- **IDE**: VS Code, PHPStorm
- **调试**: Xdebug
- **版本控制**: Git
- **API测试**: Postman, Insomnia

## 📝 代码规范

### PHP代码规范

```php
<?php
// 文件头部注释
/**
 * 文件名: example.php
 * 描述: 功能描述
 * 作者: 作者名
 * 日期: 2024-01-01
 */

// 命名规范
class SubscriptionManager {}  // 类名使用大驼峰
function getUserInfo() {}     // 函数名使用小驼峰
$userName = '';              // 变量名使用小驼峰
const MAX_RETRY = 3;         // 常量使用大写下划线

// 错误处理
try {
    $result = someFunction();
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    return ['error' => '操作失败'];
}

// 返回格式统一
return [
    'success' => true,
    'data' => $result,
    'message' => '操作成功'
];
```

### JavaScript代码规范

```javascript
// 函数命名
function loadUserData() {}    // 小驼峰
const API_BASE_URL = '';     // 常量大写下划线

// 异步处理
async function fetchData() {
    try {
        const response = await fetch('/api.php');
        const data = await response.json();
        return data;
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

// 错误处理
function showAlert(message, type = 'info') {
    // 实现代码
}
```

### CSS代码规范

```css
/* 类名使用kebab-case */
.subscription-card {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* 响应式设计 */
@media (max-width: 768px) {
    .subscription-card {
        margin: 10px 0;
    }
}
```

## 🔧 扩展开发

### 添加新的订阅源格式

1. **在 `get_source_content.php` 中添加解码函数**
   ```php
   function decodeCustomFormat($content) {
       // 自定义解码逻辑
       return $decodedContent;
   }
   ```

2. **在 `api.php` 中添加格式选项**
   ```php
   function getDecodeTypeText($type) {
       $types = [
           'none' => '不处理',
           'base64' => 'Base64解码',
           'custom' => '自定义格式'  // 新增
       ];
       return $types[$type] ?? $type;
   }
   ```

3. **在前端添加选项**
   ```html
   <option value="custom">自定义格式</option>
   ```

### 添加新的权限类型

1. **修改用户组权限结构**
   ```php
   $permissions = [
       'max_daily_requests' => 100,
       'allowed_sources' => ['all'],
       'custom_permission' => true  // 新增权限
   ];
   ```

2. **在验证逻辑中添加检查**
   ```php
   if (!$permissions['custom_permission']) {
       return ['error' => '权限不足'];
   }
   ```

### 添加新的API接口

1. **在 `api.php` 中添加新的case**
   ```php
   case 'custom_action':
       // 处理逻辑
       $result = processCustomAction($input);
       echo json_encode($result);
       break;
   ```

2. **添加相应的前端调用**
   ```javascript
   async function customAction() {
       const response = await fetch('api.php', {
           method: 'POST',
           headers: { 'Content-Type': 'application/json' },
           body: JSON.stringify({ action: 'custom_action' })
       });
       return await response.json();
   }
   ```

## 🧪 测试指南

### 单元测试

创建测试文件 `tests/UnitTest.php`:

```php
<?php
class UnitTest {
    public function testCardValidation() {
        $card = 'test_card_123';
        $result = validateCard($card);
        $this->assertTrue($result['success']);
    }
    
    public function testSourceDecoding() {
        $content = base64_encode('test content');
        $decoded = decodeBase64($content);
        $this->assertEquals('test content', $decoded);
    }
}
```

### 集成测试

```php
<?php
// 测试完整流程
function testCompleteFlow() {
    // 1. 添加订阅源
    $sourceResult = addSource([
        'name' => '测试源',
        'url' => 'https://example.com/test',
        'decode_type' => 'base64'
    ]);
    
    // 2. 添加卡密
    $cardResult = addCard([
        'card' => 'test123',
        'name' => '测试卡密'
    ]);
    
    // 3. 验证卡密
    $authResult = validateCard('test123');
    
    // 4. 获取内容
    $contentResult = getContent('test123', $sourceResult['id']);
    
    return [
        'source' => $sourceResult,
        'card' => $cardResult,
        'auth' => $authResult,
        'content' => $contentResult
    ];
}
```

### 性能测试

```php
<?php
// 压力测试
function performanceTest() {
    $startTime = microtime(true);
    
    for ($i = 0; $i < 1000; $i++) {
        validateCard('test_card_' . $i);
    }
    
    $endTime = microtime(true);
    $duration = $endTime - $startTime;
    
    return [
        'requests' => 1000,
        'duration' => $duration,
        'rps' => 1000 / $duration
    ];
}
```

## 🚀 部署指南

### 生产环境部署

1. **服务器准备**
   ```bash
   # 更新系统
   sudo apt update && sudo apt upgrade
   
   # 安装必要软件
   sudo apt install nginx php-fpm php-json php-curl
   ```

2. **配置Nginx**
   ```nginx
   server {
       listen 80;
       server_name example.com;
       root /var/www/subscription-manager;
       
       # 安全配置
       location ~ /private/ {
           deny all;
       }
       
       # PHP配置
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
           fastcgi_index index.php;
           include fastcgi_params;
       }
   }
   ```

3. **设置SSL证书**
   ```bash
   sudo certbot --nginx -d example.com
   ```

4. **配置防火墙**
   ```bash
   sudo ufw allow 80
   sudo ufw allow 443
   sudo ufw allow 22
   sudo ufw enable
   ```

### 监控和日志

```bash
# 查看错误日志
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/php7.4-fpm.log

# 监控系统资源
htop
df -h
free -h
```

### 备份策略

```bash
#!/bin/bash
# backup.sh

DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/subscription-manager"

# 创建备份目录
mkdir -p $BACKUP_DIR

# 备份配置文件
tar -czf $BACKUP_DIR/config_$DATE.tar.gz private/

# 备份代码
tar -czf $BACKUP_DIR/code_$DATE.tar.gz --exclude=private/ .

# 删除7天前的备份
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete
```

## 📞 技术支持

### 获取帮助

- **文档**: 查看本开发文档
- **Issues**: 在GitHub提交Issue
- **讨论**: 参与GitHub Discussions
- **邮件**: 发送邮件到开发团队

### 贡献代码

1. Fork项目
2. 创建功能分支
3. 编写代码和测试
4. 提交Pull Request
5. 等待代码审查

### 版本发布

```bash
# 创建发布标签
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin v1.1.0

# 生成发布包
git archive --format=zip --output=subscription-manager-v1.1.0.zip v1.1.0
```

---

感谢您对项目的贡献！🎉 