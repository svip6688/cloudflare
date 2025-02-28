<?php

// 从 config.php 加载配置
$config = require('config.php');

// 获取 API 配置
$apiToken = $config['cloudflare_api_key'];  // Global API Key
$email = $config['cloudflare_email'];  // Cloudflare 账户邮箱

// 获取所有 zones，自动获取 zoneId
$zonesUrl = "https://api.cloudflare.com/client/v4/zones";

// 获取所有 zones
$ch = curl_init($zonesUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Auth-Email: $email",
    "X-Auth-Key: $apiToken",
]);

// 禁用 SSL 证书验证
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 禁用 SSL 验证
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 禁用主机验证

// 执行请求并获取响应
$response = curl_exec($ch);
$responseData = json_decode($response, true);
curl_close($ch);

// 检查请求是否成功
if (isset($responseData['success']) && $responseData['success']) {
    // 获取所有 zones
    $zones = $responseData['result'];

    // 从 domain.txt 读取域名列表
    $domains = [];
    $filename = 'domain.txt';
    if (file_exists($filename)) {
        $domains = array_filter(array_map('trim', file($filename))); // 去掉多余的空格和换行
    } else {
        die("❌ 错误: 文件 $filename 不存在！");
    }

    // 确保文件不为空
    if (empty($domains)) {
        die("❌ 错误: 文件 $filename 为空！");
    }

    // 设置输出页面的基本 HTML 结构
    echo "<!DOCTYPE html>
    <html lang='zh'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>DNS 解析结果</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
                background-color: #f4f4f4;
            }
            h2 {
                text-align: center;
            }
            .status-success {
                color: green;
            }
            .status-error {
                color: red;
            }
            .domain-item {
                font-size: 16px;
                margin: 5px 0;
            }
        </style>
    </head>
    <body>
    
    <h2>DNS 解析结果</h2>
    <div>";

    // 批量解析 DNS 记录并显示结果
    foreach ($domains as $domain) {
        // 遍历 zones，找到该域名的 zoneId
        $zoneId = null;
        foreach ($zones as $zone) {
            if (strpos($domain, $zone['name']) !== false) { // 匹配域名所属 zone
                $zoneId = $zone['id'];
                break;
            }
        }

        if ($zoneId) {
            // 添加 A 记录: www.域名 -> 114.114.114.114
            $dnsRecord = [
                "type" => "A",  // 记录类型为 A 记录
                "name" => "www",  // 子域名
                "content" => "114.114.114.114",  // 目标 IP
                "ttl" => 3600,  // TTL 时间
                "proxied" => false  // 是否通过 Cloudflare 代理   true开启 false关闭
            ];

            $dnsUrl = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records";
            $data = json_encode($dnsRecord);

            $ch = curl_init($dnsUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "X-Auth-Email: $email",
                "X-Auth-Key: $apiToken",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

            // 禁用 SSL 证书验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // 禁用 SSL 验证
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);  // 禁用主机验证

            // 执行请求并获取响应
            $response = curl_exec($ch);
            $responseData = json_decode($response, true);
            curl_close($ch);

            // 检查请求是否成功
            if ($response === false) {
                $status = "❌ 错误: " . curl_error($ch);
                $statusClass = "status-error"; // 红色
            } else {
                if (isset($responseData['success']) && $responseData['success'] == true) {
                    $status = "✅ www.$domain 添加成功";
                    $statusClass = "status-success"; // 绿色
                } else {
                    $status = "❌ 添加失败: " . $responseData['errors'][0]['message'];
                    $statusClass = "status-error"; // 红色
                }
            }
        } else {
            $status = "❌ 未找到对应的 zoneId";
            $statusClass = "status-error"; // 红色
        }

        // 输出每个域名的状态
        echo "<div class='domain-item $statusClass'>$domain: $status</div>";
    }

    // 结束 HTML 输出
    echo "</div>
    </body>
    </html>";
} else {
    echo "❌ 无法获取 zones 数据，请检查 API Key 和邮箱是否正确。";
}
?>
