<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 引入配置文件，获取 Cloudflare API 邮箱和 API 密钥
$config = include('config.php');
$cloudflare_email = $config['cloudflare_email'];
$cloudflare_api_key = $config['cloudflare_api_key'];

// 云flare API 请求的通用函数
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

// 获取 zoneId 和 DNS 记录 ID
function get_zoneId_and_dnsRecordId($domain, $cloudflare_email, $cloudflare_api_key)
{
    $header = array(
        "X-Auth-Email: $cloudflare_email",
        "X-Auth-Key: $cloudflare_api_key",
        "Content-Type: application/json"
    );

    // 获取 zoneId
    $url = "https://api.cloudflare.com/client/v4/zones?name=$domain";
    $response = post_data($url, null, $header, 8, 1);
    $data = json_decode($response, true);

    if (isset($data['result']) && is_array($data['result']) && count($data['result']) > 0) {
        $zoneId = $data['result'][0]['id'];
    } else {
        return null; // 返回 null 以便跳过当前域名
    }

    // 获取 DNS 记录 ID
    $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records";
    $response = post_data($url, null, $header, 8, 1);
    $data = json_decode($response, true);

    if (isset($data['result']) && is_array($data['result']) && count($data['result']) > 0) {
        $dnsRecordId = $data['result'][0]['id'];
        return ['zoneId' => $zoneId, 'dnsRecordId' => $dnsRecordId];
    } else {
        return null; // 返回 null 以便跳过当前域名
    }
}

// 删除 DNS 记录
function delete_dns_record($zoneId, $dnsRecordId, $cloudflare_email, $cloudflare_api_key)
{
    $header = array(
        "X-Auth-Email: $cloudflare_email",
        "X-Auth-Key: $cloudflare_api_key",
        "Content-Type: application/json"
    );

    $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records/$dnsRecordId";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE"); // DELETE 请求方法
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "cURL 错误: " . curl_error($ch) . "\n";
    }
    curl_close($ch);

    $data = json_decode($response, true);
    if (isset($data['success']) && $data['success']) {
        return "✅ 删除成功 - DNS 记录 ID: $dnsRecordId";
    } else {
        return "❌ 删除失败 - DNS 记录 ID: $dnsRecordId\n错误原因: " . json_encode($data['errors'], JSON_PRETTY_PRINT);
    }
}

// 检查文件路径，确保域名文件存在
$domainFilePath = './domain.txt';

if (!file_exists($domainFilePath)) {
    die("❌ 找不到域名文件：$domainFilePath\n");
}

// 从文件中读取域名列表
$domains = file($domainFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

if ($domains === false) {
    die("❌ 无法读取域名文件：$domainFilePath\n");
}

echo "<html><body>"; // 开始 HTML 输出

foreach ($domains as $domain) {
    echo "<div>正在处理域名: $domain<br>";

    // 获取 zoneId 和 dnsRecordId
    $result = get_zoneId_and_dnsRecordId($domain, $cloudflare_email, $cloudflare_api_key);
    if (!$result) {
        echo "❌ 获取信息失败 - 域名: $domain<br>";
        echo "<hr>"; // 输出分割线
        continue;  // 跳过当前域名
    }

    $zoneId = $result['zoneId'];
    $dnsRecordId = $result['dnsRecordId'];

    // 删除 DNS 记录并输出结果
    $delete_result = delete_dns_record($zoneId, $dnsRecordId, $cloudflare_email, $cloudflare_api_key);
    echo "$delete_result<br>";

    echo "<hr>"; // 输出分割线
}

echo "</body></html>"; // 结束 HTML 输出
?>
