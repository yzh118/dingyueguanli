<?php
// 设置会话安全配置
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
ini_set('session.gc_maxlifetime', 3600); // 1小时后会话过期
ini_set('session.cookie_lifetime', 3600);

session_start();

// 检查会话是否已过期
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
    session_unset();
    session_destroy();
    header('Location: admin.php');
    exit;
}
$_SESSION['last_activity'] = time();

// 启用错误显示（临时调试用）
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 记录请求日志
error_log("API请求: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI'] . " Action: " . ($_GET['action'] ?? 'none'));

// 设置内容类型
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 检查身份验证
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => '未授权访问']);
    exit;
}

// 定义全局变量
define('PRIVATE_DIR', __DIR__ . '/private');
define('CARDS_FILE', PRIVATE_DIR . '/cards.json');
define('SOURCES_FILE', PRIVATE_DIR . '/sources.json');

// 确保目录存在
if (!file_exists(PRIVATE_DIR)) {
    mkdir(PRIVATE_DIR, 0755, true);
}

// 确保文件存在
if (!file_exists(CARDS_FILE)) {
    file_put_contents(CARDS_FILE, json_encode(['cards' => [], 'settings' => []]));
}
if (!file_exists(SOURCES_FILE)) {
    file_put_contents(SOURCES_FILE, json_encode(['sources' => []]));
}

// 获取处理方式文本
function getDecodeTypeText($type) {
    $types = [
        'none' => '不处理',
        'base64' => 'Base64解码',
        'base64_encode' => 'Base64编码'
    ];
    return $types[$type] ?? $type;
}

// 处理错误和异常
function handle_error($errno, $errstr, $errfile, $errline) {
    error_log("PHP错误: [$errno] $errstr in $errfile on line $errline");
    send_json(['success' => false, 'error' => 'PHP错误: ' . $errstr]);
    return true;
}

function handle_exception($e) {
    error_log("PHP异常: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    send_json(['success' => false, 'error' => 'PHP异常: ' . $e->getMessage()]);
}

// 设置错误处理器
set_error_handler('handle_error');
set_exception_handler('handle_exception');

// 启用错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', PRIVATE_DIR . '/error.log');

// 读取卡密配置
function readCards() {
    try {
        $content = file_get_contents(CARDS_FILE);
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

// 保存卡密配置
function saveCards($data) {
    try {
        $result = file_put_contents(CARDS_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            throw new Exception("无法写入cards.json文件");
        }
        return true;
    } catch(Exception $e) {
        throw new Exception("保存失败: " . $e->getMessage());
    }
}

// 读取订阅源配置
function readSources() {
    try {
        $content = file_get_contents(SOURCES_FILE);
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
        $result = file_put_contents(SOURCES_FILE, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            throw new Exception("无法写入sources.json文件");
        }
        return true;
    } catch(Exception $e) {
        throw new Exception("保存失败: " . $e->getMessage());
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

// 验证卡密
function validateCard($cardCode) {
    $cards = readCards();
    
    foreach ($cards['cards'] as &$card) {
        if ($card['card'] === $cardCode && $card['status'] === 'active') {
            // 检查是否过期
            if ($card['used_at'] !== null && isset($card['expire_days']) && $card['expire_days'] !== -1) {
                $usedTime = strtotime($card['used_at']);
                $expireTime = $usedTime + ($card['expire_days'] * 24 * 60 * 60);
                
                if (time() > $expireTime) {
                    $card['status'] = 'expired';
                    saveCards($cards);
                    return ['valid' => false, 'message' => '卡密已过期'];
                }
            }
            
            // 如果是首次使用，记录使用时间
            if ($card['used_at'] === null) {
                $card['used_at'] = date('Y-m-d H:i:s');
                $card['used_by'] = $_SERVER['REMOTE_ADDR'];
                saveCards($cards);
            }
            
            // 设置session
            $_SESSION['card_code'] = $cardCode;
            $_SESSION['card_expire_time'] = isset($card['expire_days']) && $card['expire_days'] !== -1 
                ? strtotime($card['used_at']) + ($card['expire_days'] * 24 * 60 * 60)
                : null;
            
            return ['valid' => true, 'message' => '验证成功', 'card' => $card];
        }
    }
    
    return ['valid' => false, 'message' => '无效的卡密'];
}

// 发送JSON响应
function send_json($data) {
    // 清除之前的所有输出缓冲
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // 开启新的输出缓冲
    ob_start();
    
    // 设置响应头
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    
    // 确保数据是UTF-8编码
    array_walk_recursive($data, function(&$item) {
        if (is_string($item)) {
            $item = mb_convert_encoding($item, 'UTF-8', 'UTF-8');
        }
    });
    
    // 使用JSON_INVALID_UTF8_SUBSTITUTE来处理无效的UTF-8字符
    $json = json_encode($data, 
        JSON_UNESCAPED_UNICODE | 
        JSON_UNESCAPED_SLASHES | 
        JSON_INVALID_UTF8_SUBSTITUTE
    );
    
    if ($json === false) {
        // 如果JSON编码失败，返回错误信息
        $error_data = [
            'success' => false,
            'error' => 'JSON编码失败: ' . json_last_error_msg()
        ];
        echo json_encode($error_data);
    } else {
        echo $json;
    }
    
    // 发送输出并结束
    ob_end_flush();
    exit();
}

// 获取卡密数据
function get_cards() {
    $file = __DIR__.'/private/cards.json';
    if (!file_exists($file)) {
        return [];
    }
    return json_decode(file_get_contents($file), true) ?: [];
}

// 处理API请求
$action = $_GET['action'] ?? '';

// 如果是POST请求，尝试从请求体中获取action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

// 添加测试操作
if ($action === 'test') {
    echo json_encode([
        'success' => true,
        'message' => 'API正常工作',
        'timestamp' => date('Y-m-d H:i:s'),
        'files' => [
            'cards_exists' => file_exists($cardsFile),
            'sources_exists' => file_exists($sourcesFile),
            'docs_exists' => file_exists(__DIR__ . '/docs.md')
        ]
    ]);
    exit;
}

switch ($action) {
    case 'get_sources':
        try {
            $sourcesData = readSources();
            send_json(['success' => true, 'sources' => $sourcesData['sources'] ?? []]);
        } catch(Exception $e) {
            send_json(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_cards':
        try {
            $cardsData = readCards();
            send_json([
                'success' => true,
                'cards' => $cardsData['cards'] ?? [],
                'settings' => $cardsData['settings'] ?? []
            ]);
        } catch(Exception $e) {
            send_json(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_settings':
        try {
            $sourcesData = readSources();
            send_json(['success' => true, 'settings' => [
                'multi_source_mode' => $sourcesData['multi_source_mode'] ?? 'single',
                'load_balancing' => $sourcesData['load_balancing'] ?? false,
                'user_choice_enabled' => $sourcesData['user_choice_enabled'] ?? false
            ]]);
        } catch(Exception $e) {
            send_json(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'get_docs':
        $docsFile = __DIR__ . '/docs.md';
        $content = '';
        if (file_exists($docsFile)) {
            $content = file_get_contents($docsFile);
        }
        echo json_encode(['success' => true, 'content' => $content]);
        break;

    case 'save_docs':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $content = $input['markdown_content'] ?? '';
        
        $docsFile = __DIR__ . '/docs.md';
        if (file_put_contents($docsFile, $content)) {
            echo json_encode(['success' => true, 'message' => '文档保存成功']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => '保存失败']);
        }
        break;

    case 'get_stats':
        try {
            error_log("开始获取统计数据");
            
            $sourcesData = readSources();
            error_log("读取订阅源数据: " . json_encode($sourcesData, JSON_UNESCAPED_UNICODE));
            
            $cardsData = readCards();
            error_log("读取卡密数据: " . json_encode($cardsData, JSON_UNESCAPED_UNICODE));
            
            if (!isset($sourcesData['sources'])) {
                error_log("订阅源数据格式错误: sources字段不存在");
                $sourcesData['sources'] = [];
            }
            
            if (!isset($cardsData['cards'])) {
                error_log("卡密数据格式错误: cards字段不存在");
                $cardsData['cards'] = [];
            }
            
            $stats = [
                'total_sources' => count($sourcesData['sources']),
                'active_sources' => count(array_filter($sourcesData['sources'], function($source) {
                    return $source['enabled'];
                })),
                'total_cards' => count($cardsData['cards']),
                'active_cards' => count(array_filter($cardsData['cards'], function($card) {
                    return $card['status'] === 'active';
                })),
                'current_mode' => isset($sourcesData['multi_source_mode']) ? 
                    ($sourcesData['multi_source_mode'] === 'single' ? '单一源模式' : 
                     ($sourcesData['multi_source_mode'] === 'load_balance' ? '负载均衡模式' : 
                     ($sourcesData['multi_source_mode'] === 'user_choice' ? '用户选择模式' : '未知模式'))) : '单一源模式',
                'global_card_required' => isset($cardsData['settings']) && isset($cardsData['settings']['global_card_required']) ? 
                    $cardsData['settings']['global_card_required'] : false
            ];
            
            error_log("统计数据生成完成: " . json_encode($stats, JSON_UNESCAPED_UNICODE));
            send_json(['success' => true, 'stats' => $stats]);
            
        } catch(Exception $e) {
            error_log("获取统计数据失败: " . $e->getMessage());
            error_log("错误堆栈: " . $e->getTraceAsString());
            send_json(['success' => false, 'error' => $e->getMessage()]);
        }
        break;

    case 'add_source':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $name = $input['name'] ?? '';
        $url = $input['url'] ?? '';
        $decodeType = $input['decode_type'] ?? 'none';
        $enabled = $input['enabled'] ?? true;
        $cardRequired = $input['card_required'] ?? false;
        
        if (empty($name) || empty($url)) {
            http_response_code(400);
            echo json_encode(['error' => '名称和URL不能为空']);
            break;
        }

        $sourcesData = readSources();
        $sources = $sourcesData['sources'] ?? [];
        
        $newSource = [
            'id' => uniqid(),
            'name' => $name,
            'url' => $url,
            'decode_type' => $decodeType,
            'enabled' => $enabled,
            'card_required' => $cardRequired
        ];

        $sources[] = $newSource;
        $sourcesData['sources'] = $sources;
        
        if (saveSources($sourcesData)) {
            echo json_encode(['success' => true, 'message' => '订阅源添加成功']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => '保存失败']);
        }
        break;

    case 'edit_source':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $sourceId = $input['source_id'] ?? '';
        $name = $input['name'] ?? '';
        $url = $input['url'] ?? '';
        $decodeType = $input['decode_type'] ?? 'none';
        $enabled = $input['enabled'] ?? true;
        $cardRequired = $input['card_required'] ?? false;
        
        if (empty($sourceId) || empty($name) || empty($url)) {
            http_response_code(400);
            echo json_encode(['error' => 'ID、名称和URL不能为空']);
            break;
        }

        $sourcesData = readSources();
        $sources = $sourcesData['sources'] ?? [];
        
        $found = false;
        foreach ($sources as &$source) {
            if ($source['id'] === $sourceId) {
                $source['name'] = $name;
                $source['url'] = $url;
                $source['decode_type'] = $decodeType;
                $source['enabled'] = $enabled;
                $source['card_required'] = $cardRequired;
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => '订阅源不存在']);
            break;
        }

        $sourcesData['sources'] = $sources;
        
        if (saveSources($sourcesData)) {
            echo json_encode(['success' => true, 'message' => '订阅源更新成功']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => '保存失败']);
        }
        break;

    case 'delete_source':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $sourceId = $input['source_id'] ?? '';
        
        if (empty($sourceId)) {
            http_response_code(400);
            echo json_encode(['error' => '订阅源ID不能为空']);
            break;
        }

        $sourcesData = readSources();
        $sources = $sourcesData['sources'] ?? [];
        
        $found = false;
        foreach ($sources as $key => $source) {
            if ($source['id'] === $sourceId) {
                unset($sources[$key]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => '订阅源不存在']);
            break;
        }

        $sourcesData['sources'] = array_values($sources);
        
        if (saveSources($sourcesData)) {
            echo json_encode(['success' => true, 'message' => '订阅源删除成功']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => '保存失败']);
        }
        break;

    case 'add_card':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $card = $input['card'] ?? '';
        $allowedSources = $input['allowed_sources'] ?? ['all'];
        $name = $input['name'] ?? '-';
        
        if (empty($card)) {
            http_response_code(400);
            echo json_encode(['error' => '卡密不能为空']);
            break;
        }

        $cardsData = readCards();
        $cards = $cardsData['cards'] ?? [];
        
        // 检查卡密是否已存在
        foreach ($cards as $existingCard) {
            if ($existingCard['card'] === $card) {
                http_response_code(400);
                echo json_encode(['error' => '卡密已存在']);
                break 2;
            }
        }

        $newCard = [
            'id' => uniqid(),
            'card' => $card,
            'name' => $name,
            'allowed_sources' => $allowedSources,
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => null
        ];

        $cards[] = $newCard;
        $cardsData['cards'] = $cards;
        
        try {
            if (saveCards($cardsData)) {
                echo json_encode(['success' => true, 'message' => '卡密添加成功']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '保存失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update_card':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $cardId = $input['id'] ?? '';
        $allowedSources = $input['allowed_sources'] ?? ['all'];
        $status = $input['status'] ?? 'active';
        $name = $input['name'] ?? null;
        $expireDays = isset($input['expire_days']) ? intval($input['expire_days']) : null;
        
        if (empty($cardId)) {
            http_response_code(400);
            echo json_encode(['error' => '卡密ID不能为空']);
            break;
        }

        $cardsData = readCards();
        $cards = $cardsData['cards'] ?? [];
        
        $found = false;
        foreach ($cards as &$card) {
            if ($card['id'] === $cardId) {
                $card['allowed_sources'] = $allowedSources;
                $card['status'] = $status;
                if ($name !== null) {
                    $card['name'] = $name;
                }
                if ($expireDays !== null) {
                    if ($expireDays === -1) {
                        $card['expires_at'] = null;
                    } else {
                        $card['expires_at'] = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));
                    }
                }
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => '卡密不存在']);
            break;
        }

        $cardsData['cards'] = $cards;
        
        try {
            if (saveCards($cardsData)) {
                echo json_encode(['success' => true, 'message' => '卡密更新成功']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '保存失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'delete_card':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $cardId = $input['id'] ?? '';
        
        if (empty($cardId)) {
            http_response_code(400);
            echo json_encode(['error' => '卡密ID不能为空']);
            break;
        }

        $cardsData = readCards();
        $cards = $cardsData['cards'] ?? [];
        
        $found = false;
        foreach ($cards as $key => $card) {
            if ($card['id'] === $cardId) {
                unset($cards[$key]);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            http_response_code(404);
            echo json_encode(['error' => '卡密不存在']);
            break;
        }

        $cardsData['cards'] = array_values($cards);
        
        try {
            if (saveCards($cardsData)) {
                echo json_encode(['success' => true, 'message' => '卡密删除成功']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '保存失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update_settings':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $cardsData = readCards();
        $cardsData['settings'] = $cardsData['settings'] ?? [];
        
        // 直接合并参数到设置中
        foreach ($input as $key => $value) {
            $cardsData['settings'][$key] = $value;
        }
        
        try {
            if (saveCards($cardsData)) {
                echo json_encode(['success' => true, 'message' => '设置更新成功']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '保存失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'update_card_settings':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $globalCardRequired = $input['global_card_required'] ?? false;
        
        $cardsData = readCards();
        $cardsData['settings'] = $cardsData['settings'] ?? [];
        $cardsData['settings']['global_card_required'] = $globalCardRequired;
        
        try {
            if (saveCards($cardsData)) {
                echo json_encode(['success' => true, 'message' => '卡密设置更新成功']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '保存失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'save_seo_settings':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $cardsData = readCards();
        $cardsData['seo_settings'] = $cardsData['seo_settings'] ?? [];
        
        // 保存SEO设置
        foreach ($input as $key => $value) {
            $cardsData['seo_settings'][$key] = $value;
        }
        
        try {
            if (saveCards($cardsData)) {
                echo json_encode(['success' => true, 'message' => 'SEO设置保存成功']);
            } else {
                http_response_code(500);
                echo json_encode(['error' => '保存失败']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        break;

    case 'get_seo_settings':
        $cardsData = readCards();
        $seoSettings = $cardsData['seo_settings'] ?? [];
        echo json_encode(['success' => true, 'settings' => $seoSettings]);
        break;

    case 'get_admin_config':
        $configFile = __DIR__ . '/private/admin_config.json';
        if (file_exists($configFile)) {
            $config = json_decode(file_get_contents($configFile), true);
            echo json_encode([
                'success' => true,
                'admin_config' => $config ?: []
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'admin_config' => [
                    'admin_username' => 'admin',
                    'admin_password' => '123456',
                    'admin_path' => 'admin'
                ]
            ]);
        }
        break;

    case 'update_admin_config':
        $input = json_decode(file_get_contents('php://input'), true);
        // 移除action参数，避免干扰其他参数
        unset($input['action']);
        
        $configFile = __DIR__ . '/private/admin_config.json';
        $currentConfig = [];
        
        if (file_exists($configFile)) {
            $currentConfig = json_decode(file_get_contents($configFile), true) ?: [];
        }
        
        $oldPath = $currentConfig['admin_path'] ?? 'admin';
        $newPath = $input['admin_path'] ?? 'admin';
        
        // 更新配置
        if (isset($input['admin_username']) && !empty($input['admin_username'])) {
            $currentConfig['admin_username'] = $input['admin_username'];
        }
        
        if (isset($input['admin_password']) && !empty($input['admin_password'])) {
            $currentConfig['admin_password'] = $input['admin_password'];
        }
        
        if (isset($input['admin_path']) && !empty($input['admin_path'])) {
            $currentConfig['admin_path'] = $input['admin_path'];
        }
        
        // 确保安全设置存在
        $currentConfig['security_settings'] = $currentConfig['security_settings'] ?? [
            'session_timeout' => 3600,
            'max_login_attempts' => 5,
            'lockout_duration' => 300
        ];
        
        // 如果路径发生变化，创建新的后台文件
        if ($oldPath !== $newPath) {
            $oldFile = __DIR__ . '/' . $oldPath . '.php';
            $newFile = __DIR__ . '/' . $newPath . '.php';
            
            // 如果新文件不存在，从admin.php复制
            if (!file_exists($newFile)) {
                $adminContent = file_get_contents(__DIR__ . '/admin.php');
                if ($adminContent !== false) {
                    // 修改内容，添加安全路径检查
                    $securityCheck = '
// 检查安全路径
$currentPath = basename($_SERVER[\'PHP_SELF\'], \'.php\');
if ($currentPath !== $ADMIN_PATH) {
    // 如果不是正确的安全路径，重定向到正确的路径
    $correctUrl = $ADMIN_PATH . \'.php\';
    if (file_exists(__DIR__ . \'/\' . $correctUrl)) {
        header(\'Location: \' . $correctUrl);
        exit;
    }
}
';
                    
                    // 在登录检查之前插入安全路径检查
                    $insertPos = strpos($adminContent, '// 检查登录状态');
                    if ($insertPos !== false) {
                        $adminContent = substr_replace($adminContent, $securityCheck . "\n", $insertPos, 0);
                    }
                    
                    if (file_put_contents($newFile, $adminContent) === false) {
                        http_response_code(500);
                        echo json_encode(['error' => '创建新的后台文件失败']);
                        break;
                    }
                }
            }
        }
        
        if (file_put_contents($configFile, json_encode($currentConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            echo json_encode(['success' => true, 'message' => '管理员配置更新成功']);
        } else {
            http_response_code(500);
            echo json_encode(['error' => '保存失败']);
        }
        break;

    case 'add_batch_cards':
        $data = json_decode(file_get_contents('php://input'), true);
        $group_id = $data['group_id'] ?? '';
        $cards_list = $data['cards'] ?? [];
        $auto_generate = $data['auto_generate'] ?? false;
        
        if (empty($group_id)) {
            send_json(['success' => false, 'error' => '用户组ID不能为空']);
            break;
        }
        
        if (empty($cards_list)) {
            send_json(['success' => false, 'error' => '卡密列表不能为空']);
            break;
        }
        
        $cardsData = get_cards();
        $cards = $cardsData['cards'] ?? [];
        $success_count = 0;
        $error_count = 0;
        
        foreach ($cards_list as $card_text) {
            $card_text = trim($card_text);
            if (empty($card_text)) continue;
            
            // 如果启用自动生成且输入不是卡密格式
            if ($auto_generate && !preg_match('/^[a-zA-Z0-9]{8,}$/', $card_text)) {
                $card_code = generate_card_code($card_text);
            } else {
                $card_code = $card_text;
            }
            
            // 检查卡密是否已存在
            $exists = false;
            foreach ($cards as $existing_card) {
                if ($existing_card['card'] === $card_code) {
                    $exists = true;
                    break;
                }
            }
            
            if ($exists) {
                $error_count++;
                continue;
            }
            
            // 添加新卡密
            $cards[] = [
                'id' => uniqid(),
                'card' => $card_code,
                'status' => 'active',
                'created_at' => time()
            ];
            
            $success_count++;
        }
        
        // 保存卡密数据
        if ($success_count > 0) {
            $cardsData['cards'] = $cards;
            file_put_contents($cardsFile, json_encode($cardsData, JSON_PRETTY_PRINT));
        }
        
        send_json([
            'success' => true,
            'message' => "成功添加 {$success_count} 个卡密，失败 {$error_count} 个"
        ]);
        break;

    case 'remove_card_from_group':
        $data = json_decode(file_get_contents('php://input'), true);
        $group_id = $data['group_id'] ?? '';
        $card_id = $data['card_id'] ?? '';
        
        if (empty($group_id) || empty($card_id)) {
            send_json(['success' => false, 'error' => '参数不完整']);
            break;
        }
        
        $cards = get_cards();
        $found = false;
        
        foreach ($cards as &$card) {
            if ($card['id'] === $card_id) {
                $card['status'] = 'inactive';
                $found = true;
                break;
            }
        }
        
        if ($found) {
            file_put_contents(__DIR__.'/private/cards.json', json_encode($cards, JSON_PRETTY_PRINT));
            send_json(['success' => true]);
        } else {
            send_json(['success' => false, 'error' => '卡密不存在或不属于该用户组']);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => '无效的操作']);
        break;
}

// 生成卡密的辅助函数
function generate_card_code($name) {
    $prefix = substr(md5($name), 0, 4);
    $random = substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
    return $prefix . $random;
}