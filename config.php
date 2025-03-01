<?php
return [
    // Cloudflare 账户的邮箱
    'cloudflare_email' => 'admin@gmail.com',

    // Cloudflare 账户的 Global API Key ⚠️ 请替换为你的真实 API Key，请从 https://dash.cloudflare.com/profile/api-tokens 查
    'cloudflare_api_key' => '315551111155555e7',

    // 手动指定的 zoneId，或者可以通过 API 获取
    'zoneId' => 'your-zone-id',  // 在这里填写你的zoneId,单个域名解析需要用到，其他功能无用
];
