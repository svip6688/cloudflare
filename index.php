<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cloudflare批量操作系统</title>
    <!-- 引入 Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
	<!--link rel="icon" type="image/x-icon" href="https://cdn4.iconfinder.com/data/icons/web-hosting-filled-line-1/100/web_hosting_colored_line_dns_padlock-64.png"-->
    <link rel="icon" type="image/x-icon" href="https://cdn-icons-png.flaticon.com/128/18405/18405093.png">
    <!-- 自定义样式 -->
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f8f9fa;
            margin: 0;
            padding: 0;
            padding-bottom: 70px; /* 给底部留出空间 */
        }

        .navbar {
            height: 120px;
            background-color: #212529;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 24px;
            font-weight: bold;
        }

        /* 修改容器 */
        .container {
            display: flex;
            flex-direction: column; /* 垂直排列 */
            justify-content: center; /* 垂直居中 */
            align-items: center; /* 水平居中 */
            margin-top: 50px;
            width: 100%; /* 使容器宽度占满 */
            padding: 0 10px; /* 加点内边距防止过于紧凑 */
        }

        /* 按钮行布局 */
        .btn-row {
            display: flex;
            justify-content: center; /* 按钮行水平居中 */
            gap: 10px; /* 按钮之间的间隔 */
            margin-bottom: 20px; /* 行之间的间隔 */
        }

        .btn-custom {
            padding: 15px 30px;
            font-size: 18px;
            border-radius: 10px; /* 设置圆角 */
            width: 230px; /* 固定按钮宽度 */
            text-align: center;
            font-weight: bold;
            transition: all 0.3s ease; /* 添加平滑过渡效果 */
        }

        /* 默认按钮颜色 */
        .btn-primary {
            background-color: #007bff;
            border: none;
            color: white;
        }

        .btn-secondary {
            background-color: #28a745;
            border: none;
            color: white;
        }

        .btn-info {
            background-color: #17a2b8;
            border: none;
            color: white;
        }

        .btn-warning {
            background-color: #ffc107;
            border: none;
            color: white;
        }

        .btn-danger {
            background-color: #dc3545;
            border: none;
            color: white;
        }

        .btn-fans {
            background-color: #e83e8c; /* 粉色 */
            border: none;
            color: white;
        }

        .btn-light {
            background-color: #198754; /* 新功能2按钮颜色 */
            border: none;
            color: white;
        }

        /* 所有按钮的鼠标经过效果 */
        .btn-primary:hover {
            background-color: #0056b3;
            color: #ffcc00; /* 鼠标经过时文字变色 */
        }

        .btn-secondary:hover {
            background-color: #218838;
            color: #ffcc00; /* 鼠标经过时文字变色 */
        }

        .btn-info:hover {
            background-color: #138496;
            color: #ffcc00; /* 鼠标经过时文字变色 */
        }

        .btn-warning:hover {
            background-color: #e0a800;
            color: #ffcc00; /* 鼠标经过时文字变色 */
        }

        .btn-danger:hover {
            background-color: #c82333;
            color: #ffcc00; /* 鼠标经过时文字变色 */
        }

        .btn-fans:hover {
            background-color: #d6336c;
            color: #ffcc00; /* 鼠标经过时文字变色 */
        }

        .btn-light:hover {
            background-color: #157347; /* 鼠标经过时颜色变深 */
            color: #ffcc00; /* 鼠标经过时文字变色 */
        }

        /* 只为新功能1按钮设置鼠标经过变色 */
        .btn-dark:hover {
            background-color: #343a40; /* 设置为暗色背景 */
            color: #ffc107; /* 鼠标经过时文字变色 */
        }

        /* 底部版权信息 */
        .footer {
            background-color: #212529;
            color: white;
            text-align: center;
            padding: 30px;
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 999; /* 确保版权信息位于最上层 */
        }
    </style>
</head>
<body>

    <!-- 导航栏 -->
    <div class="navbar">
        欢迎使用，Cloudflare批量添加域名系统
    </div>

    <!-- 按钮区 -->
    <div class="container">
        <div class="btn-row">
            <a href="sc.php" target="_blank" class="btn btn-primary btn-custom">域名批量生成</a>
            <a href="jc.php" target="_blank" class="btn btn-secondary btn-custom">域名批量检查</a>
        </div>

        <div class="btn-row">
            <a href="ns.php" target="_blank" class="btn btn-info btn-custom">域名批量注册</a>
            <a href="dns.php" target="_blank" class="btn btn-warning btn-custom">域名批量添加</a>
        </div>

        <div class="btn-row">
            <a href="jx.php" target="_blank" class="btn btn-danger btn-custom">域名批量解析</a>
            <a href="sdns.php" target="_blank" class="btn btn-fans btn-custom">DNS批量删除</a> <!-- 新增粉丝按钮 -->
        </div>

        <!-- 新增两个按钮 -->
        <div class="btn-row">
            <a href="dd.php" target="_blank" class="btn btn-dark btn-custom">域名批量删除</a> <!-- 新功能1按钮-->
            <a href="" target="_blank" class="btn btn-light btn-custom">待开发</a>
        </div>
    </div>

    <!-- 底部版权信息 -->
    <div class="footer">
        © 2025 无忧科技版权所有
    </div>

    <!-- 引入 Bootstrap JS 和 Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html>
