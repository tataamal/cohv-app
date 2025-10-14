<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan</title>
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
        h1 {
            font-size: 10rem;
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
        <h1>404</h1>
        <h2>Halaman Tidak Ditemukan</h2>
        <p>Maaf, halaman yang Anda cari tidak ada atau mungkin telah dipindahkan ke lokasi lain.</p>
        <a href="{{ url('/dashboard-landing') }}">Kembali ke Beranda</a>
    </div>
</body>
</html>