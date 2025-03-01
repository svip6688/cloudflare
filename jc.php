<?php
set_time_limit(5000); // 设置最大执行时间为 5 分钟

// 你的其他代码

function getWhoisServer($tld) {
    // 常见域名后缀的 WHOIS 服务器列表
    $whoisServers = [
        "com" => "whois.verisign-grs.com",
        "net" => "whois.verisign-grs.com",
        "org" => "whois.pir.org",
        "cc"  => "whois.nic.cc",
        "me"  => "whois.nic.me",
        "cn"  => "whois.cnnic.cn",
        "xyz" => "whois.nic.xyz",
        "io"  => "whois.nic.io",
        "biz" => "whois.nic.biz",
        "info"=> "whois.afilias.net",
        "us"  => "whois.nic.us",
        "uk"  => "whois.nic.uk",
        "co"  => "whois.nic.co",
        "tv"  => "whois.nic.tv",
        "edu" => "whois.educause.edu",
    ];

    // 如果已知 WHOIS 服务器，直接返回
    if (isset($whoisServers[$tld])) {
        return $whoisServers[$tld];
    }

    // 如果未知后缀，则尝试从 IANA 获取 WHOIS 服务器
    $whoisServer = getWhoisFromIANA($tld);
    return $whoisServer ?: "whois.iana.org"; // 失败时返回默认 WHOIS 服务器
}

function getWhoisFromIANA($tld) {
    $ianaServer = "whois.iana.org";
    $port = 43;
    $connection = fsockopen($ianaServer, $port, $errno, $errstr, 10);
    if (!$connection) {
        return null; // 获取失败
    }

    fwrite($connection, $tld . "\r\n");
    $response = '';

    while (!feof($connection)) {
        $response .= fgets($connection, 1024);
    }
    fclose($connection);

    // 解析 WHOIS 服务器
    if (preg_match('/whois:\s+([^\s]+)/i', $response, $matches)) {
        return trim($matches[1]); // 提取 WHOIS 服务器地址
    }
    
    return null;
}

function checkDomainAvailability($domain) {
    $tld = strtolower(pathinfo($domain, PATHINFO_EXTENSION));
    $whoisServer = getWhoisServer($tld);
    
    if (!$whoisServer) {
        return "<span style='color: orange;'>Unsupported TLD</span>";
    }

    $port = 43;
    $connection = fsockopen($whoisServer, $port, $errno, $errstr, 10);
    if (!$connection) {
        return "<span style='color: red;'>Error: $errstr ($errno)</span>";
    }

    fwrite($connection, $domain . "\r\n");
    $response = '';
    while (!feof($connection)) {
        $response .= fgets($connection, 1024);
    }
    fclose($connection);

    // 解析 WHOIS 响应（不同服务器返回格式不同）
    if (stripos($response, "No match") !== false || stripos($response, "NOT FOUND") !== false || stripos($response, "Status: AVAILABLE") !== false) {
        return "<span style='color: green;'>可注册</span>";
    } else {
        return "<span style='color: red;'>已注册</span>";
    }
}

// 读取 zc.txt 获取域名列表
$filename = "zc.txt";
if (!file_exists($filename)) {
    die("❌ 错误: 文件 $filename 不存在！");
}

$domains = array_filter(array_map('trim', file($filename)));

if (empty($domains)) {
    die("❌ 错误: 文件 $filename 为空！");
}

$availableDomains = [];

echo "<h2>域名注册检测结果</h2><pre>";
foreach ($domains as $domain) {
    $status = checkDomainAvailability($domain);
    
    if (stripos($status, "Available") !== false) {
        echo "$domain: $status ✅<br>";
        $availableDomains[] = $domain;
    } else {
        echo "$domain: $status ❌<br>";
    }
}
echo "</pre>";

// 导出可注册的域名到 d.txt
if (!empty($availableDomains)) {
    file_put_contents("domain.txt", implode("\n", $availableDomains));
    echo "<p>✅ 可注册域名已导出到 <strong>domain.txt</strong></p>";
} else {
    echo "<p>❌ 没有可注册的域名。</p>";
}
?>
