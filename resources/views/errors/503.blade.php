<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Situs Sedang Dalam Perbaikan</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

    <style>
        html, body {
            height: 100%;
            margin: 0;
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .maintenance-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            padding: 20px;
            box-sizing: border-box;
        }

        .maintenance-card {
            background: white;
            padding: 40px 50px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            max-width: 550px;
            width: 100%;
        }

        /* CSS ini sekarang berlaku untuk logo Anda */
        .maintenance-svg {
            max-width: 200px; /* Atur lebar maksimum logo */
            height: auto; /* Tinggi akan menyesuaikan otomatis */
            margin-bottom: 30px;
            animation: float 4s ease-in-out infinite; /* Animasi mengambang yang halus */
        }

        h1 {
            font-size: 28px;
            font-weight: 600;
            color: #1B5E20;
            margin: 0 0 15px 0;
        }

        p {
            font-size: 16px;
            color: #556b55;
            line-height: 1.7;
            margin: 0;
        }
        
        /* Animasi mengambang */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
    </style>
</head>
<body>
    <div class="maintenance-wrapper">
        <div class="maintenance-card">
            
            <img src="{{ asset('images/KMI.png') }}" alt="Logo Perusahaan" class="maintenance-svg">

            <h1>Kami Akan Segera Kembali</h1>
            <p>
                Maaf atas ketidaknyamanannya, situs kami sedang dalam proses pemeliharaan terjadwal. 
                Kami berusaha maksimal untuk segera kembali online!
            </p>
    </div>
</body>
</html>