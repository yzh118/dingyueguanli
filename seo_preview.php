// seo_preview.php - SEO标签预览
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <?php
    // 引入SEO生成器
    require_once 'seo_generator.php';
    
    // 获取SEO设置
    $seoSettings = getSeoSettings();
    $pageTitle = $seoSettings['seo_title'] ?? '测试页面';
    $pageDescription = $seoSettings['seo_description'] ?? '测试页面描述';
    $pageKeywords = $seoSettings['seo_keywords'] ?? '测试,关键词';
    
    outputSeoTags($pageTitle, $pageDescription, $pageKeywords);
    ?>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .info { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>SEO设置测试页面</h1>
    
    <div class="info">
        <h3>当前SEO设置:</h3>
        <pre><?php print_r($seoSettings); ?></pre>
    </div>
    
    <div class="info">
        <h3>页面标题: <?php echo htmlspecialchars($pageTitle); ?></h3>
        <h3>页面描述: <?php echo htmlspecialchars($pageDescription); ?></h3>
        <h3>页面关键词: <?php echo htmlspecialchars($pageKeywords); ?></h3>
    </div>
    
    <div class="info">
        <h3>生成的SEO标签:</h3>
        <pre><?php echo htmlspecialchars(generateSeoTags($pageTitle, $pageDescription, $pageKeywords)); ?></pre>
    </div>
    
    <p>请查看页面源代码，确认SEO标签是否正确生成。</p>
</body>
</html> 