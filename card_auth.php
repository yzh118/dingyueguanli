<?php
session_start();

// 禁用错误显示到页面
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 设置内容类型
header('Content-Type: application/json; charset=utf-8');

// 检查管理员登录状态
function checkAdminAuth() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        http_response_code(403);
        echo json_encode(['error' => '未授权访问']);
        exit;
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

// 保存卡密配置
function saveCards($data) {
    try {
        $result = file_put_contents(__DIR__ . '/private/cards.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if ($result === false) {
            throw new Exception("无法写入cards.json文件");
        }
        return true;
    } catch(Exception $e) {
        throw new Exception("保存失败: " . $e->getMessage());
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

// 使用卡密
function useCard($cardCode, $userInfo = null) {
    $cards = readCards();
    
    foreach ($cards['cards'] as &$card) {
        if ($card['card'] === $cardCode && $card['status'] === 'active') {
            $card['used_at'] = date('Y-m-d H:i:s');
            $card['used_by'] = $userInfo ?: $_SERVER['REMOTE_ADDR'];
            saveCards($cards);
            return true;
        }
    }
    
    return false;
}

// 处理API请求
$action = $_REQUEST['action'] ?? '';

switch ($action) {
    case 'validate':
        if (!isset($_POST['card_code']) || empty($_POST['card_code'])) {
            echo json_encode(['error' => '卡密不能为空']);
            exit;
        }
        
        $result = validateCard($_POST['card_code']);
        echo json_encode($result);
        break;
        
    case 'check_status':
        if (!isset($_SESSION['card_code'])) {
            echo json_encode(['valid' => false, 'message' => '未登录']);
            exit;
        }
        
        // 检查卡密是否仍然有效
        $result = validateCard($_SESSION['card_code']);
        if (!$result['valid']) {
            // 如果卡密无效，清除session
            session_destroy();
        }
        echo json_encode($result);
        break;
        
    case 'logout':
        // 清除session
        session_destroy();
        echo json_encode(['success' => true]);
        break;
        
    case 'use_card':
        // 公开API，不需要管理员权限
        $cardCode = $_POST['card_code'] ?? '';
        $userInfo = $_POST['user_info'] ?? null;
        
        if (empty($cardCode)) {
            echo json_encode(['error' => '卡密不能为空']);
            exit;
        }
        
        $result = useCard($cardCode, $userInfo);
        echo json_encode(['success' => $result]);
        break;
        
    case 'get_cards':
        // 需要管理员权限
        checkAdminAuth();
        $cards = readCards();
        echo json_encode($cards);
        break;
        
    case 'add_card':
        // 需要管理员权限
        checkAdminAuth();
        $cards = readCards();
        
        $expireDays = isset($_POST['expire_days']) ? intval($_POST['expire_days']) : 30;
        if ($expireDays < -1 || $expireDays > 3650) {
            echo json_encode(['error' => '无效的有效期设置']);
            exit;
        }
        
        $newCard = [
            'id' => 'card_' . time() . '_' . rand(1000, 9999),
            'card' => $_POST['code'],
            'name' => $_POST['name'],
            'status' => 'active',
            'created_at' => date('Y-m-d H:i:s'),
            'used_at' => null,
            'used_by' => null,
            'expire_days' => $expireDays
        ];
        
        $cards['cards'][] = $newCard;
        saveCards($cards);
        
        echo json_encode(['success' => true, 'card' => $newCard]);
        break;
        
    case 'delete_card':
        // 需要管理员权限
        checkAdminAuth();
        $cards = readCards();
        $cardCode = $_POST['code'];
        
        $cards['cards'] = array_filter($cards['cards'], function($card) use ($cardCode) {
            return $card['card'] !== $cardCode;
        });
        $cards['cards'] = array_values($cards['cards']);
        
        saveCards($cards);
        echo json_encode(['success' => true]);
        break;
        
    case 'update_card_settings':
        // 需要管理员权限
        checkAdminAuth();
        $cards = readCards();
        
        $cards['settings']['global_card_required'] = isset($_POST['global_card_required']);
        
        saveCards($cards);
        echo json_encode(['success' => true]);
        break;
        
    default:
        echo json_encode(['error' => '未知操作']);
        break;
}
?> 