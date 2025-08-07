# WhatsAppService Design Plan (Kirimi API Integration)

Tujuan
- Menyediakan satu service bernama `WhatsAppService` sebagai facade utama untuk operasi WhatsApp via Kirimi API.
- Memisahkan concerns: DTO input, HTTP client, retry/backoff, rate-limit, logging/audit, dan orkestrasi blast.
- Memudahkan reuse pada fitur harian (single send), OTP, device management, dan broadcast/blast.

Ruang Lingkup
- Service publik: `WhatsAppService` berisi fungsi-fungsi umum yang akan dipakai seluruh aplikasi.
- Service pendukung (internal): `KirimiApiClient`, `BlastOrchestrator`, `PacingScheduler`, utilites validasi/normalisasi nomor, dan logging.
- Tidak langsung mengikat ke UI; dapat dipanggil dari Filament Pages, Jobs, Command, atau Controller.

Struktur Direktori (Laravel)
- app/
  - Services/
    - WhatsApp/
      - WhatsAppService.php
      - KirimiApiClient.php
      - Contracts/
        - WhatsAppServiceInterface.php
        - KirimiApiClientInterface.php
      - DTOs/
        - CredentialsDTO.php
        - SendMessageDTO.php
        - BroadcastRequestDTO.php
        - GenerateOtpDTO.php
        - ValidateOtpDTO.php
        - CreateDeviceDTO.php
        - RenewDeviceDTO.php
        - DeviceStatusDTO.php
        - ListDevicesDTO.php
        - ListPackagesDTO.php
        - DepositCreateDTO.php
        - DepositStatusDTO.php
        - DepositCancelDTO.php
        - ListDepositsDTO.php
      - Support/
        - PhoneNormalizer.php
        - RateLimiter.php
        - RetryPolicy.php
        - Idempotency.php
        - PayloadBuilder.php
        - ResponseMapper.php
      - Orchestrators/
        - BlastOrchestrator.php
        - PacingScheduler.php
  - Jobs/
    - WhatsApp/
      - BroadcastDispatchJob.php
      - BroadcastBatchJob.php
      - SendMessageJob.php
- config/
  - whatsapp.php (konfigurasi base_url, rate-limit, retry, batch size, default delay)

Konfigurasi (config/whatsapp.php)
- base_url: "https://api.kirimi.id"
- rate_limit_per_minute: 60
- retry:
  - max_attempts: 3
  - base_delay_ms: 500
  - max_delay_ms: 3000
  - backoff: exponential_jitter
- broadcast:
  - batch_size: 200
  - default_delay_seconds: 30
  - mode: "broadcast" | "per_recipient"
- typing:
  - default_enable: true
  - default_speed_ms: 350

Interface Utama

1) Contracts/WhatsAppServiceInterface.php
- Deklarasi fungsi publik yang dipakai domain aplikasi (mis. notifikasi absensi).
- Menyediakan API tingkat-bisnis yang mudah: sendMessage, sendAttendanceNotificationLate, dsb.
- Menggunakan DTO untuk input, mengembalikan ApiResult/array terstandarisasi.
- Tidak mengurusi detail HTTP atau rate-limit; fokus pada orkestrasi dan business rules ringan (format nomor, template pesan, dsb).

2) Contracts/KirimiApiClientInterface.php
- Abstraksi low-level untuk memanggil endpoint Kirimi (transport layer).
- Bertanggung jawab atas: serialisasi payload, panggilan HTTP, retry/backoff, rate-limit, mapping respons.
- Tidak mengetahui konteks bisnis (absensi, kampanye, dsb); murni pemanggil API.

Perbedaan WhatsAppServiceInterface vs KirimiApiClientInterface
- Tingkat Abstraksi:
  - WhatsAppServiceInterface: high-level, business-facing. Contoh fungsi: `sendAttendanceLate(...)`, `sendMessage(...)`.
  - KirimiApiClientInterface: low-level, API-facing. Contoh fungsi: `sendMessage(payload)`, `broadcastMessage(payload)`.
- Tanggung Jawab:
  - WhatsAppService: validasi sederhana, normalisasi nomor, pilih endpoint yang tepat (send vs broadcast), siapkan template pesan notifikasi absensi.
  - KirimiApiClient: eksekusi HTTP, handle retry/rate-limit, mapping error dan response mentah.
- Dampak Perubahan:
  - Jika Kirimi API berubah, cukup perbarui client + mapper. Lapisan service tetap stabil untuk pemanggil aplikasi.

Fokus Use Case: Notifikasi Absensi
Kita sederhanakan `WhatsAppService` untuk tiga skenario utama:
- Terlambat (late)
- Tidak hadir (absent)
- Izin (excused/leave)

Tambahkan fungsi tingkat-bisnis agar konsumen tidak perlu mengurus template setiap kali:
- sendAttendanceLate(CredentialsDTO credentials, string phone, array context): ApiResult
- sendAttendanceAbsent(CredentialsDTO credentials, string phone, array context): ApiResult
- sendAttendanceExcused(CredentialsDTO credentials, string phone, array context): ApiResult

Di balik layar, fungsi ini akan:
- Memformat nomor (62xxxxxxxxxx)
- Menyusun template pesan dari context (nama siswa, kelas, tanggal, jam, alasan)
- Meneruskan ke KirimiApiClient::sendMessage dengan payload yang sesuai
- Mencatat hasil ke MessageLog (jika disediakan)

Contoh kontrak ringkas:
- Interface [WhatsAppServiceInterface.php](app/Services/WhatsApp/Contracts/WhatsAppServiceInterface.php:1)
```php
interface WhatsAppServiceInterface {
    public function setDefaultCredentials(CredentialsDTO $credentials): void;
    public function withCredentials(CredentialsDTO $credentials): self;

    // Generic messaging
    public function sendMessage(SendMessageDTO $dto): array;

    // Attendance notifications (business-friendly)
    public function sendAttendanceLate(CredentialsDTO $credentials, string $phone, array $context): array;
    public function sendAttendanceAbsent(CredentialsDTO $credentials, string $phone, array $context): array;
    public function sendAttendanceExcused(CredentialsDTO $credentials, string $phone, array $context): array;
}
```

- Interface [KirimiApiClientInterface.php](app/Services/WhatsApp/Contracts/KirimiApiClientInterface.php:1)
```php
interface KirimiApiClientInterface {
    public function sendMessage(array $payload): array;
    public function broadcastMessage(array $payload): array;
    // Endpoint lain disediakan bila dibutuhkan (OTP, device, deposit, dll)
}
```

Template Pesan (Contoh)
- Late:
  - "Informasi Absensi: {nama} (kelas {kelas}) tercatat terlambat pada {tanggal} pukul {jam}. Mohon perhatian orang tua/wali."
- Absent:
  - "Informasi Absensi: {nama} (kelas {kelas}) tidak hadir pada {tanggal}. Silakan konfirmasi ke pihak sekolah."
- Excused:
  - "Informasi Absensi: {nama} (kelas {kelas}) izin pada {tanggal} dengan alasan: {alasan}. Terima kasih."

Pemetaan Context
- context = { nama, kelas, tanggal, jam?, alasan? } → render ke template
- extensible: dukung localization, variable tambahan

## Apa itu DTO?

DTO (Data Transfer Object) adalah objek sederhana untuk mengangkut data antar lapisan tanpa logika bisnis. Tujuan utama:
- Menstandarkan input fungsi service/API client
- Memastikan tipe data dan field terdefinisi jelas
- Menghindari parameter list yang panjang dan raw array yang rentan typo
- Memudahkan validasi dan mapping payload

Karakteristik DTO:
- Hanya properti (fields) dan konstruktor/validator sederhana
- Tidak memiliki dependency ke framework (sebisa mungkin plain PHP object)
- Digunakan oleh WhatsAppService dan KirimiApiClient untuk membangun payload JSON yang sesuai dokumentasi Kirimi

Contoh sketsa DTO:
```php
final class SendMessageDTO {
    public function __construct(
        public CredentialsDTO $credentials,
        public string $receiver,
        public string $message,
        public ?string $media_url = null,
        public ?bool $enableTypingEffect = null,
        public ?int $typingSpeedMs = null,
    ) {}
}
```

DTO Utama (disederhanakan untuk Absensi)
- CredentialsDTO: user_code, secret, device_id
- SendMessageDTO: credentials, receiver, message, media_url?, enableTypingEffect?, typingSpeedMs?
- AttendanceNotificationDTO (opsional): type [late|absent|excused], context (nama, kelas, tanggal, jam?, alasan?)

## Apa itu Job Stub?

Job stub adalah kerangka awal (skeleton) class Job Laravel tanpa implementasi detail, yang berfungsi sebagai tempat untuk menuliskan logika asynchronous di tahap selanjutnya. Dengan menyiapkan job stub:
- Tim dapat menyepakati kontrak input/output, dependensi, dan context logging
- Memungkinkan paralelisme pekerjaan: sebagian tim menyiapkan model/migrasi, sebagian lain mengisi implementasi job
- Mempermudah integrasi dengan queue worker dan penjadwalan

Elemen umum job stub:
- Nama class dan namespace final
- Properti input DTO/ID yang diperlukan (typed)
- Constructor untuk mengikat data
- Method `handle()` kosong atau dengan TODO
- PHPDoc yang menjelaskan tujuan job, parameter, retry policy, dan idempotensi

Contoh Job Stub (disesuaikan untuk notifikasi absensi tunggal):

File: [app/Jobs/WhatsApp/SendAttendanceNotificationJob.php](app/Jobs/WhatsApp/SendAttendanceNotificationJob.php:1)
```php
<?php

namespace App\Jobs\WhatsApp;

use App\Services\WhatsApp\Contracts\WhatsAppServiceInterface;
use App\Services\WhatsApp\DTOs\CredentialsDTO;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Kirim notifikasi absensi (late | absent | excused) ke satu nomor.
 */
final class SendAttendanceNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public CredentialsDTO $credentials,
        public string $type, // late | absent | excused
        public string $phone,
        public array $context // { nama, kelas, tanggal, jam?, alasan? }
    ) {}

    public function handle(WhatsAppServiceInterface $service): void
    {
        // TODO:
        // switch ($this->type) { case 'late': $service->sendAttendanceLate(...); ... }
        // logging & error handling
    }
}
```

Jika nantinya dibutuhkan broadcast absensi (mis. ke banyak orang tua):
- Gunakan pattern serupa: buat job dispatcher yang mengantrikan `SendAttendanceNotificationJob` per nomor dengan queue delay untuk pacing.

Observability & Error Handling
- Semua panggilan API dicatat: endpoint, status, durasi, ringkasan payload (tanpa data sensitif)
- Kesalahan divalidasi dan diberikan error_code (VALIDATION_ERROR, RATE_LIMITED, UPSTREAM_5XX)
- Notifikasi operasional (opsional): jika banyak gagal, kirim Filament notification/email ops

Keamanan
- Simpan `secret` secara terenkripsi bila perlu
- Validasi dan sanitasi input sebelum mengirim ke Kirimi API
- Batasi akses service melalui policy/guard di lapisan pemanggil (UI/Controller)

Konfigurasi .env (contoh)
- KIRIMI_BASE_URL=https://api.kirimi.id
- WHATSAPP_RATE_LIMIT_PER_MINUTE=60
- WHATSAPP_RETRY_MAX_ATTEMPTS=3
- WHATSAPP_RETRY_BASE_DELAY_MS=500
- WHATSAPP_RETRY_MAX_DELAY_MS=3000
- WHATSAPP_TYPING_DEFAULT_ENABLE=true
- WHATSAPP_TYPING_DEFAULT_SPEED_MS=350

Mermaid Diagram (Overview sederhana untuk Absensi)
flowchart TD
  AbsensiModule --> WS[WhatsAppService]
  WS --> DTO[Build SendMessageDTO / Template Absensi]
  WS --> KAC[KirimiApiClient]
  KAC --> API[(Kirimi API /v1/send-message)]
  AbsensiModule -->|Async optional| J1[SendAttendanceNotificationJob]
  J1 --> WS

Roadmap Implementasi (spesifik notifikasi absensi)
1) Definisikan Contracts: WhatsAppServiceInterface dan KirimiApiClientInterface (hanya endpoint send-message).
2) Definisikan DTOs minimal: CredentialsDTO, SendMessageDTO.
3) Implement KirimiApiClient::sendMessage (HTTP + retry + error mapping).
4) Implement WhatsAppService:
   - Helper template builder untuk late/absent/excused
   - Fungsi sendAttendanceLate/Absent/Excused yang memanggil client.sendMessage
5) Buat Job stub SendAttendanceNotificationJob untuk eksekusi asynchronous.
6) Integrasi dengan modul absensi (trigger pada event status absensi).
7) Tambahkan logging dan uji dengan sandbox/nomor dummy.

Catatan Akhir
- Untuk kebutuhan Anda (notifikasi absensi), cukup mulai dari send-message saja melalui `WhatsAppService` + `KirimiApiClient`.
- Kompetensi lain (broadcast masif, OTP, device) dapat ditambahkan bertahap tanpa mengubah pemanggil yang ada.