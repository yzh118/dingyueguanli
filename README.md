# 订阅管理系统
### 中文-简体|[English](https://github.com/yzh118/dingyueguanli/blob/main/README_EN.md)
一个订阅管理系统，主要功能及设定介绍（全文省略API）：
1. 前台，对应项目文件`获取订阅.php`，从订阅源URL实时获取数据供用户复制，可进行自定义处理，处理后的内容将呈现在预览框中供用户检验。
2. 后台，对应项目文件`admin.php`，可在后台设置订阅源、卡密、系统设置、SEO设置。
3. 卡密，可在 `后台>卡密管理` 中管理，v1.0.0 版本可设置访问权限、有效期、随机或自定义卡密、名称等内容。
4. 系统设置，可在 `后台>系统设置` 中管理，v1.0.0 版本可修改用户名、密码，设置安全入口、全局卡密验证等设置。
5. 安全入口、多源管理模式，可在 `后台>系统设置` 中管理，在 v1.0.0 版本中此类功能BUG频发，功能不健全、逻辑冲突，所以不建议在 v1.0.0 中进行设置。
6. SEO设置，可在 `后台>SEO设置` 中管理，已较为成熟（不排除有BUG的可能），可设置：
- 基础SEO信息
    - Title
    - Description
    - Keywords
    - Author
    - 站点图标
    - Apple Touch Icon URL
- Open Graph信息
    - OG标题
    - OG描述
    - OG图片
    - OG类型
- 更多测试功能（不完善）
    - Bing 验证码
    - Google 验证码

7. 说明文档，对应项目文件`docs.md`，说明文档≠README，是给用户看的网站指南，由站长管理员在 `后台>说明文档` 中使用Markdown格式语法进行编辑。
8. 重装系统，对应项目文件`install.php`，
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

到这里就算配置完成了，点击下方绿色按钮“保存”即可保存配置文件，如果改废了也没关系，可以在“历史文件”中查看恢复到修改前版本。
### 验证安装
进入 `domain/install.php` 检测订阅管理系统及其依赖是否安装完成，如果后续你的项目文件严重损坏（但是JSON文件完好）可以使用一键下载并重新安装系统。
- 注意！如果JSON文件损坏那么也无法恢复，所以改动系统文件一定要谨慎。
