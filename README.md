# 订阅管理系统
### 中文-简体|[English](https://github.com/yzh118/dingyueguanli/blob/main/README_EN.md)
一个功能完整、安全可靠的订阅管理系统，支持多种订阅源格式、卡密验证、用户组权限管理等功能。

## ✨ 功能特性

- 🔐 **安全认证**：支持卡密验证和用户组权限管理
- 📡 **多源支持**：支持多种订阅源格式（Base64、明文等）
- 👥 **用户组管理**：细粒度的权限控制和订阅源访问管理
- 🎨 **美观界面**：现代化的响应式设计，支持移动端
- 🔧 **后台管理**：完整的管理后台，支持所有功能配置
- 📊 **数据统计**：实时统计订阅源和卡密使用情况
- 🔍 **SEO优化**：完整的SEO设置和搜索引擎优化
- 🛡️ **安全防护**：自定义后台路径，防止恶意访问

## 🚀 快速开始

### 环境要求

- PHP 7.4 或更高版本
- Web服务器（Apache/Nginx）
- 支持JSON扩展
- 支持cURL扩展（推荐）

### 安装步骤

1. **下载源码**
   ```bash
   git clone https://github.com/your-username/subscription-manager.git
   cd subscription-manager
   ```

2. **上传到服务器**
   将整个目录上传到你的Web服务器根目录

3. **设置权限**
   ```bash
   chmod 755 private/
   chmod 644 private/*.json
   ```

4. **访问安装页面**
   在浏览器中访问：`http://示例：example.com/install.php`

5. **完成安装**
   按照安装向导完成初始配置

### 默认登录信息

- **用户名**: `admin`
- **密码**: `123456`
- **后台路径**: `admin.php`

⚠️ **重要提醒**：首次登录后请立即修改默认密码和后台访问路径！

## 📁 目录结构

```
subscription-manager/
├── admin.php                 # 后台管理页面
├── api.php                   # API接口文件
├── 获取订阅.php              # 前台订阅获取页面
├── frontend_api.php          # 前台API接口
├── card_auth.php             # 卡密验证接口
├── get_source_content.php    # 订阅源内容获取
├── seo_generator.php         # SEO生成器
├── install.php               # 安装向导
├── robots.txt                # 搜索引擎爬虫配置
├── docs.md                   # 说明文档
├── README.md                 # 项目说明（本文件）
├── README_EN.md              # 英文说明
├── DEVELOPER.md              # 开发文档
├── private/                  # 私有数据目录
│   ├── admin_config.json     # 管理员配置
│   ├── cards.json           # 卡密和用户组数据
│   └── sources.json         # 订阅源配置
└── test_*.php               # 测试文件
```

## ⚙️ 配置说明

### 管理员配置

在后台"系统设置"中可以修改：
- 管理员用户名和密码
- 后台安全访问路径
- 支持中文用户名和密码

### 订阅源配置

支持以下配置选项：
- **名称**：订阅源显示名称
- **URL**：订阅源地址
- **处理方式**：Base64解码、编码或不处理
- **状态**：启用或禁用
- **卡密验证**：是否需要卡密验证

### 卡密管理

- **有效期设置**：-1表示永久有效，最高3650天
- **用户组分配**：为卡密分配特定用户组
- **订阅源权限**：限制卡密可访问的订阅源

### SEO设置

完整的SEO配置选项：
- 网站标题、描述、关键词
- Open Graph信息
- 站点图标设置
- 搜索引擎验证码

## 🔧 高级配置

### Nginx配置示例

```nginx
server {
    listen 80;
    server_name 示例：example.com;
    root /path/to/subscription-manager;
    index 获取订阅.php;

    # 安全配置
    location ~ /private/ {
        deny all;
    }

    # PHP配置
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # 静态文件缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Apache配置示例

```apache
<VirtualHost *:80>
    ServerName 示例：example.com
    DocumentRoot /path/to/subscription-manager
    
    <Directory /path/to/subscription-manager>
        AllowOverride All
        Require all granted
    </Directory>
    
    # 保护私有目录
    <Directory /path/to/subscription-manager/private>
        Require all denied
    </Directory>
</VirtualHost>
```

### .htaccess配置

```apache
# 保护私有目录
<Files "private/*">
    Require all denied
</Files>

# 启用重写引擎
RewriteEngine On

# 强制HTTPS（可选）
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 🔒 安全建议

1. **修改默认密码**：首次登录后立即修改管理员密码
2. **自定义后台路径**：设置复杂的后台访问路径
3. **启用HTTPS**：使用SSL证书保护数据传输
4. **定期备份**：定期备份 `private/` 目录下的配置文件
5. **限制访问**：配置防火墙，限制不必要的访问
6. **更新系统**：定期更新PHP和Web服务器版本

## 🐛 故障排除

### 常见问题

**Q: 后台无法登录**
A: 检查 `private/admin_config.json` 文件权限和内容

**Q: 订阅源无法获取**
A: 检查订阅源URL是否可访问，网络连接是否正常

**Q: 卡密验证失败**
A: 检查卡密是否有效，用户组权限是否正确

**Q: SEO设置不生效**
A: 检查 `private/cards.json` 中的 `seo_settings` 配置

### 调试模式

访问 `test_config.php` 查看配置状态：
```
http://示例：example.com/test_config.php
```

访问 `test_seo_simple.php` 测试SEO功能：
```
http://示例：example.com/test_seo_simple.php
```

⭐ 如果这个项目对你有帮助，请给我们一个星标！ 
