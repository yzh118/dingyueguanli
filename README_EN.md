# Subscription Management System

A comprehensive, secure, and reliable subscription management system that supports multiple subscription source formats, card key verification, user group permission management, and more.

## ✨ Features

- 🔐 **Secure Authentication**: Card key verification and user group permission management
- 📡 **Multi-Source Support**: Multiple subscription source formats (Base64, plain text, etc.)
- 👥 **User Group Management**: Fine-grained permission control and subscription source access management
- 🎨 **Beautiful Interface**: Modern responsive design with mobile support
- 🔧 **Admin Panel**: Complete management backend with all configuration options
- 📊 **Data Statistics**: Real-time statistics for subscription sources and card key usage
- 🔍 **SEO Optimization**: Complete SEO settings and search engine optimization
- 🛡️ **Security Protection**: Custom admin path to prevent malicious access

## 🚀 Quick Start

### Requirements

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- JSON extension support
- cURL extension support (recommended)

### Installation Steps

1. **Download Source Code**
   ```bash
   git clone https://github.com/your-username/subscription-manager.git
   cd subscription-manager
   ```

2. **Upload to Server**
   Upload the entire directory to your web server root directory

3. **Set Permissions**
   ```bash
   chmod 755 private/
   chmod 644 private/*.json
   ```

4. **Access Installation Page**
   Visit in browser: `http://your-domain.com/install.php`

5. **Complete Installation**
   Follow the installation wizard to complete initial configuration

### Default Login Information

- **Username**: `admin`
- **Password**: `123456`
- **Admin Path**: `admin.php`

⚠️ **Important**: Please change the default password and admin access path immediately after first login!

## 📁 Directory Structure

```
subscription-manager/
├── admin.php                 # Admin management page
├── api.php                   # API interface file
├── 获取订阅.php              # Frontend subscription page
├── frontend_api.php          # Frontend API interface
├── card_auth.php             # Card key verification interface
├── get_source_content.php    # Subscription source content retrieval
├── seo_generator.php         # SEO generator
├── install.php               # Installation wizard
├── robots.txt                # Search engine crawler configuration
├── docs.md                   # Documentation
├── README.md                 # Project description (Chinese)
├── README_EN.md              # Project description (English)
├── DEVELOPER.md              # Developer documentation
├── private/                  # Private data directory
│   ├── admin_config.json     # Admin configuration
│   ├── cards.json           # Card keys and user group data
│   └── sources.json         # Subscription source configuration
└── test_*.php               # Test files
```

## ⚙️ Configuration

### Admin Configuration

Modifiable in backend "System Settings":
- Admin username and password
- Admin security access path
- Support for Chinese usernames and passwords

### Subscription Source Configuration

Supported configuration options:
- **Name**: Display name for subscription source
- **URL**: Subscription source address
- **Processing Method**: Base64 decode, encode, or no processing
- **Status**: Enable or disable
- **Card Key Verification**: Whether card key verification is required

### Card Key Management

- **Validity Period**: -1 means permanent, maximum 3650 days
- **User Group Assignment**: Assign specific user groups to card keys
- **Subscription Source Permissions**: Limit accessible subscription sources for card keys

### SEO Settings

Complete SEO configuration options:
- Website title, description, keywords
- Open Graph information
- Site icon settings
- Search engine verification codes

## 🔧 Advanced Configuration

### Nginx Configuration Example

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/subscription-manager;
    index 获取订阅.php;

    # Security configuration
    location ~ /private/ {
        deny all;
    }

    # PHP configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Static file caching
    location ~* \.(css|js|png|jpg|jpeg|gif|ico)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### Apache Configuration Example

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/subscription-manager
    
    <Directory /path/to/subscription-manager>
        AllowOverride All
        Require all granted
    </Directory>
    
    # Protect private directory
    <Directory /path/to/subscription-manager/private>
        Require all denied
    </Directory>
</VirtualHost>
```

### .htaccess Configuration

```apache
# Protect private directory
<Files "private/*">
    Require all denied
</Files>

# Enable rewrite engine
RewriteEngine On

# Force HTTPS (optional)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

## 🔒 Security Recommendations

1. **Change Default Password**: Change admin password immediately after first login
2. **Custom Admin Path**: Set complex admin access path
3. **Enable HTTPS**: Use SSL certificate to protect data transmission
4. **Regular Backup**: Regularly backup configuration files in `private/` directory
5. **Access Restriction**: Configure firewall to limit unnecessary access
6. **System Updates**: Regularly update PHP and web server versions

## 🐛 Troubleshooting

### Common Issues

**Q: Cannot login to admin panel**
A: Check `private/admin_config.json` file permissions and content

**Q: Cannot retrieve subscription sources**
A: Check if subscription source URLs are accessible and network connection is normal

**Q: Card key verification fails**
A: Check if card key is valid and user group permissions are correct

**Q: SEO settings not working**
A: Check `seo_settings` configuration in `private/cards.json`

### Debug Mode

Visit `config_status.php` to view configuration status:
```
http://your-domain.com/config_status.php
```

Visit `seo_preview.php` to test SEO functionality:
```
http://your-domain.com/seo_preview.php
```

## 📝 Changelog

### v1.0.0 (2024-01-01)
- Initial version release
- Basic subscription source management support
- Card key verification system
- User group permission management
- Complete admin management interface
- SEO optimization features

## 🤝 Contributing

Welcome to submit Issues and Pull Requests!

1. Fork this repository
2. Create feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. Open Pull Request

⭐ If this project helps you, please give us a star! 