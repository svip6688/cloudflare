<?php
$message = ""; // 初始化消息变量

// 设置默认的起始数字和结束数字
$default_start = 1;
$default_end = 99;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 获取表单数据，域名前缀、后缀、起始和结束数字
    $prefix = $_POST['prefix'];
    $suffix = $_POST['suffix'];
    $start = isset($_POST['start']) && is_numeric($_POST['start']) ? $_POST['start'] : $default_start;
    $end = isset($_POST['end']) && is_numeric($_POST['end']) ? $_POST['end'] : $default_end;

    // 生成域名
    function generateDomains($prefix, $start, $end, $suffix) {
        $domains = [];
        for ($i = $start; $i <= $end; $i++) {
            $domains[] = $prefix . $i . '.' . $suffix;
        }
        return $domains;
    }

    // 生成域名列表
    $domains = generateDomains($prefix, $start, $end, $suffix);

    // 写入文件
    $file = fopen("zc.txt", "w");
    if (!$file) {
        $message = "❌ 无法打开文件 zc.txt 进行写入！";
    } else {
        foreach ($domains as $domain) {
            fwrite($file, $domain . "\n");
        }
        fclose($file);
        $message = "✅ 域名已保存到 <strong>zc.txt</strong> 文件中。";
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
	<!--link rel="icon" type="image/x-icon" href="https://cdn4.iconfinder.com/data/icons/web-hosting-filled-line-1/100/web_hosting_colored_line_dns_padlock-64.png"-->
    <link rel="icon" type="image/x-icon" href="https://cdn-icons-png.flaticon.com/128/18405/18405093.png">
    <title>生成域名</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
        }
        h1 {
            text-align: center;
            font-size: 24px;
            color: #333;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            font-size: 16px;
            color: #555;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background-color: #f9f9f9;
            box-sizing: border-box;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background-color: #007BFF;
            color: white;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #0056b3;
        }
        .message {
            font-size: 18px;
            text-align: center;
            color: green;
        }
        .error {
            font-size: 18px;
            text-align: center;
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>生成域名</h1>
    <form method="POST">
        <div class="form-group">
            <label for="prefix">域名前缀：</label>
            <input type="text" id="prefix" name="prefix" value="ucuc" required>
        </div>
        <div class="form-group">
            <label for="start">起始数字：</label>
            <input type="number" id="start" name="start" value="<?= isset($_POST['start']) ? $_POST['start'] : $default_start ?>" required>
        </div>
        <div class="form-group">
            <label for="end">结束数字：</label>
            <input type="number" id="end" name="end" value="<?= isset($_POST['end']) ? $_POST['end'] : $default_end ?>" required>
        </div>
        <div class="form-group">
            <label for="suffix">域名后缀：</label>
            <input type="text" id="suffix" name="suffix" value="cc" required>
        </div>
        <button type="submit" class="btn">生成并保存域名</button>
    </form>

    <!-- 只有在表单提交后才显示结果 -->
    <?php
    // 如果表单已经提交
    if ($_SERVER["REQUEST_METHOD"] == "POST" && $message != "") {
        if (strpos($message, "❌") !== false) {
            echo "<div class='error'>$message</div>";
        } else {
            echo "<div class='message'>$message</div>";
        }
    }
    ?>
</div>

</body>
</html>
