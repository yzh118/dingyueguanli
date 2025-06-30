<?php
// seo_debug.php - SEO调试与测试
// 测试SEO设置
echo "<h2>SEO设置测试</h2>";

// 测试读取SEO设置
$cardsFile = __DIR__ . '/private/cards.json';
if (file_exists($cardsFile)) {
    $cardsData = json_decode(file_get_contents($cardsFile), true);
    $seoSettings = $cardsData['seo_settings'] ?? [];
    
    echo "<h3>当前SEO设置:</h3>";
    echo "<pre>" . print_r($seoSettings, true) . "</pre>";
    
    // 测试保存SEO设置
    echo "<h3>测试保存SEO设置:</h3>";
    $testSeoSettings = [
        'seo_title' => '测试网站标题',
        'seo_description' => '测试网站描述',
        'seo_keywords' => '测试,关键词',
        'seo_author' => '测试作者',
        'og_title' => '测试OG标题',
        'og_description' => '测试OG描述',
        'og_image' => 'https://example.com/test.jpg',
        'og_type' => 'website',
        'favicon_url' => 'https://example.com/favicon.ico',
        'apple_touch_icon' => 'https://example.com/apple-touch-icon.png',
        'bing_verification' => 'test_bing_verification',
        'google_verification' => 'test_google_verification',
        'enable_sitemap' => true
    ];
    
    $cardsData['seo_settings'] = $testSeoSettings;
    
    if (file_put_contents($cardsFile, json_encode($cardsData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
        echo "<p style='color: green;'>SEO设置保存成功</p>";
        
        // 重新读取验证
        $cardsData = json_decode(file_get_contents($cardsFile), true);
        $seoSettings = $cardsData['seo_settings'] ?? [];
        
        echo "<h3>保存后的SEO设置:</h3>";
        echo "<pre>" . print_r($seoSettings, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>SEO设置保存失败</p>";
    }
} else {
    echo "<p style='color: red;'>cards.json文件不存在</p>";
}

// 测试SEO生成器
echo "<h3>测试SEO生成器:</h3>";
require_once 'seo_generator.php';

$seoSettings = getSeoSettings();
echo "<p>SEO设置读取结果:</p>";
echo "<pre>" . print_r($seoSettings, true) . "</pre>";

echo "<h3>生成的SEO标签:</h3>";
echo "<div style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
echo "<pre>" . htmlspecialchars(generateSeoTags('测试页面标题', '测试页面描述', '测试,关键词')) . "</pre>";
echo "</div>";
?> 