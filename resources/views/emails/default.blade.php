<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Verification Code</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #e0f7fa;
            color: #0d47a1;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
            max-width: 600px;
            margin: 40px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .header {
            background: linear-gradient(to right, #006064, #0d47a1);
            color: #ffffff;
            padding: 30px;
            font-size: 28px;
            font-weight: 600;
            text-align: center;
        }

        .content {
            padding: 30px;
            font-size: 16px;
            line-height: 1.6;
            color: #333;
            text-align: left;
        }

        .code {
            display: inline-block;
            background-color: #e0f7fa;
            border: 2px dashed #00acc1;
            color: #006064;
            font-size: 26px;
            font-weight: bold;
            padding: 12px 24px;
            margin: 20px 0;
            border-radius: 8px;
            letter-spacing: 4px;
        }

        .footer {
            background-color: #f1f1f1;
            color: #666666;
            padding: 20px;
            font-size: 12px;
            text-align: center;
        }

        .footer a {
            color: #006064;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    @yield('conteudo')
    <div class="footer">
        <p>By using our services, you agree to our <a href="#">Privacy Policy</a> and <a href="#">Terms of Service</a>.</p>
        <p>&copy; {{ date('Y') }} All rights reserved.</p>
    </div>
</div>
</body>
</html>
