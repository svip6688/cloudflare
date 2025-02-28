<?php
// 加载配置文件
$config = require('config.php');

// 从配置文件中获取 Cloudflare API 配置
$headers = [
    'Content-Type: application/json',
    'X-Auth-Email: ' . $config['cloudflare_email'],  // 使用配置中的邮箱
    'X-Auth-Key: ' . $config['cloudflare_api_key'],  // 使用配置中的 API Key
];

function getZoneIdByDomain($domain) {
    global $headers;

    $baseUrl = "https://api.cloudflare.com/client/v4";  // Cloudflare API 基础 URL
    $url = $baseUrl . "/zones?name={$domain}";  // 获取 Zone ID 的 API URL

    // 初始化 cURL 会话
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  // 设置 HTTP 头
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 禁用 SSL 证书验证（可选）

    // 执行请求
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 调试输出
    if ($httpCode == 200) {
        $data = json_decode($response, true);
        if (isset($data['result'][0]['id'])) {
            return $data['result'][0]['id'];  // 返回第一个 Zone ID
        }
    }

    return false;  // 返回 false 表示获取失败
}

function deleteZone($zoneId) {
    global $headers;

    $baseUrl = "https://api.cloudflare.com/client/v4";  // Cloudflare API 基础 URL
    $url = $baseUrl . "/zones/{$zoneId}";  // 删除 Zone 的 API URL

    // 初始化 cURL 会话
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);  // 设置 HTTP 头
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');  // 设置删除请求方法
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 禁用 SSL 证书验证（可选）

    // 执行请求
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // 判断是否删除成功
    if ($httpCode == 200) {
        return true;
    } else {
        return false;
    }
}

// 从 domain.txt 读取域名并处理
$file = 'domain.txt';  // 读取域名文件

if (file_exists($file)) {
    $domains = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);  // 读取每一行的域名
    echo "<html><body>";  // 开始 HTML 输出

    foreach ($domains as $domain) {
        echo "<div>";

        echo "<strong>正在处理域名: {$domain}</strong><br>";

        // 获取域名的 Zone ID
        $zoneId = getZoneIdByDomain($domain);

        if ($zoneId) {
            echo "获取域名: {$domain} 的 Zone ID: {$zoneId}<br>";

            // 删除 Zone
            $deleteSuccess = deleteZone($zoneId);

            if ($deleteSuccess) {
                echo "<span style='color: green;'>域名（Zone）删除成功！</span><br>";
            } else {
                echo "<span style='color: red;'>删除域名（Zone）失败！</span><br>";
            }
        } else {
            echo "<span style='color: red;'>获取域名: {$domain} 的 Zone ID 失败！</span><br>";
        }

        echo "<hr>";  // 分割线
        echo "</div>";
    }

    echo "</body></html>";  // 结束 HTML 输出
} else {
    echo "无法找到 domain.txt 文件！";
}
?>
