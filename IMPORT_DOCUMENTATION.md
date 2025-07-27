# Dokumentasi Fitur Import Data

## Overview
Fitur import data memungkinkan Anda untuk mengimpor data siswa, guru, dan kelas secara massal menggunakan file Excel atau CSV.

## Format File yang Didukung
- `.xlsx` (Excel)
- `.csv` (Comma Separated Values)

## Data yang Dapat Diimpor

### 1. Import Data Siswa

**Kolom yang diperlukan:**
- `name` - Nama siswa (wajib)
- `nis` - Nomor Induk Siswa (wajib, harus unik)
- `class_name` - Nama kelas (wajib, harus sudah ada di database)
- `gender` - Jenis kelamin (wajib: L/P, LAKI-LAKI/PEREMPUAN, MALE/FEMALE)
- `fingerprint_id` - ID sidik jari (opsional)
- `parent_whatsapp` - Nomor WhatsApp orang tua (opsional)

**Template:** `storage/app/templates/template_import_siswa.csv`

### 2. Import Data Guru

**Kolom yang diperlukan:**
- `name` - Nama guru (wajib)
- `nip` - Nomor Induk Pegawai (wajib, harus unik)
- `fingerprint_id` - ID sidik jari (opsional)
- `whatsapp_number` - Nomor WhatsApp (wajib)

**Template:** `storage/app/templates/template_import_guru.csv`

### 3. Import Data Kelas

**Kolom yang diperlukan:**
- `name` - Nama kelas (wajib, harus unik)
- `level` - Level/tingkat kelas (wajib, 1-12)
- `major` - Jurusan (wajib)
- `homeroom_teacher_name` - Nama wali kelas (opsional, harus sudah ada di database guru)

**Template:** `storage/app/templates/template_import_kelas.csv`

## Cara Menggunakan

### 1. Persiapan File
1. **Download Template:**
   - Klik tombol **"Download Template"** (icon dokumen berwarna abu-abu) di halaman data yang ingin diimpor
   - Template akan otomatis terdownload dengan format Excel (.xlsx)
   - Template sudah berisi contoh data dan petunjuk penggunaan
2. **Isi Data:**
   - Buka file template yang telah didownload
   - Baca petunjuk pada sheet "Petunjuk Import"
   - Isi data pada sheet "Template" sesuai format yang ditentukan
   - Hapus data contoh dan ganti dengan data sebenarnya
3. **Simpan File:**
   - Simpan file dalam format Excel (.xlsx) atau CSV (.csv)
   - Pastikan nama kolom tidak berubah

### 2. Proses Import
1. Masuk ke halaman data yang ingin diimpor (Siswa/Guru/Kelas)
2. Klik tombol **"Import Data"** (icon panah ke bawah berwarna hijau)
3. Upload file yang telah disiapkan
4. Mapping kolom akan dilakukan otomatis jika nama kolom sesuai
5. Preview data akan ditampilkan untuk verifikasi
6. Klik **"Import"** untuk memulai proses

### 3. Monitoring Import
- Proses import berjalan di background
- Notifikasi akan muncul setelah proses selesai
- Data yang berhasil dan gagal akan ditampilkan

## Validasi Data

### Data Siswa
- NIS harus unik (tidak boleh duplikat)
- Kelas harus sudah ada di database
- Gender harus dalam format yang valid (L/P)

### Data Guru
- NIP harus unik (tidak boleh duplikat)
- Nomor WhatsApp akan diformat otomatis (+62)

### Data Kelas
- Nama kelas harus unik
- Level harus berupa angka 1-12
- Wali kelas harus sudah ada di database guru (jika diisi)

## Tips Import

1. **Urutan Import yang Disarankan:**
   - Import Guru terlebih dahulu
   - Import Kelas (jika ada wali kelas)
   - Import Siswa

2. **Menghindari Error:**
   - Pastikan data wajib diisi lengkap
   - Periksa format data (angka, text, dll)
   - Hindari karakter khusus yang tidak perlu

3. **Update Data Existing:**
   - Sistem akan update data jika NIS/NIP/Nama Kelas sudah ada
   - Data baru akan ditambahkan jika belum ada

## Error Handling

Jika terjadi error saat import:
1. Periksa format file (xlsx/csv)
2. Pastikan kolom wajib terisi
3. Validasi format data sesuai ketentuan
4. Cek koneksi database
5. Hubungi administrator jika masalah berlanjut

## Contoh Format Data

### CSV Format:
```csv
name,nis,class_name,gender,fingerprint_id,parent_whatsapp
Ahmad Rizki,2023001,12 IPA 1,L,001,+6281234567890
```

### Excel Format:
| name | nis | class_name | gender | fingerprint_id | parent_whatsapp |
|------|-----|------------|--------|---------------|-----------------|
| Ahmad Rizki | 2023001 | 12 IPA 1 | L | 001 | +6281234567890 |

## Limitasi
- Maksimal 1000 record per file import
- File maksimal 5MB
- Proses import timeout setelah 5 menit
