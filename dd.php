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
    echo "<html>
	<head>
	<title>DNS 删除域名管理</title>
	<!--link rel='icon' type='image/x-icon' href='https://cdn4.iconfinder.com/data/icons/web-hosting-filled-line-1/100/web_hosting_colored_line_dns_padlock-64.png'-->
    <link rel='icon' type='image/x-icon' href='https://cdn-icons-png.flaticon.com/128/18405/18405093.png'>
	<style>
            body {
                font-family: 'Arial', sans-serif;
                background-color: #f4f6f9;
                color: #333;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 800px;
                margin: 50px auto;
                background-color: #fff;
                padding: 20px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                border-radius: 8px;
            }
            h1 {
                text-align: center;
                color: #333;
            }
            .domain {
                background-color: #fafafa;
                padding: 10px;
                margin-bottom: 20px;
                border-radius: 5px;
                border: 1px solid #ddd;
            }
            .domain h3 {
                margin: 0;
                color: #333;
            }
            .result {
                margin-top: 10px;
                padding: 10px;
                font-size: 14px;
            }
            .success {
                color: green;
            }
            .error {
                color: red;
            }
            hr {
                border: 0;
                border-top: 1px solid #eee;
            }
            .footer {
                text-align: center;
                margin-top: 30px;
                font-size: 14px;
                color: #777;
            }
            </style></head><body>";
    echo "<div class='container'>";
    echo "<h1>Cloudflare 域名批量删除</h1>";

    foreach ($domains as $domain) {
        echo "<div class='domain'>";
        echo "<h3>正在处理域名: {$domain}</h3>";

        // 获取域名的 Zone ID
        $zoneId = getZoneIdByDomain($domain);

        if ($zoneId) {
            echo "<div class='result'>获取域名: {$domain} 的 Zone ID: {$zoneId}</div>";

            // 删除 Zone
            $deleteSuccess = deleteZone($zoneId);

            if ($deleteSuccess) {
                echo "<div class='result success'>域名（Zone）删除成功！</div>";
            } else {
                echo "<div class='result error'>删除域名（Zone）失败！</div>";
            }
        } else {
            echo "<div class='result error'>获取域名: {$domain} 的 Zone ID 失败！</div>";
        }

        echo "<hr>";
        echo "</div>";
    }

    echo "</div>";
    echo "<div class='footer'>处理完毕</div>";
    echo "</body></html>";
} else {
    echo "<div class='error'>无法找到 domain.txt 文件！</div>";
}
?>
