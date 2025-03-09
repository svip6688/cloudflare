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
curl_close($ch);

// 处理响应数据
$responseData = json_decode($response, true);

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
		<!--link rel='icon' type='image/x-icon' href='https://cdn4.iconfinder.com/data/icons/web-hosting-filled-line-1/100/web_hosting_colored_line_dns_padlock-64.png'-->
        <link rel='icon' type='image/x-icon' href='https://cdn-icons-png.flaticon.com/128/18405/18405093.png'>
        <title>DNS 解析结果</title>
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background-color: #f5f5f5;
                color: #333;
                margin: 0;
                padding: 0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
            }
            h2 {
                text-align: center;
                font-size: 28px;
                color: #333;
                margin-bottom: 20px;
            }
            .status-success {
                color: #27ae60;
            }
            .status-error {
                color: #e74c3c;
            }
            .domain-item {
                background-color: white;
                padding: 15px;
                border-radius: 5px;
                margin: 10px 0;
                font-size: 16px;
                box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .domain-item.success {
                border-left: 5px solid #27ae60;
            }
            .domain-item.error {
                border-left: 5px solid #e74c3c;
            }
            .domain-item .status {
                font-size: 14px;
            }
            .btn-container {
                display: flex;
                justify-content: center;
                margin-top: 30px;
            }
            button {
                padding: 12px 20px;
                background-color: #3498db;
                color: white;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                font-size: 16px;
                transition: background-color 0.3s;
                margin: 0 10px;
            }
            button:hover {
                background-color: #2980b9;
            }
        </style>
    </head>
    <body>

    <div>
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
            // 创建多个 DNS 记录：

            $dnsRecords = [
                // A记录 (带www)
                [
                    "type" => "A",  // 记录类型为 A 记录
                    "name" => "www",  // 子域名
                    "content" => "114.114.114.114",  // 目标 IP
                    "ttl" => 1,  // 自动 TTL
                    "proxied" => false  // 是否通过 Cloudflare 代理   true开启 false关闭
                ],
                // A记录 (不带www)
                [
                    "type" => "A",  // 记录类型为 A 记录
                    "name" => "@",  // 空白表示根域名
                    "content" => "114.114.114.114",  // 目标 IP
                    "ttl" => 1,  // 自动 TTL
                    "proxied" => false  // 是否通过 Cloudflare 代理
                ],
				// AAAA记录 (带www)
                [
                    "type" => "AAAA",  // 记录类型为 AAAA 记录
                    "name" => "www",  // 空白表示根域名
                    "content" => "2a0b:4e07:8:1::848",  // 目标 IP
                    "ttl" => 1,  // 自动 TTL
                    "proxied" => false  // 是否通过 Cloudflare 代理
                ],
				// AAAA记录 (不带www)
                [
                    "type" => "AAAA",  // 记录类型为 AAAA 记录
                    "name" => "@",  // 空白表示根域名
                    "content" => "2a0b:4e07:8:1::848",  // 目标 IP
                    "ttl" => 1,  // 自动 TTL
                    "proxied" => false  // 是否通过 Cloudflare 代理
                ],
                // CNAME记录: blog.域名 -> example.com
                [
                    "type" => "CNAME",
                    "name" => "blog",  // 子域名
                    "content" => "example.com",  // 目标域名
                    "ttl" => 1,  // 自动 TTL
                    "proxied" => false
                ],
                // MX记录: mail.域名 -> mail.example.com
                [
                    "type" => "MX",
                    "name" => "mail",  // 子域名
                    "content" => "mail.example.com",  // 邮件服务器地址
                    "priority" => 10,  // 优先级
                    "ttl" => 1,  // 自动 TTL
                    "proxied" => false
                ]
            ];

            // 逐一添加 DNS 记录
            foreach ($dnsRecords as $dnsRecord) {
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
                        $status = "✅ $dnsRecord[name] 记录添加成功";
                        $statusClass = "status-success"; // 绿色
                    } else {
                        $status = "❌ 添加失败: " . $responseData['errors'][0]['message'];
                        $statusClass = "status-error"; // 红色
                    }
                }

                // 输出每个记录的状态
                echo "<div class='domain-item $statusClass'>
                        <span>$domain</span>
                        <span class='status'>$status</span>
                      </div>";
            }
        } else {
            $status = "❌ 未找到对应的 zoneId";
            $statusClass = "status-error"; // 红色
            echo "<div class='domain-item $statusClass'>
                    <span>$domain</span>
                    <span class='status'>$status</span>
                  </div>";
        }
    }

    // 结束 HTML 输出
    echo "</div>
        <div class='btn-container'>
            <button onclick='window.location.reload()'>重新加载</button>
        </div>
    </div>

    </body>
    </html>";
} else {
    echo "❌ 无法获取 zones 数据，请检查 API Key 和邮箱是否正确。";
}

?>
