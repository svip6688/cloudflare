<?php
$apiKey = "4555855665455"; // 替换成你的 NameSilo API Key     https://www.namesilo.com/account/api-manager
$domainsFile = "domain.txt";

// 读取域名列表
$domains = file($domainsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$availableDomains = [];

// 使用 cURL 发送 API 请求
function fetchXML($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    
    $response = curl_exec($ch);
    curl_close($ch);

    // 检查是否是有效的 XML
    $xml = simplexml_load_string($response);
    if (!$xml) {
        echo "API 响应不是有效的 XML。\n";
        exit;
    }
    return $xml;
}

// 批量查询域名是否可注册
$domainList = implode(",", $domains);
$checkUrl = "https://www.namesilo.com/api/checkRegisterAvailability?version=1&type=xml&key=$apiKey&domains=$domainList";
$response = fetchXML($checkUrl);

// 确保 API 响应正确
if ($response && isset($response->reply)) {
    // 解析可注册域名
    if (isset($response->reply->available->domain)) {
        foreach ($response->reply->available->domain as $domain) {
            $availableDomains[] = (string) $domain;
            echo "✅ 可注册: $domain\n";
        }
    }

    // 解析已注册域名
    if (isset($response->reply->registered->domain)) {
        foreach ($response->reply->registered->domain as $domain) {
            echo "❌ 不可注册: $domain\n";
        }
    }
} else {
    echo "API 请求失败，请检查 NameSilo API Key 或网络连接。\n";
    exit;
}

// 如果没有可注册的域名，则结束
if (empty($availableDomains)) {
    echo "🚫 没有可注册的域名。\n";
    exit;
}

// 将可注册的域名加入购物车（创建待支付订单）
foreach ($availableDomains as $domain) {
    $registerUrl = "https://www.namesilo.com/api/registerDomain?version=1&type=xml&key=$apiKey&domain=$domain&years=1&payment_id=default";
    $registerResponse = fetchXML($registerUrl);

    if ($registerResponse && isset($registerResponse->reply->code)) {
        if ($registerResponse->reply->code == 300) {
            echo "🛒 已加入购物车 (待支付): $domain\n";
        } else {
            echo "🚨 加入购物车失败 ($domain): " . ($registerResponse->reply->detail ?? "未知错误") . "\n";
        }
    } else {
        echo "API 响应无效，无法加入购物车: $domain\n";
    }
}

echo "\n✅ 请登录 NameSilo 账户完成支付: https://www.namesilo.com/account/orders\n";
?>
