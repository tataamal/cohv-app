# Dockerfile

# Langkah 1: Gunakan base image resmi Python
# Ini adalah fondasi dari container kita, sudah terinstal Python 3.9.
FROM python:3.11-slim 

# Langkah 2: Atur direktori kerja di dalam container
# Semua perintah selanjutnya akan dijalankan dari dalam folder /app.
WORKDIR /app

# Langkah 3: Salin file dependensi dan instal
# Tanda titik (.) berarti menyalin ke direktori kerja saat ini (/app).
# Ini dilakukan terpisah agar Docker bisa menggunakan cache,
# sehingga proses build lebih cepat jika dependensi tidak berubah.
COPY requirements.txt .
RUN pip install -r requirements.txt

# Langkah 4: Salin semua file kode aplikasi
# Ini menyalin file seperti app.py ke dalam container.
COPY . .

# Langkah 5: Beri tahu Docker port mana yang akan digunakan aplikasi
EXPOSE 5000

# Langkah 6: Tentukan perintah untuk menjalankan aplikasi saat container dimulai
CMD ["python", "app.py"]