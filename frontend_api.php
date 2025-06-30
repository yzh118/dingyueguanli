<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

session_start();

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

$action = $_GET['action'] ?? '';

// 支持POST请求体中的action参数
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

switch ($action) {
    case 'verify_card':
        $input = json_decode(file_get_contents('php://input'), true);
        $card = $input['card'] ?? '';
        
        if (empty($card)) {
            http_response_code(400);
            echo json_encode(['error' => '卡密不能为空']);
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
            http_response_code(401);
            echo json_encode(['error' => '卡密无效或已禁用']);
            break;
        }

        // 设置session信息
        $_SESSION['card_code'] = $card;
        $_SESSION['card_id'] = $validCard['id'];

        echo json_encode([
            'success' => true,
            'card' => $validCard
        ]);
        break;

    case 'get_full_content':
        // 检查用户是否已通过卡密验证
        if (!isset($_SESSION['card_code']) || empty($_SESSION['card_code'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先验证卡密']);
            break;
        }

        $cardsData = json_decode(file_get_contents($cardsFile), true);
        $cards = $cardsData['cards'] ?? [];
        $sourcesData = json_decode(file_get_contents($sourcesFile), true);
        $sources = $sourcesData['sources'] ?? [];
        
        // 获取当前用户的卡密信息
        $currentCard = null;
        foreach ($cards as $card) {
            if ($card['card'] === $_SESSION['card_code']) {
                $currentCard = $card;
                break;
            }
        }
        
        if (!$currentCard) {
            http_response_code(401);
            echo json_encode(['error' => '卡密信息无效']);
            break;
        }

        // 过滤允许的订阅源
        $allowedSources = $currentCard['allowed_sources'] ?? ['all'];
        // 确保allowed_sources是数组
        if (!is_array($allowedSources)) {
            $allowedSources = ['all'];
        }
        $filteredSources = [];
        
        foreach ($sources as $source) {
            if (in_array('all', $allowedSources) || in_array($source['id'], $allowedSources)) {
                if ($source['enabled']) {
                    $filteredSources[] = $source;
                }
            }
        }

        // 生成HTML内容
        $html = '';
        
        if (!empty($filteredSources)) {
            $html .= '<div class="section-title">📡 选择订阅源</div>';
            $html .= '<div class="source-selector">';
            $html .= '<div class="source-dropdown">';
            $html .= '<button class="source-dropdown-btn" onclick="toggleDropdown()" id="dropdownBtn">';
            $html .= htmlspecialchars($filteredSources[0]['name']);
            $html .= '</button>';
            $html .= '<div class="source-dropdown-content" id="dropdownContent">';
            
            foreach ($filteredSources as $source) {
                $html .= '<div class="source-dropdown-item" onclick="switchSource(\'' . $source['id'] . '\', \'' . htmlspecialchars($source['name']) . '\')">';
                $html .= htmlspecialchars($source['name']);
                $html .= '</div>';
            }
            
            $html .= '</div>';
            $html .= '</div>';
            $html .= '</div>';
            
            $html .= '<button class="copy-btn" onclick="copyContent()">';
            $html .= '<i class="bi bi-clipboard"></i> 复制最新节点';
            $html .= '</button>';
            
            $html .= '<div class="result-box">';
            $html .= '<div class="result-content" id="decodedContent">正在加载订阅源内容...</div>';
            $html .= '</div>';
            
            // 自动加载第一个订阅源的内容
            $html .= '<script>';
            $html .= 'setTimeout(() => { switchSource(\'' . $filteredSources[0]['id'] . '\', \'' . htmlspecialchars($filteredSources[0]['name']) . '\'); }, 100);';
            $html .= '</script>';
        } else {
            $html .= '<div class="source-info">';
            $html .= '<h3>无可用订阅源</h3>';
            $html .= '<p>您当前没有可访问的订阅源。</p>';
            $html .= '</div>';
        }

        echo $html;
        break;

    case 'get_source_content':
        // 检查用户是否已通过卡密验证
        if (!isset($_SESSION['card_code']) || empty($_SESSION['card_code'])) {
            http_response_code(401);
            echo json_encode(['error' => '请先验证卡密']);
            break;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $sourceId = $input['source_id'] ?? '';
        
        if (empty($sourceId)) {
            http_response_code(400);
            echo json_encode(['error' => '订阅源ID不能为空']);
            break;
        }

        $cardsData = json_decode(file_get_contents($cardsFile), true);
        $cards = $cardsData['cards'] ?? [];
        $sourcesData = json_decode(file_get_contents($sourcesFile), true);
        $sources = $sourcesData['sources'] ?? [];
        
        // 获取当前用户的卡密信息
        $currentCard = null;
        foreach ($cards as $card) {
            if ($card['card'] === $_SESSION['card_code']) {
                $currentCard = $card;
                break;
            }
        }
        
        if (!$currentCard) {
            http_response_code(401);
            echo json_encode(['error' => '卡密信息无效']);
            break;
        }

        // 检查用户是否有权限访问该订阅源
        $allowedSources = $currentCard['allowed_sources'] ?? ['all'];
        if (!is_array($allowedSources)) {
            $allowedSources = ['all'];
        }
        
        if (!in_array('all', $allowedSources) && !in_array($sourceId, $allowedSources)) {
            http_response_code(403);
            echo json_encode(['error' => '您没有权限访问该订阅源']);
            break;
        }

        // 查找指定的订阅源
        $targetSource = null;
        foreach ($sources as $source) {
            if ($source['id'] === $sourceId && $source['enabled']) {
                $targetSource = $source;
                break;
            }
        }
        
        if (!$targetSource) {
            http_response_code(404);
            echo json_encode(['error' => '订阅源不存在或已禁用']);
            break;
        }

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
                throw new Exception("cURL错误: " . $error);
            }
            
            if ($http_code !== 200) {
                throw new Exception("HTTP错误: " . $http_code);
            }
            
            if (empty($content)) {
                throw new Exception("获取到的内容为空");
            }
            
            // 根据配置处理内容
            switch ($targetSource['decode_type']) {
                case 'base64':
                    $processed_content = base64_decode($content, true);
                    if ($processed_content === false) {
                        throw new Exception("Base64解码失败，内容可能不是有效的Base64编码");
                    }
                    break;
                    
                case 'base64_encode':
                    $processed_content = base64_encode($content);
                    break;
                    
                case 'none':
                default:
                    $processed_content = $content;
                    break;
            }
            
            echo json_encode([
                'success' => true,
                'content' => $processed_content,
                'source_name' => $targetSource['name']
            ]);
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => '获取失败: ' . $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => '无效的操作']);
        break;
}
?> 