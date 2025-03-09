<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 从 config.php 加载配置
$config = require('config.php');

// 获取 API Key 和 Email
$cloudflare_email = $config['cloudflare_email'];
$cloudflare_api_key = $config['cloudflare_api_key'];

function post_data($url, $post = null, $header = array(), $timeout = 8, $https = 0)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    if ($https) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    if ($header) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    }

    if ($post) {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
    }

    $content = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "cURL 错误: " . curl_error($ch) . PHP_EOL;
    }

    curl_close($ch);
    return $content;
}

// 统一的 API Header
$header = array(
    "X-Auth-Email: $cloudflare_email",
    "X-Auth-Key: $cloudflare_api_key",
    "Content-Type: application/json"
);

// 读取域名列表
$domain = preg_split('/\r\n|\r|\n/', file_get_contents('./domain.txt'));

$is_cli = php_sapi_name() === 'cli'; // 判断是否是 CLI 运行环境
$line_break = $is_cli ? PHP_EOL : "<br>\n"; // CLI 用换行符，浏览器用 <br>

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!--link rel='icon' type='image/x-icon' href='https://cdn4.iconfinder.com/data/icons/web-hosting-filled-line-1/100/web_hosting_colored_line_dns_padlock-64.png'-->
    <link rel='icon' type='image/x-icon' href='https://cdn-icons-png.flaticon.com/128/18405/18405093.png'>
    <title>域名添加状态</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f9;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
        }

        h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 20px;
        }

        .container {
            width: 80%;
            max-width: 800px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .domain-item {
            display: flex;
            flex-direction: column;
            padding: 15px;
            background-color: #fff;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .domain-item.success {
            background-color: #d4f7d6;
        }

        .domain-item.error {
            background-color: #f8d7da;
        }

        .domain-item h3 {
            margin: 0;
            font-size: 18px;
            color: #333;
        }

        .domain-item .status {
            font-size: 16px;
            margin-top: 5px;
        }

        .domain-item .details {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }

        .divider {
            height: 1px;
            background-color: #eee;
            margin: 15px 0;
        }

        .button-container {
            display: flex;
            justify-content: center;
            gap: 15px;
        }

        button {
            padding: 10px 20px;
            background-color: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
            margin-top: 5px; /* 将按钮向下移动5px */
        }

        button:hover {
            background-color: #2980b9;
        }

    </style>
</head>
<body>

<h1>Cloudflare 域名添加状态</h1>

<div class="container">
    <?php
    foreach ($domain as $v_domain) {
        if (empty($v_domain)) continue;

        $url = "https://api.cloudflare.com/client/v4/zones";
        $post = json_encode(array(
            "name" => $v_domain,
            "jump_start" => true // 跳过 DNS 设置，直接让 Cloudflare 添加域名
        ));

        // 发送请求添加域名
        $rs = post_data($url, $post, $header, 8, 1);
        $rs = json_decode($rs, true);

        if (!$rs || !isset($rs['success'])) {
            echo "<div class='domain-item error'>";
            echo "<h3>❌ API 响应无效</h3>";
            echo "<p class='status'>请求失败</p>";
            echo "<p class='details'>无法处理域名: " . htmlspecialchars($v_domain) . "</p>";
            echo "</div>";
            continue;
        }

        if ($rs['success'] == false) {
            echo "<div class='domain-item error'>";
            echo "<h3>❌ 添加失败 - " . htmlspecialchars($v_domain) . "</h3>";
            echo "<p class='status'>错误原因</p>";
            echo "<pre class='details'>" . json_encode($rs['errors'], JSON_PRETTY_PRINT) . "</pre>";
            echo "</div>";
            continue;
        }

        echo "<div class='domain-item success'>";
        echo "<h3>✅ 添加成功 - " . htmlspecialchars($v_domain) . "</h3>";
        echo "<p class='status'>📌 域名 ID: " . $rs['result']['id'] . "</p>";
        echo "<p class='status'>🌍 域名: <b>" . htmlspecialchars($rs['result']['name']) . "</b></p>";
        echo "<p class='status'>📢 状态: <b>" . htmlspecialchars($rs['result']['status']) . "</b></p>";
        echo "</div>";

        echo "<div class='divider'></div>";
    }
    ?>
</div>

<div class="button-container">
    <button onclick="window.location.reload()">重新加载</button>
</div>

</body>
</html>
