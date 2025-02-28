<?php
function generateDomains($prefix, $start, $end, $suffix) {
    $domains = [];
    for ($i = $start; $i <= $end; $i++) {
        $domains[] = $prefix . $i . '.' . $suffix;
    }
    return $domains;
}

$prefix = "admin";
$start = 1;
$end = 99;
$suffix = "cc";

$domains = generateDomains($prefix, $start, $end, $suffix);

$file = fopen("zc.txt", "w");
foreach ($domains as $domain) {
    fwrite($file, $domain . "\n");
}
fclose($file);

echo "域名保存到 zc.txt";
