# ⚙️ Settings System Documentation

## Sistem Pengaturan Komprehensif untuk Presensiku

### **📋 OVERVIEW**

Sistem settings telah diimplementasikan menggunakan **MySQL database** dengan struktur yang fleksibel dan mudah digunakan. Settings diorganisir dalam **groups** dan mendukung **type casting**, **caching**, dan **audit trail**.

---

## 🗄️ **DATABASE STRUCTURE**

### **Tabel Settings**
```sql
CREATE TABLE settings (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    key VARCHAR(255) NOT NULL UNIQUE,
    value TEXT NULL,
    type ENUM('string', 'integer', 'boolean', 'json', 'float') DEFAULT 'string',
    group_name VARCHAR(100) NULL,
    description TEXT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_by BIGINT NULL,
    updated_by BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
);
```

### **Key Features:**
- ✅ **Type Casting** - Automatic value conversion
- ✅ **Grouping** - Organized by category
- ✅ **Audit Trail** - Track who changed what
- ✅ **Public/Private** - Control frontend access
- ✅ **Caching** - Performance optimization

---

## 🎛️ **SETTINGS GROUPS**

### **1. Dashboard Settings (`dashboard`)**
| Key | Type | Default | Public | Description |
|-----|------|---------|--------|-------------|
| `dashboard.auto_hide_delay` | integer | 10 | ✅ | Timer auto-hide (detik) |
| `dashboard.polling_interval` | integer | 1 | ✅ | Polling interval (detik) |
| `dashboard.show_test_button` | boolean | true | ❌ | Show test button di local |
| `dashboard.transition_duration` | integer | 300 | ✅ | Durasi animasi (ms) |

### **2. Attendance Settings (`attendance`)**
| Key | Type | Default | Public | Description |
|-----|------|---------|--------|-------------|
| `attendance.scan_detection_window` | integer | 3 | ❌ | Window deteksi scan (detik) |
| `attendance.allow_late_scan` | boolean | true | ❌ | Izinkan scan terlambat |
| `attendance.auto_status_calculation` | boolean | true | ❌ | Kalkulasi status otomatis |
| `attendance.require_photo` | boolean | false | ❌ | Wajib foto saat scan |
| `default_time_in_start` | string | 07:00 | ❌ | Default jam masuk mulai |
| `default_time_in_end` | string | 08:00 | ❌ | Default jam masuk selesai |
| `default_time_out_start` | string | 15:00 | ❌ | Default jam pulang mulai |
| `default_time_out_end` | string | 16:00 | ❌ | Default jam pulang selesai |

### **3. Notification Settings (`notification`)**
| Key | Type | Default | Public | Description |
|-----|------|---------|--------|-------------|
| `notification.enabled` | boolean | true | ❌ | Aktifkan notifikasi |
| `notification.late_threshold` | integer | 15 | ❌ | Batas terlambat (menit) |
| `notification.absent_notification_time` | string | 09:00 | ❌ | Jam kirim notif tidak hadir |
| `notification.channels` | json | ["whatsapp"] | ❌ | Channel notifikasi aktif |
| `wa_api_key` | string | "" | ❌ | WhatsApp API Key |
| `kesiswaan_whatsapp_number` | string | "" | ❌ | Nomor WA kesiswaan |

### **4. System Settings (`system`)**
| Key | Type | Default | Public | Description |
|-----|------|---------|--------|-------------|
| `system.timezone` | string | Asia/Jakarta | ✅ | Timezone aplikasi |
| `system.date_format` | string | d/m/Y | ✅ | Format tampilan tanggal |
| `system.language` | string | id | ✅ | Bahasa default |
| `system.maintenance_mode` | boolean | false | ✅ | Mode maintenance |

### **5. WhatsApp Templates (`whatsapp`)**
| Key | Type | Default | Public | Description |
|-----|------|---------|--------|-------------|
| `wa_message_late` | string | Template | ❌ | Template pesan terlambat |
| `wa_message_absent` | string | Template | ❌ | Template pesan tidak hadir |
| `wa_message_present` | string | Template | ❌ | Template pesan hadir |

---

## 💻 **USAGE EXAMPLES**

### **1. Using Setting Model Directly**
```php
use App\Models\Setting;

// Get setting value
$value = Setting::get('dashboard.auto_hide_delay', 10);

// Set setting value
Setting::set('dashboard.auto_hide_delay', 15, 'integer');

// Check if setting exists
if (Setting::has('notification.enabled')) {
    // Setting exists
}

// Get settings by group
$dashboardSettings = Setting::getByGroup('dashboard');

// Get all public settings
$publicSettings = Setting::getPublic();
```

### **2. Using SettingsHelper (Recommended)**
```php
use App\Helpers\SettingsHelper;

// Get grouped settings
$dashboardSettings = SettingsHelper::getDashboardSettings();
$attendanceSettings = SettingsHelper::getAttendanceSettings();
$notificationSettings = SettingsHelper::getNotificationSettings();

// Quick checks
if (SettingsHelper::isNotificationEnabled()) {
    // Send notification
}

if (SettingsHelper::isMaintenanceMode()) {
    // Show maintenance message
}

// Format date using system setting
$formattedDate = SettingsHelper::formatDate(now());

// Get WhatsApp message with variables
$message = SettingsHelper::getWhatsAppMessage('late', [
    'nama_siswa' => 'John Doe',
    'jam_masuk' => '08:15',
    'jam_seharusnya' => '08:00'
]);
```

### **3. In Controllers**
```php
use App\Helpers\SettingsHelper;

public function dashboard()
{
    $settings = SettingsHelper::getPublicSettings();
    
    return view('dashboard', compact('settings'));
}

public function updateAttendance(Request $request)
{
    $attendanceSettings = SettingsHelper::getAttendanceSettings();
    
    if (!$attendanceSettings['allow_late_scan']) {
        return response()->json(['error' => 'Late scan not allowed']);
    }
    
    // Process attendance...
}
```

### **4. In Livewire Components**
```php
use App\Helpers\SettingsHelper;

class RealtimeAttendanceDashboard extends Component
{
    public function mount()
    {
        $dashboardSettings = SettingsHelper::getDashboardSettings();
        
        $this->autoHideDelay = $dashboardSettings['auto_hide_delay'];
        $this->pollingInterval = $dashboardSettings['polling_interval'];
    }
}
```

---

## 🎯 **FILAMENT SETTINGS PAGE**

### **Fitur Halaman Settings:**
- ✅ **Grouped Sections** - Organized by category
- ✅ **Type-specific Inputs** - Toggle, TimePicker, Select, etc.
- ✅ **Helpful Descriptions** - Helper text for each setting
- ✅ **Icons** - Visual indicators for each section
- ✅ **Grid Layout** - Responsive design
- ✅ **Validation** - Input validation
- ✅ **Auto-save** - Save all settings at once

### **Navigation:**
- **Path:** Admin Panel → Pengaturan Sistem → Pengaturan Sistem
- **Icon:** `heroicon-o-cog-6-tooth`
- **Group:** Pengaturan Sistem
- **Sort Order:** 6

---

## 🔧 **ADVANCED FEATURES**

### **1. Caching System**
```php
// Settings are automatically cached for 1 hour
// Cache is cleared when settings are updated

// Manual cache management
SettingsHelper::clearCache();
SettingsHelper::refreshCache();
```

### **2. Type Casting**
```php
// Automatic type conversion based on 'type' column
$enabled = Setting::get('notification.enabled'); // Returns boolean
$threshold = Setting::get('notification.late_threshold'); // Returns integer
$channels = Setting::get('notification.channels'); // Returns array (from JSON)
```

### **3. Audit Trail**
```php
// Track who created/updated settings
$setting = Setting::find(1);
echo $setting->creator->name; // Who created this setting
echo $setting->updater->name; // Who last updated this setting
```

### **4. Public Settings API**
```php
// For frontend/dashboard use - only returns is_public = true settings
Route::get('/api/settings/public', function() {
    return SettingsHelper::getPublicSettings();
});
```

---

## 🚀 **INTEGRATION EXAMPLES**

### **1. RealtimeAttendanceDashboard Integration**
```php
// In RealtimeAttendanceDashboard.php
public function checkForNewScans()
{
    $settings = SettingsHelper::getAttendanceSettings();
    
    $query = StudentAttendance::where('created_at', '>=', 
        now()->subSeconds($settings['scan_detection_window'])
    );
    
    // Use dynamic window from settings
}
```

### **2. WhatsApp Notification Integration**
```php
// In NotificationService.php
public function sendLateNotification($student, $scanTime)
{
    if (!SettingsHelper::isNotificationEnabled()) {
        return;
    }
    
    $message = SettingsHelper::getWhatsAppMessage('late', [
        'nama_siswa' => $student->name,
        'jam_masuk' => $scanTime->format('H:i'),
        'jam_seharusnya' => SettingsHelper::get('default_time_in_end')
    ]);
    
    // Send WhatsApp message
}
```

### **3. Frontend Integration**
```javascript
// Fetch public settings for frontend
fetch('/api/settings/public')
    .then(response => response.json())
    .then(settings => {
        // Use settings in frontend
        const autoHideDelay = settings['dashboard.auto_hide_delay'] * 1000;
        const pollingInterval = settings['dashboard.polling_interval'] * 1000;
    });
```

---

## 🛠️ **MAINTENANCE & MANAGEMENT**

### **1. Adding New Settings**
```php
// Via Seeder
Setting::create([
    'key' => 'new.setting',
    'value' => 'default_value',
    'type' => 'string',
    'group_name' => 'group',
    'description' => 'Description',
    'is_public' => false,
]);

// Via Helper
SettingsHelper::set('new.setting', 'value', 'string');
```

### **2. Backup Settings**
```bash
# Export settings to JSON
php artisan tinker
>>> file_put_contents('settings_backup.json', Setting::all()->toJson());

# Import settings from JSON
>>> $settings = json_decode(file_get_contents('settings_backup.json'), true);
>>> foreach($settings as $setting) { Setting::create($setting); }
```

### **3. Environment-specific Settings**
```php
// Different defaults per environment
$defaultValue = app()->environment('production') ? 'prod_value' : 'dev_value';
Setting::get('key', $defaultValue);
```

---

## 📊 **MONITORING & ANALYTICS**

### **1. Settings Usage Tracking**
```php
// Track which settings are accessed most
Log::info('Setting accessed', [
    'key' => $key,
    'user_id' => auth()->id(),
    'timestamp' => now()
]);
```

### **2. Settings Change History**
```php
// Model events automatically track changes
// Check updated_by and updated_at columns for audit trail
```

---

## 🎯 **BEST PRACTICES**

### **1. Naming Convention**
- Use **dot notation**: `group.setting_name`
- Use **snake_case** for setting names
- Keep group names **short and descriptive**

### **2. Default Values**
- Always provide **sensible defaults**
- Use **type-appropriate** defaults
- Consider **environment differences**

### **3. Security**
- Mark sensitive settings as **`is_public = false`**
- Use **password** input type for secrets
- Consider **encryption** for sensitive values

### **4. Performance**
- Use **SettingsHelper** for grouped access
- Leverage **caching** for frequently accessed settings
- Avoid **database queries** in tight loops

---

**🎯 Settings system sekarang fully functional dan siap digunakan untuk mengelola semua konfigurasi aplikasi secara terpusat!**
