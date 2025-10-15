<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>502 - Gangguan Server</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
        }
        .error-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            min-height: 100vh;
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }
        h1 {
            font-size: 10rem;
            font-weight: 600;
            color: #0D47A1; /* Dark Blue */
            margin: 0;
            line-height: 1;
        }
        h2 {
            font-size: 2rem;
            color: #1976D2; /* Medium Blue */
            margin: 10px 0 20px 0;
        }
        p {
            color: #57647e; /* Muted Blue */
            max-width: 450px;
        }
        a {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 25px;
            background-color: #2196F3; /* Primary Blue */
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }
        a:hover {
            background-color: #1976D2; /* Darker Blue */
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <h1>502</h1>
        <h2>Gangguan Server</h2>
        <p>Saat ini terjadi masalah komunikasi antar server kami. Tim kami sedang menanganinya. Silakan coba lagi dalam beberapa saat.</p>
        <a href="{{ url('/login') }}">Kembali ke Beranda</a>
    </div>
</body>
</html>