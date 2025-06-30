<?php
// 简化版 install.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 1. 环境检测
function check_env() {
    $result = [
        'php_version' => PHP_VERSION,
        'php_version_ok' => version_compare(PHP_VERSION, '7.0.0', '>='),
        'extensions' => [],
        'functions' => [],
        'status' => 'PASS',
        'issues' => []
    ];
    $need_ext = ['curl','json','mbstring','openssl','zip'];
    $need_func = ['file_get_contents','file_put_contents','json_encode','json_decode','curl_init','base64_encode','base64_decode'];
    foreach($need_ext as $ext) {
        $loaded = extension_loaded($ext);
        $result['extensions'][$ext] = $loaded;
        if(!$loaded) {$result['status']='FAIL'; $result['issues'][]="缺少扩展: $ext";}
    }
    foreach($need_func as $func) {
        $exists = function_exists($func);
        $result['functions'][$func] = $exists;
        if(!$exists) {$result['status']='FAIL'; $result['issues'][]="缺少函数: $func()";}
    }
    if(!$result['php_version_ok']) {
        $result['status']='FAIL';
        $result['issues'][] = 'PHP版本过低，需>=7.0.0';
    }
    return $result;
}

// 2. 备份所有JSON文件
function backup_all_json() {
    $dir = __DIR__;
    $backup_dir = $dir.'/backup';
    if(!is_dir($backup_dir)) mkdir($backup_dir,0755,true);
    $zipfile = $backup_dir.'/json_backup_'.date('Ymd_His').'.zip';
    $zip = new ZipArchive();
    if($zip->open($zipfile, ZipArchive::CREATE)!==TRUE) return ['success'=>false,'msg'=>'无法创建zip'];
    $jsons = glob($dir.'/*.json');
    foreach($jsons as $f) $zip->addFile($f,basename($f));
    $zip->close();
    return ['success'=>true,'file'=>basename($zipfile),'count'=>count($jsons)];
}

// 更健壮的递归删除目录
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item == '.' || $item == '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    // 多次尝试删除目录，防止偶发占用
    for ($i=0; $i<3; $i++) {
        if (@rmdir($dir)) return;
        clearstatcache();
        usleep(100000); // 等待0.1秒
    }
}

// 3. 下载并安装
function download_and_install($ver_or_url='', $is_url=false) {
    $dir = __DIR__;
    $backup_dir = $dir.'/backup';
    $jsons = glob($dir.'/*.json');
    $json_backup = [];
    foreach($jsons as $f) $json_backup[basename($f)] = file_get_contents($f);
    $url = $ver_or_url;
    $tmpzip = $dir.'/tmp_install.zip';
    $extract_dir = $dir.'/tmp_extract';
    
    // 使用cURL下载，支持重定向
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // 允许重定向
    curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // 最多允许5次重定向
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 不验证SSL证书
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // 不验证SSL主机
    curl_setopt($ch, CURLOPT_VERBOSE, true); // 启用详细信息
    curl_setopt($ch, CURLOPT_HEADER, true); // 包含响应头
    
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $redirectUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL); // 获取最终URL
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($data, 0, $headerSize);
    $body = substr($data, $headerSize);
    
    curl_close($ch);
    
    if($data === false || $httpCode !== 200) {
        $debug_info = "原始URL: $url\n";
        $debug_info .= "最终URL: $redirectUrl\n";
        $debug_info .= "HTTP状态码: $httpCode\n";
        $debug_info .= "响应头:\n$headers\n";
        $debug_info .= "错误信息: $error\n";
        return ['success'=>false,'msg'=>'下载失败，调试信息：'.$debug_info];
    }
    
    file_put_contents($tmpzip, $body);
    // 解压
    $zip = new ZipArchive();
    if($zip->open($tmpzip)!==TRUE) return ['success'=>false,'msg'=>'解压失败'];
    if(is_dir($extract_dir)) {
        foreach(glob($extract_dir.'/*') as $f) is_dir($f)?rmdir($f):unlink($f);
    } else {
        mkdir($extract_dir,0755,true);
    }
    $zip->extractTo($extract_dir);
    $zip->close();
    // 覆盖文件（不覆盖json）
    foreach(glob($extract_dir.'/*') as $f) {
        $base = basename($f);
        if(strtolower(substr($base,-5))=='.json') continue;
        if(is_dir($f)) continue;
        copy($f, $dir.'/'.$base);
    }
    // 还原JSON
    foreach($json_backup as $name=>$content) {
        file_put_contents($dir.'/'.$name, $content);
    }
    // 校验JSON
    $bad = [];
    foreach(array_keys($json_backup) as $name) {
        $c = @file_get_contents($dir.'/'.$name);
        if($c===false || json_decode($c,true)===null) $bad[] = $name;
    }
    // 清理
    unlink($tmpzip);
    rrmdir($extract_dir);
    if($bad) return ['success'=>false,'msg'=>'以下JSON文件损坏: '.implode(', ',$bad)];
    return ['success'=>true,'msg'=>'安装完成，JSON文件校验通过'];
}

// 4. 自动修复配置/文档
function create_default_config() {
                $default_config = [
                    'sources' => [
                        [
                            'id' => 'default',
                            'name' => '默认订阅源',
                'url' => '',
                            'decode_type' => 'base64',
                            'enabled' => true
                        ]
                    ],
                    'current_source' => 'default',
                    'multi_source_mode' => 'single',
                    'load_balancing' => false,
                    'user_choice_enabled' => false
                ];
    file_put_contents(__DIR__.'/sources.json', json_encode($default_config, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
    return '✅ 默认配置文件创建成功！';
}


// 处理表单
$msg = '';
if(isset($_POST['action'])) {
    switch($_POST['action']) {
        case 'download_and_install':
            $ver = trim($_POST['version']??'');
            // 判断是否为URL
            if (preg_match('/^https?:\/\//i', $ver)) {
                $url = $ver;
            } else if ($ver) {
                $url = "https://8-8-8-8.top/dygl/dygl_".$ver.".zip";
            } else {
                $url = "https://8-8-8-8.top/dygl/dygl.zip";
            }
            $r = download_and_install($url, true);
            $msg = $r['success'] ? '✅ '.$r['msg'] : '❌ '.$r['msg'];
            break;
        case 'create_default_config':
            $msg = create_default_config();
            break;
    }
}
$env = check_env();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>订阅获取系统 - 安装检测</title>
    <style>
        body {font-family:'Microsoft YaHei',Arial,sans-serif;background:#f5f5f5;padding:30px;}
        .container{max-width:700px;margin:0 auto;background:#fff;padding:30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.1);}
        h1{text-align:center;}
        .status{padding:10px 0;text-align:center;}
        .status.pass{color:#28a745;}
        .status.fail{color:#dc3545;}
        .status ul{margin:0;padding:0;list-style:none;}
        .section{margin:30px 0;}
        .btn{background:#007bff;color:#fff;padding:8px 16px;border:none;border-radius:4px;cursor:pointer;margin:5px;}
        .btn:hover{background:#0056b3;}
        .msg{padding:10px;margin:10px 0;border-radius:4px;}
        .msg.success{background:#d4edda;color:#155724;}
        .msg.error{background:#f8d7da;color:#721c24;}
        .info-list{padding-left:20px;}
        label{display:inline-block;width:80px;}
        input[type=text]{width:320px;padding:5px;}
        .error-debug{max-height:300px;overflow:auto;background:#f8f9fa;border:1px solid #ccc;padding:10px;white-space:pre-wrap;word-break:break-all;}
    </style>
</head>
<body>
    <div class="container">
            <h1>🔧 订阅获取系统 - 安装检测</h1>
    <?php if($msg): ?>
        <div class="msg <?php echo strpos($msg,'✅')!==false?'success':'error'; ?>">
            <?php 
            if(strpos($msg,'下载失败，调试信息：')!==false) {
                $parts = explode('下载失败，调试信息：', $msg, 2);
                echo '❌ 下载失败';
                echo '<pre class="error-debug">'.htmlspecialchars($parts[1]).'</pre>';
            } else {
                echo $msg;
            }
            ?>
        </div>
    <?php endif; ?>
    <div class="section">
        <h2>环境检测</h2>
        <div class="status <?php echo strtolower($env['status']); ?>">
            <?php if($env['status']==='PASS'): ?>
                ✅ 环境检测通过
            <?php else: ?>
                ❌ 环境异常
                <ul>
                <?php foreach($env['issues'] as $issue): ?><li><?php echo $issue; ?></li><?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <ul class="info-list">
            <li>PHP版本: <?php echo $env['php_version']; ?> <?php echo $env['php_version_ok']?'✓':'✗'; ?></li>
            <?php foreach($env['extensions'] as $ext=>$ok): ?>
                <li>扩展 <?php echo $ext; ?>: <?php echo $ok?'已安装 ✓':'未安装 ✗'; ?></li>
                    <?php endforeach; ?>
            <?php foreach($env['functions'] as $func=>$ok): ?>
                <li>函数 <?php echo $func; ?>(): <?php echo $ok?'可用 ✓':'不可用 ✗'; ?></li>
                    <?php endforeach; ?>
        </ul>
                </div>
    <div class="section">
        <h2>安装/重装</h2>
        <form method="post" style="display:inline-block;">
            <input type="hidden" name="action" value="download_and_install">
            <label>版本号或URL:</label><input type="text" name="version" placeholder="如v1.0.0 或 https://..."> 
            <button type="submit" class="btn">一键下载并安装</button>
            <div style="font-size:12px;color:#888;margin-top:5px;">可填写版本号（如v1.0.0），或直接填写zip包下载URL，留空为最新版</div>
        </form>
        </div>
        <div class="section">
        <h2>自动修复</h2>
        <form method="post" style="display:inline-block;">
                    <input type="hidden" name="action" value="create_default_config">
            <button type="submit" class="btn">修复默认配置</button>
                </form>
            </div>
        <div class="section">
        <h2>快速链接</h2>
            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                <a href="获取订阅.php" target="_blank" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">📄 访问主页面</a>
                <a href="admin.php" target="_blank" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">⚙️ 后台管理</a>
                <a href="README.md" target="_blank" style="background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">📖 使用说明</a>
            </div>
        </div>
    </div>
</body>
</html>