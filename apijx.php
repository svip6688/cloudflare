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

// 获取域名列表（从文件加载或 API 获取）
$zones = $responseData['result'];
$filename = 'domain.txt';
$domains = [];

if (file_exists($filename)) {
    // 从 domain.txt 文件读取域名
    $domains = array_filter(array_map('trim', file($filename))); // 去除空行
}

if (empty($domains)) {
    // 如果 domain.txt 为空，加载 API 中的域名
    foreach ($zones as $zone) {
        $domains[] = $zone['name'];
    }
}

// 设置输出页面的基本 HTML 结构
echo "<!DOCTYPE html>
<html lang='zh'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>DNS 记录管理</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f9f9f9;
        }
        h2 {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        label {
            font-weight: bold;
            margin-top: 10px;
        }
        input, select, button {
            width: 100%;
            padding: 12px;
            margin-top: 8px;
            margin-bottom: 12px;
            border-radius: 4px;
            border: 1px solid #ddd;
            font-size: 14px;
        }
        button {
            background-color: #3498db;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background-color: #2980b9;
        }
        .checkbox-group label {
            display: block;
            margin-left: 10px;
        }
        .checkbox-group input {
            margin-right: 10px;
        }
        #searchBox {
            margin-bottom: 10px;
            padding: 8px;
            width: 100%;
            font-size: 14px;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .checkbox-group {
            max-height: 200px;
            overflow-y: auto;
        }
        .checkbox-group input {
            margin-right: 10px;
        }
    </style>
</head>
<body>
<h2>Cloudflare DNS 记录管理</h2>

<div class='form-container'>
    <form action='' method='POST'>
        
        <!-- 域名选择框和全选复选框 -->
        <div style='margin-bottom: 20px;'>
            <label><input type='checkbox' id='selectAll'> 全选所有域名</label>
        </div>
        
        <label for='zone'>选择域名 (Zone):</label>
        <select name='zone' id='zone'>
            <option value=''>请选择域名</option>";

foreach ($zones as $zone) {
    echo "<option value='" . $zone['id'] . "'>" . $zone['name'] . "</option>";
}

echo "</select>

        <label for='type'>记录类型:</label>
        <select name='type' id='type'>
            <option value='A'>A 记录</option>
            <option value='AAAA'>AAAA 记录</option>
            <option value='CNAME'>CNAME 记录</option>
            <option value='MX'>MX 记录</option>
            <option value='TXT'>TXT 记录</option>
            <option value='NS'>NS 记录</option>
            <option value='SOA'>SOA 记录</option>
            <option value='SRV'>SRV 记录</option>
            <option value='PTR'>PTR 记录</option>
        </select>

        <label for='name'>子域名名称 (空表示根域名):</label>
        <input type='text' name='name' id='name' required>

        <label for='content'>内容 (IP 或目标域名):</label>
        <input type='text' name='content' id='content' required>

        <label for='ttl'>TTL:</label>
        <select name='ttl' id='ttl'>
            <option value='1'>自动</option>
            <option value='3600'>3600 秒</option>
            <option value='86400'>86400 秒</option>
        </select>

        <label for='proxied'>是否启用 Cloudflare 代理:</label>
        <select name='proxied' id='proxied'>
            <option value='false'>关闭</option>
            <option value='true'>开启</option>
        </select>

        <label for='domains'>选择域名 (从 domain.txt 加载):</label>
        <input type='text' id='searchBox' onkeyup='filterDomains()' placeholder='搜索域名...'>
        
        <div class='checkbox-group' id='domainList'>
            <label><input type='checkbox' id='selectAll'> 全选所有域名</label>";

foreach ($domains as $domain) {
    echo "<label><input type='checkbox' name='domains[]' value='" . htmlspecialchars($domain) . "' class='domainCheckbox'> " . htmlspecialchars($domain) . "</label>";
}

echo "</div>

        <button type='submit'>添加记录</button>
    </form>
</div>

<script>
    // 搜索域名功能
    function filterDomains() {
        var input = document.getElementById('searchBox');
        var filter = input.value.toLowerCase();
        var domainList = document.getElementById('domainList');
        var domains = domainList.getElementsByTagName('label');

        for (var i = 0; i < domains.length; i++) {
            var domain = domains[i].innerText || domains[i].textContent;
            if (domain.toLowerCase().indexOf(filter) > -1) {
                domains[i].style.display = '';
            } else {
                domains[i].style.display = 'none';
            }
        }
    }

    // 全选功能
    document.getElementById('selectAll').addEventListener('change', function () {
        var checkboxes = document.querySelectorAll('.domainCheckbox');
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = this.checked;
        });
    });
</script>

</body>
</html>";


// 如果表单提交
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $zoneId = $_POST['zone'];
    $type = $_POST['type'];
    $name = $_POST['name'];
    $content = $_POST['content'];
    $ttl = $_POST['ttl'];
    $proxied = $_POST['proxied'] === 'true' ? true : false;
    $selectedDomains = $_POST['domains'] ?? [];

    if ($zoneId && $type && $name && $content && !empty($selectedDomains)) {
        // 遍历所有选中的域名，添加记录
        foreach ($selectedDomains as $domain) {
            // 构造 DNS 记录数据
            $dnsRecord = [
                "type" => $type,
                "name" => $name,
                "content" => $content,
                "ttl" => $ttl,
                "proxied" => $proxied
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

            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            $response = curl_exec($ch);
            $responseData = json_decode($response, true);
            curl_close($ch);

            if ($responseData['success']) {
                echo "<p>✅ 域名 " . htmlspecialchars($domain) . " 的记录添加成功！</p>";
            } else {
                echo "<p>❌ 域名 " . htmlspecialchars($domain) . " 的记录添加失败！</p>";
            }
        }
    }
}

?>
