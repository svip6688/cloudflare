cloudflare批量添加域名及添加dns解析记录php脚本

原理：
程序会循环读取domain.txt的每一行通过cloudflare接口添加，并将record.txt中的记录循环读取添加到每一个域名中。
接口参考：https://api.cloudflare.com/

目前支持A、CNAME记录批量添加。

配置方法：

1、首先修改config.php中
return [
    // Cloudflare 账户的邮箱
    'cloudflare_email' => 'admin@gmail.com',

    // Cloudflare 账户的 Global API Key ⚠️ 请替换为你的真实 API Key，请从 https://dash.cloudflare.com/profile/api-tokens 查
    'cloudflare_api_key' => '315551111155555e7',

    // 手动指定的 zoneId，或者可以通过 API 获取
    'zoneId' => 'your-zone-id',  // 在这里填写你的zoneId,单个域名解析需要用到，其他功能无用
];

X-Auth-Email及X-Auth-Key参数从cloudflare后台中查看。其它部分代码不需修改。

2、domain.txt为待加入的域名列表，一行一个，文件编码为utf-8，格式为windows（cr lf）格式（务必）。推荐用notepad++编辑。

3、record.txt为待加入的解析列表。一行一个。文件编码为utf-8，格式为windows（cr lf）格式（务必）。每行分3列，以逗号分隔，第一列为主机记录，如www，第二列为记录类型，如A记录，第三列为值，如114.114.114.114

4、推荐在命令行中执行 php index.php，不要在浏览器中访问。


sc.php为批量生成域名，打开sc.php第10行代码修改要注册的域名，11-12行代码是从1-99批量写入，13行代码是要注册的后缀
jc.php为自动检测可用域名写入到domain.txt
dns.php是自动把域名添加到cfdns
