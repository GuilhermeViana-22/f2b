<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>API</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        <style>
            body {
                font-family: 'Nunito', sans-serif;
                margin: 0;
                padding: 0;
                min-height: 100vh;
                background: linear-gradient(to right, #8B5CF6, #6366F1, #3B82F6);
                display: flex;
                justify-content: center;
                align-items: center;
                color: white;
            }

            .container {
                text-align: center;
                padding: 2rem;
            }

            .api-text {
                font-size: 5rem;
                font-weight: 700;
                margin-bottom: 2rem;
                text-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }

            .svg-container {
                max-width: 300px;
                margin: 0 auto;
            }

            .svg-container svg {
                width: 100%;
                height: auto;
            }
        </style>
    </head>
    <body>
        <div class="container">
     
            <h1 class="api-text">API</h1>
            <p>Application Programming Interface</p>
        </div>
    </body>
</html>
