# Panduan Git Push dan Pull

Panduan ini dipakai untuk mengirim perubahan ke GitHub dan mengambil update terbaru dari GitHub.

## Data Repo

- Remote GitHub: `https://github.com/WANGDELL30/web-pengelolaan-skripsi.git`
- Branch utama: `main`

## Push ke GitHub

Gunakan command ini kalau kamu sudah mengubah file di project dan ingin mengirimnya ke GitHub.

```powershell
git status
git add .
git commit -m "update project"
git push origin main
```

Kalau ingin pesan commit lebih jelas, ganti bagian `"update project"`.

Contoh:

```powershell
git commit -m "perbaikan halaman settings"
```

## Pull dari GitHub

Gunakan command ini kalau project sudah ada di laptop/komputer dan ingin mengambil update terbaru dari GitHub.

```powershell
git pull origin main
```

## Clone Project Pertama Kali

Gunakan command ini kalau teman kamu belum punya folder project-nya.

```powershell
git clone https://github.com/WANGDELL30/web-pengelolaan-skripsi.git
cd web-pengelolaan-skripsi
```

Setelah itu, untuk update berikutnya cukup pakai:

```powershell
git pull origin main
```

## Cek Branch yang Sedang Dipakai

```powershell
git branch --show-current
```

Kalau hasilnya bukan `main`, sesuaikan command push/pull dengan nama branch tersebut.

Contoh:

```powershell
git push origin nama-branch
git pull origin nama-branch
```

## Urutan Kerja yang Aman

Sebelum mulai mengedit project, ambil update terbaru dulu:

```powershell
git pull origin main
```

Setelah selesai mengedit dan ingin upload ke GitHub:

```powershell
git status
git add .
git commit -m "update project"
git push origin main
```

## Kalau Ada Error Saat Pull

Kalau muncul pesan konflik, jangan langsung hapus file. Cek dulu file yang bermasalah:

```powershell
git status
```

Lalu buka file yang konflik, pilih bagian kode yang benar, simpan, kemudian jalankan:

```powershell
git add .
git commit -m "resolve conflict"
git push origin main
```

## Catatan untuk Repo Private

Kalau repo GitHub bersifat private, teman kamu harus ditambahkan sebagai collaborator di GitHub terlebih dahulu.
