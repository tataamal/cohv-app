<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        /* Base Reset */
        body { margin: 0; padding: 0; background-color: #f4f7f6; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        table { border-collapse: collapse; width: 100%; }
        
        /* Container */
        .email-wrapper { max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px #038f49ff; margin-top: 30px; margin-bottom: 30px; }
        
        /* Body */
        .email-body { padding: 40px; color: #334155; line-height: 1.6; }
        .greeting { font-size: 18px; font-weight: 600; margin-bottom: 20px; color: #0f172a; }
        .intro-text { margin-bottom: 25px; font-size: 15px; }
        
        /* Footer */
        .email-footer { background-color: #f1f5f9; padding: 20px 40px; text-align: center; border-top: 1px solid #e2e8f0; }
        .footer-text { font-size: 12px; color: #94a3b8; margin: 5px 0; line-height: 1.5; }
        .footer-company { font-weight: 700; color: #64748b; }
        
        /* Mobile */
        @media only screen and (max-width: 600px) {
            .email-body { padding: 25px; }
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-body">
            <div class="greeting">Yth. Bapak/Ibu,</div>
            
            <p class="intro-text">
                Bersama ini terlampir laporan rekapitulasi Report Weekly untuk rentang tanggal <strong>{{ $dateInfo }}</strong>. Mohon untuk dapat diperiksa dan digunakan sebagai acuan KPI operasional produksi.
            </p>

            <p style="font-size: 14px; margin-bottom: 0;">
                Hormat Kami,<br>
                <strong>Tim ERP & IT - PT. Kayu Mebel Indonesia</strong>
            </p>
        </div>

        <!-- Footer -->
        <div class="email-footer">
            <p class="footer-text footer-company">&copy; {{ date('Y') }} PT. Kayu Mebel Indonesia.</p>
            <p class="footer-text">
                Email ini dibuat secara otomatis oleh sistem (Auto-Generated).<br>
                Mohon untuk tidak membalas email ini secara langsung. Jika terdapat ketidaksesuaian data, silakan hubungi bagian IT atau Admin Produksi.
            </p>
            <p class="footer-text" style="margin-top: 10px; font-style: italic; color: #cbd5e1;">
                Convidentiality Notice: This email and any attachments are confidential and intended solely for the use of the individual or entity to whom they are addressed.
            </p>
        </div>
    </div>
</body>
</html>
