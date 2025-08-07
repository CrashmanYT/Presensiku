# Kirimi WhatsApp API — Developer Documentation (Versi Lengkap)

Dokumentasi lengkap berdasarkan halaman yang Anda berikan, dilengkapi spesifikasi Request Payload (struktur JSON, tipe data, required/optional, contoh lengkap) dan contoh cURL serta Node.js untuk endpoint kunci.

## Informasi Umum

- Base URL: https://api.kirimi.id
- Versi: v1
- Format respons: JSON
- Autentikasi: kombinasi `user_code` dan `secret` dalam body request
- Rate limit: default 60 request/menit
- Keamanan:
  - Gunakan HTTPS untuk semua request
  - Jaga kerahasiaan Secret Key, regenerasi berkala
  - Validasi input dan tangani error dengan baik

Konvensi:
- Semua request menggunakan `Content-Type: application/json`
- Nomor telepon dalam format internasional (contoh: 6281234567890)

Legenda tipe data:
- string: teks
- number: bilangan (integer/float sesuai konteks)
- boolean: true/false
- array: daftar nilai
- object: struktur JSON bersarang

Catatan Paket:
- Pengiriman media pada send-message: tersedia untuk paket Lite, Basic, Pro
- Fitur OTP: tersedia untuk paket Basic, Pro

---

## Kredensial API

Field yang digunakan untuk autentikasi:
- user_code (string, Required): kode pengguna dari akun Kirimi
- secret (string, Required): secret key untuk autentikasi

Payload contoh minimal:
```json
{
  "user_code": "YOUR-USER-CODE",
  "secret": "YOUR-SECRET-KEY"
}
```

---

## 1) Authentication & User Management

### 1.1 User Info
- Method: POST
- Path: /v1/user-info

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string"
}
```

Validasi:
- user_code: required, non-empty
- secret: required, non-empty

Respons (contoh):
```json
{
  "success": true,
  "data": {
    "user_code": "U12345",
    "email": "user@example.com",
    "plan": "pro",
    "created_at": "2025-07-01T10:00:00Z"
  }
}
```

cURL:
```bash
curl -X POST "https://api.kirimi.id/v1/user-info" \
  -H "Content-Type: application/json" \
  -d '{"user_code":"YOUR-USER-CODE","secret":"YOUR-SECRET-KEY"}'
```

Node.js:
```js
const res = await fetch("https://api.kirimi.id/v1/user-info", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({ user_code: "YOUR-USER-CODE", secret: "YOUR-SECRET-KEY" })
});
console.log(await res.json());
```

---

### 1.2 Create Device
- Method: POST
- Path: /v1/create-device

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "package_id": 0,
  "voucher_code": "string (optional)"
}
```

Aturan:
- package_id: required, number valid sesuai daftar paket
- voucher_code: optional

cURL:
```bash
curl -X POST "https://api.kirimi.id/v1/create-device" \
  -H "Content-Type: application/json" \
  -d '{
    "user_code":"YOUR-USER-CODE",
    "secret":"YOUR-SECRET-KEY",
    "package_id":1,
    "voucher_code":"PROMO2025"
  }'
```

---

### 1.3 Connect Device
- Method: POST
- Path: /v1/connect-device

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string"
}
```

Validasi:
- device_id: required, device harus ada dan milik user

---

### 1.4 Renew Device
- Method: POST
- Path: /v1/renew-device

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string",
  "package_id": 0,
  "voucher_code": "string (optional)"
}
```

---

### 1.5 Device Status
- Method: POST
- Path: /v1/device-status

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string"
}
```

---

### 1.6 Enhanced Device Status
- Method: POST
- Path: /v1/device-status-enhanced

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string"
}
```

---

### 1.7 List Devices
- Method: POST
- Path: /v1/list-devices

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "page": 1,
  "limit": 10
}
```

Default:
- page: 1
- limit: 10 (maksimum sesuai kebijakan backend)

---

## 2) Message Sending

### 2.1 Send Message
- Method: POST
- Path: /v1/send-message

Request Payload (teks saja):
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string",
  "receiver": "string",
  "message": "string",
  "enableTypingEffect": true,
  "typingSpeedMs": 350
}
```

Request Payload (dengan media):
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string",
  "receiver": "string",
  "message": "string",
  "media_url": "string",
  "enableTypingEffect": true,
  "typingSpeedMs": 500
}
```

Aturan:
- receiver: Required, format internasional 62XXXXXXXXXX
- media_url: Optional, public URL; ukuran file ≤ 64MB
- enableTypingEffect: Optional, default true
- typingSpeedMs: Optional, rentang 100–800; default 350 (teks), 500 (media)

cURL (teks):
```bash
curl -X POST "https://api.kirimi.id/v1/send-message" \
  -H "Content-Type: application/json" \
  -d '{
    "user_code":"YOUR-USER-CODE",
    "secret":"YOUR-SECRET-KEY",
    "device_id":"YOUR-DEVICE-ID",
    "receiver":"6281234567890",
    "message":"Halo dari Kirimi API!"
  }'
```

Node.js (media):
```js
const body = {
  user_code: "YOUR-USER-CODE",
  secret: "YOUR-SECRET-KEY",
  device_id: "YOUR-DEVICE-ID",
  receiver: "6281234567890",
  message: "Cek dokumen ini",
  media_url: "https://example.com/sample.pdf",
  enableTypingEffect: true,
  typingSpeedMs: 500
};
const res = await fetch("https://api.kirimi.id/v1/send-message", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify(body)
});
console.log(await res.json());
```

Format media yang didukung:
- Gambar: JPEG, PNG, GIF, WebP
- Video: MP4, AVI, MOV
- Audio: MP3, WAV, OGG
- Dokumen: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX

---

### 2.2 Broadcast Message
- Method: POST
- Path: /v1/broadcast-message

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string",
  "label": "string",
  "numbers": ["string"],
  "message": "string",
  "delay": 30,
  "media_url": "string (optional)",
  "started_at": "2025-08-10T09:00:00Z",
  "enableTypingEffect": true,
  "typingSpeedMs": 350
}
```

Aturan:
- numbers: Required, array minimal 1 nomor valid
- delay: Optional, default 30 (detik)
- started_at: Optional, ISO datetime untuk penjadwalan
- enableTypingEffect/typingSpeedMs: Optional

cURL:
```bash
curl -X POST "https://api.kirimi.id/v1/broadcast-message" \
  -H "Content-Type: application/json" \
  -d '{
    "user_code":"YOUR-USER-CODE",
    "secret":"YOUR-SECRET-KEY",
    "device_id":"YOUR-DEVICE-ID",
    "label":"Promo-Agustus",
    "numbers":["6281111111111","6282222222222"],
    "message":"Promo spesial!",
    "delay":30,
    "started_at":"2025-08-10T09:00:00Z"
  }'
```

---

## 3) OTP Management

Syarat: paket Basic/Pro. OTP 6 digit, berlaku 5 menit, sekali pakai.

### 3.1 Generate OTP
- Method: POST
- Path: /v1/generate-otp

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string",
  "phone": "string",
  "enableTypingEffect": true,
  "typingSpeedMs": 350
}
```

Aturan:
- phone: Required, format internasional 62XXXXXXXXXX

cURL:
```bash
curl -X POST "https://api.kirimi.id/v1/generate-otp" \
  -H "Content-Type: application/json" \
  -d '{
    "user_code":"YOUR-USER-CODE",
    "secret":"YOUR-SECRET-KEY",
    "device_id":"YOUR-DEVICE-ID",
    "phone":"6281234567890"
  }'
```

---

### 3.2 Validate OTP
- Method: POST
- Path: /v1/validate-otp

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "device_id": "string",
  "phone": "string",
  "otp": "string"
}
```

Aturan:
- phone: Required, harus sama dengan nomor di generate-otp
- otp: Required, 6 digit

Node.js:
```js
const res = await fetch("https://api.kirimi.id/v1/validate-otp", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    user_code: "YOUR-USER-CODE",
    secret: "YOUR-SECRET-KEY",
    device_id: "YOUR-DEVICE-ID",
    phone: "6281234567890",
    otp: "123456"
  })
});
console.log(await res.json());
```

---

## 4) Contact Management

### 4.1 Save Contact
- Method: POST
- Path: /v1/save-contact

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "nama": "string",
  "nomor": "string",
  "tag": "string"
}
```

Aturan:
- nama: Required
- nomor: Required, format 62XXXXXXXXXX
- tag: Optional, default "default"

---

### 4.2 Bulk Save Contacts
- Method: POST
- Path: /v1/save-contacts-bulk

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "contacts": [
    { "nama": "string", "nomor": "string", "tag": "string" }
  ]
}
```

Aturan:
- contacts: Required, minimal 1 item dengan `nama`, `nomor`; `tag` optional

cURL:
```bash
curl -X POST "https://api.kirimi.id/v1/save-contacts-bulk" \
  -H "Content-Type: application/json" \
  -d '{
    "user_code":"YOUR-USER-CODE",
    "secret":"YOUR-SECRET-KEY",
    "contacts":[
      { "nama":"Ayu", "nomor":"6281111111111", "tag":"vip" },
      { "nama":"Budi", "nomor":"6282222222222", "tag":"default" }
    ]
  }'
```

---

## 5) Deposit Management

Integrasi pembayaran Midtrans. Link pembayaran dikirim dan berlaku 24 jam. Maksimal 2 deposit `unpaid` aktif per user.

### 5.1 Create Deposit
- Method: POST
- Path: /v1/create-deposit

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "nominal": 100
}
```

Aturan:
- nominal: Required, minimal 100 (IDR)

Node.js:
```js
const res = await fetch("https://api.kirimi.id/v1/create-deposit", {
  method: "POST",
  headers: { "Content-Type": "application/json" },
  body: JSON.stringify({
    user_code: "YOUR-USER-CODE",
    secret: "YOUR-SECRET-KEY",
    nominal: 50000
  })
});
console.log(await res.json());
```

---

### 5.2 Deposit Status
- Method: POST
- Path: /v1/deposit-status

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "ref": "string"
}
```

Aturan:
- ref: Required, reference dari create-deposit

---

### 5.3 Cancel Deposit
- Method: POST
- Path: /v1/cancel-deposit

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "ref": "string"
}
```

Aturan:
- ref: Required, hanya untuk deposit dengan status unpaid

---

### 5.4 List Deposits
- Method: POST
- Path: /v1/list-deposits

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string",
  "page": 1,
  "limit": 10,
  "status": "unpaid"
}
```

Aturan:
- status: Optional, salah satu dari [unpaid, paid, expired, cancelled]
- page, limit: Optional

---

## 6) Package Management

### 6.1 List Packages
- Method: POST
- Path: /v1/list-packages

Request Payload:
```json
{
  "user_code": "string",
  "secret": "string"
}
```

cURL:
```bash
curl -X POST "https://api.kirimi.id/v1/list-packages" \
  -H "Content-Type: application/json" \
  -d '{"user_code":"YOUR-USER-CODE","secret":"YOUR-SECRET-KEY"}'
```

---

## Error Handling

Format error generik (contoh):
```json
{
  "success": false,
  "error": {
    "code": "VALIDATION_ERROR",
    "message": "Field receiver is required",
    "details": {
      "receiver": ["The receiver field is required."]
    }
  }
}
```

Praktik terbaik:
- Tangani 4xx sebagai kesalahan input/otorisasi
- Tangani 5xx sebagai error sementara; terapkan retry dengan backoff
- Validasi format nomor telepon dan URL media sebelum request
- Log permintaan dan respons untuk audit

---

## Webhook (Ringkasan)

- API mendukung webhook untuk:
  - Status pengiriman pesan
  - Balasan dari pengguna
- Lihat panduan Webhook Integration untuk format payload dan verifikasi signature.

---

## Tips Implementasi

- Ganti placeholder:
  - YOUR-USER-CODE, YOUR-DEVICE-ID, YOUR-SECRET-KEY
- Gunakan format nomor 62XXXXXXXXXX
- Gunakan HTTPS
- Implementasikan retry untuk kegagalan sementara
- Batasi concurrency broadcast dan atur delay untuk menghindari deteksi spam