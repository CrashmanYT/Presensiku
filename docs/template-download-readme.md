# Fitur Download Template Import

## Overview
Fitur download template memungkinkan pengguna untuk mengunduh template Excel yang sudah diformat untuk import data siswa, guru, dan kelas. Template ini dilengkapi dengan:
- ✅ Header kolom yang sesuai dengan database
- ✅ Data contoh yang valid
- ✅ Sheet petunjuk penggunaan lengkap
- ✅ Format Excel yang siap pakai

## Fitur Template

### 🎨 Desain Template
- **Header berwarna biru** dengan teks putih
- **Freeze panel** pada baris pertama
- **Auto-sizing** kolom untuk keterbacaan optimal
- **Border** pada semua sel data
- **Sheet terpisah** untuk petunjuk penggunaan

### 📋 Isi Template

#### Sheet "Template"
- Header kolom sesuai database
- 3 baris data contoh yang valid
- Format yang konsisten dan mudah dibaca

#### Sheet "Petunjuk Import" 
- Panduan langkah demi langkah
- Penjelasan setiap kolom
- Format data yang benar
- Contoh pengisian
- Tips menghindari error

### 🔄 Data Dinamis
Template mengambil data dari database untuk contoh yang akurat:
- **Template Siswa**: Menggunakan nama kelas yang ada di database
- **Template Kelas**: Menggunakan nama guru yang ada di database
- **Template Guru**: Data contoh yang realistis

## Lokasi Download

### Via UI (Rekomendasi)
Setiap halaman resource memiliki tombol **"Download Template"**:
- 📊 **StudentResource**: `/admin/students` → Tombol abu-abu dengan icon dokumen
- 👨‍🏫 **TeacherResource**: `/admin/teachers` → Tombol abu-abu dengan icon dokumen  
- 🏫 **ClassesResource**: `/admin/classes` → Tombol abu-abu dengan icon dokumen

### Via URL Langsung
```
GET /template/student  → Template_Import_Siswa.xlsx
GET /template/teacher  → Template_Import_Guru.xlsx
GET /template/class    → Template_Import_Kelas.xlsx
```

## Template Structure

### 1. Template Siswa
**File**: `Template_Import_Siswa.xlsx`
```
Kolom: name | nis | class_name | gender | fingerprint_id | parent_whatsapp
Contoh: Ahmad Rizki | 2023001 | 12 IPA 1 | L | 001 | +6281234567890
```

### 2. Template Guru  
**File**: `Template_Import_Guru.xlsx`
```
Kolom: name | nip | fingerprint_id | whatsapp_number
Contoh: Dr. Ahmad Malik | 196512151990031001 | 101 | +6281234567800
```

### 3. Template Kelas
**File**: `Template_Import_Kelas.xlsx`
```
Kolom: name | level | major | homeroom_teacher_name
Contoh: 12 IPA 1 | 12 | IPA | Dr. Ahmad Malik
```

## Implementasi Teknis

### File Structure
```
app/
├── Http/Controllers/
│   └── TemplateController.php       # Controller untuk download
├── Exports/
│   └── TemplateExport.php          # Export class untuk template
└── Filament/Resources/
    ├── StudentResource.php         # Tombol download siswa
    ├── TeacherResource.php         # Tombol download guru
    └── ClassesResource.php         # Tombol download kelas

routes/
└── web.php                         # Route untuk download template
```

### Dependencies
- `maatwebsite/excel` - untuk generate Excel files
- `phpoffice/phpspreadsheet` - untuk styling Excel

### Security
- Route dilindungi middleware `auth`
- Validasi tipe template sebelum download
- Error handling untuk template yang tidak ditemukan

## Usage Guide

### Untuk Admin/User
1. **Buka halaman** data siswa/guru/kelas
2. **Klik tombol** "Download Template" (warna abu-abu)
3. **File otomatis terdownload** dengan nama yang sesuai
4. **Buka file Excel** dan baca petunjuk di sheet kedua
5. **Isi data** sesuai format yang ditentukan
6. **Upload untuk import** menggunakan tombol "Import Data"

### Untuk Developer
```php
// Custom template download
$headers = ['column1', 'column2'];
$sampleData = [['data1', 'data2']];
return Excel::download(
    new TemplateExport($headers, $sampleData), 
    'filename.xlsx'
);
```

## Keunggulan Template

### ✅ User-Friendly
- Template siap pakai tanpa setup manual
- Petunjuk lengkap dalam bahasa Indonesia
- Contoh data yang realistis

### ✅ Error-Proof
- Format kolom sesuai validasi import
- Contoh data yang sudah tervalidasi
- Panduan untuk menghindari error umum

### ✅ Professional
- Desain yang rapi dan konsisten
- Styling yang mudah dibaca
- Format Excel yang kompatibel

### ✅ Dynamic
- Data contoh diambil dari database aktual
- Template selalu up-to-date dengan perubahan database
- Fleksibel untuk customization

## Maintenance

Template akan otomatis update jika:
- Ada perubahan struktur database
- Ada penambahan/pengurangan kolom
- Ada perubahan validasi import

Tidak perlu maintenance manual untuk template files.

## Future Enhancements

Rencana pengembangan:
- [ ] Template dengan multiple sheets untuk data besar
- [ ] Template dengan dropdown validation
- [ ] Template dengan conditional formatting
- [ ] Export template dalam format CSV
- [ ] Template dengan data pre-filled berdasarkan filter
