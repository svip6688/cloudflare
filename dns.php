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
        echo "❌ API 响应无效" . $line_break . $line_break;
        continue;
    }

    if ($rs['success'] == false) {
        echo "❌ 添加失败 - <b style='color: blue;'>" . htmlspecialchars($v_domain) . "</b>" . $line_break;
        echo "错误原因: " . json_encode($rs['errors'], JSON_PRETTY_PRINT) . $line_break . $line_break;
        continue;
    }

    echo "✅ 添加域名成功" . $line_break;
    echo "📌 域名 ID： " . $rs['result']['id'] . $line_break;
    echo "🌍 域名： <b style='color: green;'>" . htmlspecialchars($rs['result']['name']) . "</b><br>\n";
    echo "📢 状态： <b style='color: red;'>" . htmlspecialchars($rs['result']['status']) . "</b> (pending 表示域名商DNS未生效或未添加)" . $line_break;
    echo "<hr>" . $line_break; // 分隔符，让每个域名分开显示
}






//     foreach ($record as $v_record)
//     {
//         // 添加解析
//       //   $url_add_records = "https://api.cloudflare.com/client/v4/zones/$zoneid/dns_records";
// 
//         $record_detail = explode(',', $v_record);
//         $name = strtolower($record_detail[0]);
//         $type = strtoupper($record_detail[1]);
//         $ip   = $record_detail[2];
//         $post = array(
//             "type"     => $type,
//             "name"     => $name,
//             "content"  => $ip,
//             "ttl"      => 3600, // 1 为自动，此处单位为秒，也就是1小时
//             "priority" => 10,
//             "proxied"  => false // true 为开启 dns and http proxy (cdn)
//         );
// 
//         $post = json_encode($post);
//         $add_records_rs = post_data($url_add_records, $post, $header, 8, 1);
//         $rs = json_decode($add_records_rs, true);
//         if ($rs['success'] == false)
//         {
//             echo '记录添加失败，错误原因：' . $rs['errors'][0]['message'] . "\n";
//         }
//         else
//         {
//             echo '记录添加成功' . "\n";
//         }
//     }
// 

?>