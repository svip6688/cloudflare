<?php
set_time_limit(5000); // 设置最大执行时间为 5 分钟

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
        "uk" => "whois.nic.uk",
        "co" => "whois.nic.co",
        "tv" => "whois.nic.tv",
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
        return "<span class='status orange'>不支持的 TLD</span>";
    }

    $port = 43;
    $connection = fsockopen($whoisServer, $port, $errno, $errstr, 10);
    if (!$connection) {
        return "<span class='status red'>连接错误: $errstr ($errno)</span>";
    }

    fwrite($connection, $domain . "\r\n");
    $response = '';
    while (!feof($connection)) {
        $response .= fgets($connection, 1024);
    }
    fclose($connection);

    // 解析 WHOIS 响应（不同服务器返回格式不同）
    if (stripos($response, "No match") !== false || stripos($response, "NOT FOUND") !== false || stripos($response, "Status: AVAILABLE") !== false) {
        return "<span class='status green'>✔ 可注册</span>";
    } else {
        return "<span class='status red'>❌ 已注册</span>";
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

echo "<!DOCTYPE html>
<html lang='zh'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>域名注册检测</title>
	<!--link rel='icon' type='image/x-icon' href='https://cdn4.iconfinder.com/data/icons/web-hosting-filled-line-1/100/web_hosting_colored_line_dns_padlock-64.png'-->
    <link rel='icon' type='image/x-icon' href='https://cdn-icons-png.flaticon.com/128/18405/18405093.png'>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Arial', sans-serif;
            background: #f9f9f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        h2 {
            color: #333;
            font-size: 32px;
            margin-bottom: 30px;
        }
        table {
            width: 80%;
            margin-bottom: 30px;
            border-collapse: collapse;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 15px;
            text-align: center;
            position: relative;
            border: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        td {
            color: #555;
            font-size: 16px;
        }
        .status {
            font-weight: bold;
        }
        .green {
            color: #2ecc71;
        }
        .red {
            color: #e74c3c;
        }
        .orange {
            color: #f39c12;
        }
        .button {
            padding: 12px 24px;
            background: linear-gradient(145deg, #3498db, #2980b9);
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-size: 18px;
            box-shadow: 0 6px 8px rgba(0,0,0,0.1);
            transition: background 0.3s ease;
        }
        .button:hover {
            background: linear-gradient(145deg, #2980b9, #3498db);
        }
        .alert {
            font-size: 18px;
            margin-top: 20px;
            padding: 12px;
            color: #fff;
            border-radius: 8px;
        }
        .alert.success {
            background-color: #2ecc71;
        }
        .alert.error {
            background-color: #e74c3c;
        }
    </style>
</head>
<body>
    <h2>域名注册检测结果</h2>
    <table>
        <thead>
            <tr>
                <th>域名</th>
                <th>状态</th>
            </tr>
        </thead>
        <tbody>";

foreach ($domains as $domain) {
    $status = checkDomainAvailability($domain);
    
    // 如果是可注册的域名，则加入 availableDomains 数组
    if (stripos($status, "✔ 可注册") !== false) {
        $availableDomains[] = $domain;
    }

    // 将域名和状态显示在表格的一行
    echo "<tr>
            <td>$domain</td>
            <td>$status</td>
        </tr>";
}

echo "</tbody>
    </table>";

if (!empty($availableDomains)) {
    // 生成可注册域名文件
    file_put_contents("domain.txt", implode("\n", $availableDomains));

    // 显示下载按钮和导出信息
    echo "<a href='domain.txt' class='button'>下载可注册域名列表</a>";
    echo "<div class='alert success'>✅ 可注册域名已导出到 <strong>domain.txt</strong></div>";
} else {
    // 如果所有域名都不可注册
    echo "<div class='alert error'>❌ 所有域名均已注册</div>";
}

echo "</body></html>";
?>
