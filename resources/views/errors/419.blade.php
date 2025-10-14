<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Sesi Kedaluwarsa</title>
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
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            text-align: center;
            padding: 20px;
            box-sizing: border-box;
        }
        .icon {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 8rem;
            font-weight: 600;
            color: #1B5E20;
            margin: 0;
            line-height: 1;
        }
        h2 {
            font-size: 2rem;
            color: #388E3C;
            margin: 10px 0 20px 0;
        }
        p {
            color: #556b55;
            max-width: 450px;
        }
        a {
            display: inline-block;
            margin-top: 30px;
            padding: 12px 25px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s ease;
            cursor: pointer;
        }
        a:hover {
            background-color: #388E3C;
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#388E3C">
            <path d="M12 2C6.486 2 2 6.486 2 12s4.486 10 10 10 10-4.486 10-10S17.514 2 12 2zm0 18c-4.411 0-8-3.589-8-8s3.589-8 8-8 8 3.589 8 8-3.589 8-8 8z"/>
            <path d="M13 7h-2v5.414l3.293 3.293 1.414-1.414L13 11.586z"/>
        </svg>
        <h1>419</h1>
        <h2>Sesi Anda Telah Berakhir</h2>
        <p>Maaf, Anda perlu masuk kembali untuk melanjutkan. Silakan klik tombol di bawah untuk pergi ke halaman login.</p>
        
        <a href="{{ route('login') }}">Silakan Login Kembali</a>
    </div>
</body>
</html>