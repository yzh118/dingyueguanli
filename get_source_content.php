<?php
// 禁用错误显示到页面
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 设置内容类型
header('Content-Type: application/json; charset=utf-8');

// 开启输出缓冲
ob_start();

session_start();

// 检查管理员登录状态
function checkAdminAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        echo json_encode(['error' => '未授权访问']);
        exit;
    }
}

// 读取订阅源配置
function readSources() {
    try {
        $content = file_get_contents(__DIR__ . '/private/sources.json');
        if ($content === false) {
            throw new Exception("无法读取sources.json文件");
        }
        return json_decode($content, true);
    } catch(Exception $e) {
        return [
            'sources' => [
                [
                    'id' => 'default',
                    'name' => '默认订阅源',
                    'url' => '',
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

// 保存订阅源配置
function saveSources($data) {
    try {
        $result = file_put_contents(__DIR__ . '/private/sources.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            throw new Exception("无法写入sources.json文件");
        }
        return true;
    } catch(Exception $e) {
        throw new Exception("保存失败: " . $e->getMessage());
    }
}

// 读取卡密配置
function readCards() {
    try {
        $content = file_get_contents(__DIR__ . '/private/cards.json');
        if ($content === false) {
            throw new Exception("无法读取cards.json文件");
        }
        return json_decode($content, true);
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

// 获取内容处理函数
function getContentFromSource($source) {
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

// 处理API请求
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'get_sources':
            // 需要管理员权限
            checkAdminAuth();
            $sources = readSources();
            echo json_encode(['success' => true, 'sources' => $sources['sources']]);
            break;
            
        case 'get_source':
            // 需要管理员权限
            checkAdminAuth();
            $sources = readSources();
            $sourceId = $_POST['source_id'];
            
            $source = null;
            foreach ($sources['sources'] as $s) {
                if ($s['id'] === $sourceId) {
                    $source = $s;
                    break;
                }
            }
            
            if ($source) {
                echo json_encode($source);
            } else {
                echo json_encode(['error' => '订阅源不存在']);
            }
            break;
            
        case 'add_source':
            // 需要管理员权限
            checkAdminAuth();
            $sources = readSources();
            
            $newSource = [
                'id' => 'source_' . time(),
                'name' => $_POST['name'],
                'url' => $_POST['url'],
                'decode_type' => $_POST['decode_type'],
                'enabled' => isset($_POST['enabled']),
                'card_required' => isset($_POST['card_required'])
            ];
            
            $sources['sources'][] = $newSource;
            saveSources($sources);
            
            echo json_encode(['success' => true, 'source' => $newSource]);
            break;
            
        case 'edit_source':
            // 需要管理员权限
            checkAdminAuth();
            $sources = readSources();
            $sourceId = $_POST['source_id'];
            
            foreach ($sources['sources'] as &$source) {
                if ($source['id'] === $sourceId) {
                    $source['name'] = $_POST['name'];
                    $source['url'] = $_POST['url'];
                    $source['decode_type'] = $_POST['decode_type'];
                    $source['enabled'] = isset($_POST['enabled']);
                    $source['card_required'] = isset($_POST['card_required']);
                    break;
                }
            }
            
            saveSources($sources);
            echo json_encode(['success' => true]);
            break;
            
        case 'delete_source':
            // 需要管理员权限
            checkAdminAuth();
            $sources = readSources();
            $sourceId = $_POST['source_id'];
            
            $sources['sources'] = array_filter($sources['sources'], function($source) use ($sourceId) {
                return $source['id'] !== $sourceId;
            });
            $sources['sources'] = array_values($sources['sources']);
            
            if ($sources['current_source'] === $sourceId) {
                $sources['current_source'] = !empty($sources['sources']) ? $sources['sources'][0]['id'] : '';
            }
            
            saveSources($sources);
            echo json_encode(['success' => true]);
            break;
            
        case 'set_current':
            // 需要管理员权限
            checkAdminAuth();
            $sources = readSources();
            $sourceId = $_POST['source_id'];
            
            $sources['current_source'] = $sourceId;
            saveSources($sources);
            echo json_encode(['success' => true]);
            break;
            
        case 'get_settings':
            // 需要管理员权限
            checkAdminAuth();
            $sources = readSources();
            echo json_encode([
                'success' => true,
                'settings' => [
                    'multi_source_mode' => $sources['multi_source_mode'] ?? 'single',
                    'load_balancing' => $sources['load_balancing'] ?? false,
                    'user_choice_enabled' => $sources['user_choice_enabled'] ?? false
                ]
            ]);
            break;
            
        case 'save_settings':
            // 需要管理员权限
            checkAdminAuth();
    $sources = readSources();
            
            // 更新设置
            $sources['multi_source_mode'] = $_POST['multi_source_mode'] ?? 'single';
            $sources['load_balancing'] = isset($_POST['load_balancing']);
            $sources['user_choice_enabled'] = isset($_POST['user_choice_enabled']);
            
            saveSources($sources);
            echo json_encode(['success' => true]);
            break;
            
        case 'update_docs':
            // 需要管理员权限
            checkAdminAuth();
            try {
                file_put_contents('docs.md', $_POST['markdown_content']);
                echo json_encode(['success' => true]);
            } catch(Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
            }
            break;
            
        case 'get_docs':
            try {
                $file_path = __DIR__ . '/docs.md';
                // 检查文件是否存在
                if (!file_exists($file_path)) {
                    throw new Exception("文件不存在: " . $file_path);
                }
                // 检查文件权限
                if (!is_readable($file_path)) {
                    throw new Exception("文件无法读取，请检查权限: " . $file_path);
                }
                $content = file_get_contents($file_path);
                if ($content === false) {
                    throw new Exception("无法读取docs.md文件");
                }
                
                // 检查是否有BOM头
                if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
                    $content = substr($content, 3); // 去除BOM头
                }
                
                // 确保内容是UTF-8编码
                if (!mb_check_encoding($content, 'UTF-8')) {
                    $content = mb_convert_encoding($content, 'UTF-8', mb_detect_encoding($content, ['GBK', 'GB2312', 'BIG5', 'UTF-8']));
                }
                
                // 清除之前的所有输出
                ob_clean();
                
                // 设置响应头
                header('Content-Type: application/json; charset=utf-8');
                
                // 输出JSON
                echo json_encode(['success' => true, 'content' => $content], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE);
            } catch(Exception $e) {
                // 清除之前的所有输出
                ob_clean();
                
                // 设置响应头
                header('Content-Type: application/json; charset=utf-8');
                
                echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
            }
            break;
            
        case 'save_docs':
            // 需要管理员权限
            checkAdminAuth();
            try {
                $file_path = __DIR__ . '/docs.md';
                $content = $_POST['markdown_content'] ?? '';
                
                // 检查目录权限
                if (!is_writable(dirname($file_path))) {
                    throw new Exception("目录无写入权限: " . dirname($file_path));
                }
                
                // 如果文件存在，检查文件权限
                if (file_exists($file_path) && !is_writable($file_path)) {
                    throw new Exception("文件无写入权限: " . $file_path);
                }
                
                $result = file_put_contents($file_path, $content);
                if ($result === false) {
                    throw new Exception("无法写入docs.md文件");
                }
                echo json_encode(['success' => true]);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        case 'get_stats':
            // 需要管理员权限
            checkAdminAuth();
            try {
                $sources = readSources();
                $cards = readCards();
                
                $stats = [
                    'total_sources' => count($sources['sources']),
                    'active_sources' => count(array_filter($sources['sources'], function($source) {
                        return $source['enabled'];
                    })),
                    'total_cards' => count($cards['cards']),
                    'active_cards' => count(array_filter($cards['cards'], function($card) {
                        return !$card['used'];
                    })),
                    'current_mode' => isset($sources['multi_source_mode']) ? 
                        ($sources['multi_source_mode'] === 'single' ? '单一源模式' : 
                         ($sources['multi_source_mode'] === 'load_balance' ? '负载均衡模式' : 
                         ($sources['multi_source_mode'] === 'user_choice' ? '用户选择模式' : '未知模式'))) : '未知模式',
                    'global_card_required' => isset($cards['settings']['global_card_required']) && $cards['settings']['global_card_required'] ? '已启用' : '未启用'
                ];
                
                echo json_encode(['success' => true, 'stats' => $stats]);
            } catch(Exception $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
            break;
            
        default:
            // 处理普通的获取内容请求
            $sources = readSources();
            $sourceId = $_POST['source_id'] ?? '';
    
    // 查找指定的订阅源
    $target_source = null;
    foreach ($sources['sources'] as $source) {
                if ($source['id'] === $sourceId && $source['enabled']) {
            $target_source = $source;
            break;
        }
    }
    
    if ($target_source) {
        $content = getContentFromSource($target_source);
        echo htmlspecialchars($content);
    } else {
        echo "订阅源不存在或已禁用";
    }
            break;
}

// 结束输出缓冲
ob_end_flush();
?> 