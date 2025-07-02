<?php
// 添加更详细的错误日志
ini_set('display_errors', 1);
error_reporting(E_ALL);
error_log("API Request started - " . $_SERVER['REQUEST_METHOD']);

// 设置更完整的CORS头
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

// 确保session正确启动
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));

// 获取处理方式文本
function getDecodeTypeText($type) {
    $types = [
        'none' => '不处理',
        'base64' => 'Base64解码',
        'base64_encode' => 'Base64编码'
    ];
    return $types[$type] ?? $type;
}

$cardsFile = __DIR__ . '/private/cards.json';
$sourcesFile = __DIR__ . '/private/sources.json';

// 确保文件存在
if (!file_exists($cardsFile)) {
    file_put_contents($cardsFile, json_encode(['cards' => [], 'settings' => []]));
}
if (!file_exists($sourcesFile)) {
    file_put_contents($sourcesFile, json_encode(['sources' => []]));
}

// 获取请求体
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

error_log("Received action: " . $action);

// 支持POST请求体中的action参数
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

// 读取订阅源配置
function readSources() {
    $sourcesFile = __DIR__ . '/private/sources.json';
    if (!file_exists($sourcesFile)) {
        return ['sources' => []];
    }
    $content = file_get_contents($sourcesFile);
    if ($content === false) {
        error_log("Error reading sources file");
        return ['sources' => []];
    }
    $data = json_decode($content, true);
    if ($data === null) {
        error_log("Error decoding sources JSON: " . json_last_error_msg());
        return ['sources' => []];
    }
    return $data;
}

switch ($action) {
    case 'verify_card':
        error_log("Processing verify_card");
        $card = $input['card'] ?? '';
        
        if (empty($card)) {
            error_log("Empty card code");
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '卡密不能为空']);
            break;
        }

        $cardsData = json_decode(file_get_contents($cardsFile), true);
        $cards = $cardsData['cards'] ?? [];
        
        $validCard = null;
        foreach ($cards as $cardData) {
            if ($cardData['card'] === $card && $cardData['status'] === 'active') {
                $validCard = $cardData;
                break;
            }
        }
        
        if (!$validCard) {
            error_log("Invalid or disabled card: " . $card);
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => '卡密无效或已禁用']);
            break;
        }

        // 设置session信息
        $_SESSION['card_code'] = $card;
        $_SESSION['card_id'] = $validCard['id'];
        error_log("Card validated and session set: " . $card);

        echo json_encode([
            'success' => true,
            'card' => $validCard
        ]);
        break;

    case 'get_full_content':
        error_log("Processing get_full_content");
        
        $sourcesData = json_decode(file_get_contents($sourcesFile), true);
        $sources = $sourcesData['sources'] ?? [];
        $filteredSources = [];
        
        // 检查是否有卡密验证
        $hasCardAuth = isset($_SESSION['card_code']) && !empty($_SESSION['card_code']);
        
        if ($hasCardAuth) {
            // 如果有卡密，获取卡密信息
            $cardsData = json_decode(file_get_contents($cardsFile), true);
            $cards = $cardsData['cards'] ?? [];
            
            // 获取当前用户的卡密信息
            $currentCard = null;
            foreach ($cards as $card) {
                if ($card['card'] === $_SESSION['card_code']) {
                    $currentCard = $card;
                    break;
                }
            }
            
            if (!$currentCard) {
                error_log("Invalid card info in session");
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => '卡密信息无效']);
                break;
            }
            
            // 获取允许的订阅源
            $allowedSources = $currentCard['allowed_sources'] ?? ['all'];
            if (!is_array($allowedSources)) {
                $allowedSources = ['all'];
            }
            
            // 过滤允许的订阅源
            foreach ($sources as $source) {
                if ($source['enabled'] && (in_array('all', $allowedSources) || in_array($source['id'], $allowedSources))) {
                    $filteredSources[] = $source;
                }
            }
        } else {
            // 如果没有卡密，只显示不需要卡密的订阅源
            foreach ($sources as $source) {
                if ($source['enabled'] && (!isset($source['card_required']) || !$source['card_required'])) {
                    $filteredSources[] = $source;
                }
            }
        }

        // 生成HTML内容
        $html = '';
        
        if (!empty($filteredSources)) {
            // 添加订阅源信息
            $html .= '<div class="source-info">';
            $html .= '<h3>📡 当前订阅源</h3>';
            $html .= '<p>名称：' . htmlspecialchars($filteredSources[0]['name']) . '</p>';
            $html .= '<p>URL：<a href="' . htmlspecialchars($filteredSources[0]['url']) . '" target="_blank">' . htmlspecialchars($filteredSources[0]['url']) . '</a></p>';
            $html .= '<p>处理方式：' . getDecodeTypeText($filteredSources[0]['decode_type'] ?? 'none') . '</p>';
            $html .= '<p>模式：' . ($sourcesData['multi_source_mode'] === 'load_balance' ? '负载均衡模式' : '用户选择模式') . '</p>';
            $html .= '</div>';

            // 在负载均衡模式下，不显示源选择器
            if ($sourcesData['multi_source_mode'] !== 'load_balance') {
                $html .= '<div class="section-title">选择订阅源</div>';
                $html .= '<div class="source-selector">';
                $html .= '<div class="source-dropdown">';
                $html .= '<button class="source-dropdown-btn" onclick="toggleDropdown()" id="dropdownBtn">';
                $html .= htmlspecialchars($filteredSources[0]['name']);
                $html .= '</button>';
                $html .= '<div class="source-dropdown-content" id="dropdownContent">';
                
                // 获取所有订阅源
                $allSources = $sourcesData['sources'] ?? [];
                foreach ($allSources as $source) {
                    if (!$source['enabled']) continue; // 跳过禁用的源
                    
                    $isProtected = isset($source['card_required']) && $source['card_required'];
                    $isAccessible = in_array($source['id'], array_column($filteredSources, 'id'));
                    $isSelected = $source['id'] === $filteredSources[0]['id'];
                    
                    $html .= '<div class="source-dropdown-item' . 
                            ($isSelected ? ' selected' : '') . 
                            '" ' . 
                            'onclick="' . ($isProtected && !$isAccessible ? 'showCardAuthModal()' : 'switchSource(\'' . $source['id'] . '\', \'' . htmlspecialchars($source['name']) . '\')') . '"' .
                            ' data-source-id="' . $source['id'] . '">';
                    
                    // 如果是受保护的源，添加锁图标
                    if ($isProtected) {
                        $html .= '<i class="bi bi-lock-fill"></i> ';
                    }
                    
                    $html .= htmlspecialchars($source['name']);
                    
                    if ($isProtected && !$isAccessible) {
                        $html .= ' <span class="text-muted">(需要卡密验证)</span>';
                    }
                    
                    $html .= '</div>';
                }
                
                $html .= '</div>';
                $html .= '</div>';
                $html .= '</div>';
            } else {
                // 在负载均衡模式下显示提示
                $html .= '<div class="alert alert-info" role="alert">';
                $html .= '<i class="bi bi-info-circle"></i> 系统正在使用负载均衡模式，将自动为您分配最佳订阅源。';
                $html .= '</div>';
            }
            
            // 添加复制按钮和内容显示区域
            $html .= '<button class="copy-btn" onclick="copyContent()">';
            $html .= '<i class="bi bi-clipboard"></i> 复制最新节点';
            $html .= '</button>';
            
            $html .= '<div class="result-box">';
            $html .= '<div class="result-content" id="decodedContent">正在加载订阅源内容...</div>';
            $html .= '</div>';
        } else {
            $html .= '<div class="source-info">';
            $html .= '<h3>无可用订阅源</h3>';
            $html .= '<p>您当前没有可访问的订阅源。</p>';
            $html .= '</div>';
        }

        // 将HTML内容包装在JSON响应中
        echo json_encode([
            'success' => true,
            'html' => $html,
            'sources' => $filteredSources
        ]);
        break;

    case 'get_source_content':
        error_log("Processing get_source_content");
        // 检查用户是否已通过卡密验证
        if (!isset($_SESSION['card_code']) || empty($_SESSION['card_code'])) {
            error_log("Card auth failed: no card_code in session");
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => '请先验证卡密']);
            break;
        }

        $sourceId = $input['source_id'] ?? '';
        error_log("Source ID: " . $sourceId);
        
        if (empty($sourceId)) {
            error_log("Empty source ID");
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '订阅源ID不能为空']);
            break;
        }

        $sourcesData = readSources();
        $sources = $sourcesData['sources'] ?? [];
        
        // 查找指定的订阅源
        $targetSource = null;
        foreach ($sources as $source) {
            if ($source['id'] === $sourceId && $source['enabled']) {
                $targetSource = $source;
                break;
            }
        }
        
        if (!$targetSource) {
            error_log("Source not found or disabled: " . $sourceId);
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => '订阅源不存在或已禁用']);
            break;
        }

        error_log("Found source: " . json_encode($targetSource));

        // 获取订阅源内容
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $targetSource['url']);
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
                error_log("cURL error: " . $error);
                throw new Exception("cURL错误: " . $error);
            }
            
            if ($http_code !== 200) {
                error_log("HTTP error: " . $http_code);
                throw new Exception("HTTP错误: " . $http_code);
            }
            
            if (empty($content)) {
                error_log("Empty content received");
                throw new Exception("获取到的内容为空");
            }
            
            error_log("Content received, length: " . strlen($content));
            
            // 根据配置处理内容
            switch ($targetSource['decode_type']) {
                case 'base64':
                    $processed_content = base64_decode($content, true);
                    if ($processed_content === false) {
                        error_log("Base64 decode failed");
                        throw new Exception("Base64解码失败，内容可能不是有效的Base64编码");
                    }
                    $content = $processed_content;
                    error_log("Base64 decoded, new length: " . strlen($content));
                    break;
                    
                case 'base64_encode':
                    $content = base64_encode($content);
                    error_log("Content base64 encoded");
                    break;
                    
                case 'none':
                default:
                    error_log("No content processing needed");
                    break;
            }
            
            $response = [
                'success' => true,
                'content' => $content,
                'source' => [
                    'id' => $targetSource['id'],
                    'name' => $targetSource['name'],
                    'decode_type' => $targetSource['decode_type']
                ]
            ];
            error_log("Sending response with content length: " . strlen($content));
            echo json_encode($response);
            
        } catch (Exception $e) {
            error_log("Error in get_source_content: " . $e->getMessage());
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        break;

    default:
        error_log("Unknown action: " . $action);
        echo json_encode(['error' => '未知操作']);
        break;
}
?> 