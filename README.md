# 订阅管理系统
### 中文-简体|[English](https://github.com/yzh118/dingyueguanli/blob/main/README_EN.md)
一个功能完整、安全可靠的订阅管理系统，支持多种订阅源格式、卡密验证、用户组权限管理等功能。

## 宝塔部署
### 安装准备：
- PHP 7.0 +
- Nginx 1.10 +
本项目部署简单、v1.0.0版本ZIP压缩包体积仅74KB，基本开箱即用，为保护重要JSON文件所以需要修改站点的 Nginx 配置文件，前台文件为 `获取订阅.php` ，所以也要修改默认站点 Nginx 配置文件来将它设置为正确的 index 页面。
### 开始部署
在 `宝塔->网站->PHP` 中添加一个新的站点，PHP 建议选择8.0，实际上7.0 +都可以，但是你要已安装该版本才能选择；完成后点击**确定**以完成添加站点。
接着将压缩包放入网站目录如 `/www/wwwroot/你的站点目录名` ，后解压缩包即完成部署，但还要进行下一步 Nginx 配置操作。
#### Nginx 配置
在 v1.0.0 以后项目压缩包中会存在一个 `nginx.conf.example` 文件，这里面存放着 Nginx 配置模板，比如：
```Nginx
# 将这些配置添加到你的nginx配置文件中的server块内

# 禁止直接访问json文件
location ~* \.json$ {
    deny all;
    return 403;
}

# 保护private目录
location ^~ /private/ {
    deny all;
    return 403;
}

# 保护PHP源文件，只允许指定的入口文件
location ~ ^/(card_auth|get_source_content|install)\.php$ {
    deny all;
    return 403;
}

# 允许访问主要的PHP文件
location ~ ^/(获取订阅|admin|api)\.php$ {
    fastcgi_pass   unix:/var/run/php-fpm.sock;  # 根据你的PHP-FPM配置修改
    fastcgi_index  index.php;
    fastcgi_param  SCRIPT_FILENAME  $document_root$fastcgi_script_name;
    include        fastcgi_params;
}

# 添加安全headers
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "same-origin" always;

# 禁止目录列表
autoindex off;

# 如果访问目录下没有索引文件，返回403
location / {
    try_files $uri $uri/ =403;
} 
```
在 PHP 项目中找到添加的站点->设置->配置文件，这个就是站点的 Nginx 配置文件了，修改操作：
- 对于新手，仅需将它们插入到配置文件的最后一个 `}` 右大括号之上即可。

- 找到全配置文件的第5行，或是`server`块中的第3行，应该能看到 `index` 指令，在其后面找到 `index.php` 将其改为 `获取订阅.php` 即完成配置；如若没有请手动添加。
