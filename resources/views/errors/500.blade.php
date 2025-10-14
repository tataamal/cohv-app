<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Kesalahan Server</title>
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
        }
        a:hover {
            background-color: #388E3C;
        }
    </style>
</head>
<body>
    <div class="error-wrapper">
        <svg class="icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#388E3C">
            <path d="M20 3H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zM4 19V5h16v14zM11 15h2v2h-2zm0-8h2v6h-2z"/>
        </svg>
        <h1>500</h1>
        <h2>Terjadi Kesalahan pada Server</h2>
        <p>Ada sedikit masalah teknis di pihak kami. Tim kami telah diberitahu dan sedang bekerja untuk memperbaikinya. Silakan coba lagi nanti.</p>
        {{-- <a href="{{ url('/') }}">Kembali ke Beranda</a> --}}
    </div>
</body>
</html>