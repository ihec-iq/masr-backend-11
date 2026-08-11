# 📋 خطة بناء Frontend Vue 3 لنظام Backup

## 📚 جدول المحتويات
- [المتطلبات الأساسية](#المتطلبات-الأساسية)
- [خارطة API Routes](#خارطة-api-routes)
- [هيكل المشروع](#هيكل-المشروع)
- [المراحل التفصيلية](#المراحل-التفصيلية)
- [أجزاء الميزة الرئيسية](#أجزاء-الميزة-الرئيسية)
- [الأولويات](#الأولويات)
- [الجدول الزمني](#الجدول-الزمني)

---

## 🎯 المتطلبات الأساسية

### التقنيات المستخدمة:
- **Vue 3** (Composition API)
- **Vue Router** (للتنقل بين الصفحات)
- **Pinia** (State Management)
- **Axios** (API Calls)
- **TailwindCSS** أو **Vuetify** (UI Framework)
- **VueUse** (Utilities)
- **Chart.js** (للرسوم البيانية)
- **Day.js** (معالجة التواريخ)
- **SweetAlert2** (Notifications)

---

## 🗺️ خارطة API Routes

### 📍 1. Backup Operations (عمليات النسخ الاحتياطي)

| Method | Endpoint | Parameters | الوصف |
|--------|----------|------------|-------|
| POST | `/api/backup/run` | `backup_type?: 'db'\|'files'\|'both'` | تشغيل نسخة احتياطية جديدة |
| GET | `/api/backup/list` | - | عرض قائمة جميع النسخ الاحتياطية |
| DELETE | `/api/backup/delete` | `path: string` | حذف نسخة احتياطية واحدة |
| DELETE | `/api/backup/delete_all` | - | حذف جميع النسخ الاحتياطية |
| DELETE | `/api/backup/deleteAllByLogs` | - | حذف النسخ بناءً على السجلات |
| POST | `/api/backup/temp-link` | `path: string` | الحصول على رابط تحميل مؤقت |
| POST | `/api/backup/restore` | `backup_log_id`, `restore_database`, `restore_files`, `verify_checksum` | استعادة نسخة احتياطية |
| GET | `/api/backup/logs` | `per_page?: number`, `type?: string`, `status?: string` | جلب سجلات النسخ الاحتياطي (Backup Logs) |

**مثال Response لـ `/api/backup/list`:**
```json
[
  {
    "path": "Backups/laravel/db/mysql/2025-01-28_123456.zip",
    "size": 15728640,
    "lastModified": 1706438400,
    "url": null
  }
]
```

**مثال Request لـ `/api/backup/run`:**
```json
{
  "backup_type": "both"
}
```

**مثال Response لـ `/api/backup/run`:**
```json
{
  "status": "ok",
  "ran_at": "2025-01-28T12:34:56.000000Z",
  "backup_type": "both"
}
```

**مثال Response لـ `/api/backup/logs`:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "manual",
      "status": "success",
      "include_files": true,
      "total_size": 15728640,
      "duration": 45,
      "message": "Backup completed successfully.",
      "error_details": null,
      "databases": ["mysql"],
      "files": ["/storage/app/public"],
      "checksum": null,
      "checksums": {
        "Backups/MsarERP/db/mysql/backup_db_mysql_2025-11-02.zip": "abc123..."
      },
      "backup_paths": [
        "Backups/MsarERP/db/mysql/backup_db_mysql_2025-11-02.zip"
      ],
      "storage_disk": "google",
      "created_at": "2025-11-02T11:10:22.000000Z",
      "updated_at": "2025-11-02T11:11:07.000000Z",
      "started_at": "2025-11-02T11:10:22.000000Z",
      "completed_at": "2025-11-02T11:11:07.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost/api/v1/backup/logs?page=1",
    "last": "http://localhost/api/v1/backup/logs?page=3",
    "prev": null,
    "next": "http://localhost/api/v1/backup/logs?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "per_page": 15,
    "to": 15,
    "total": 42
  }
}
```

---

### ⚙️ 2. Settings (إعدادات النظام)

| Method | Endpoint | Parameters | الوصف |
|--------|----------|------------|-------|
| GET | `/api/backup/settings` | - | عرض جميع الإعدادات الحالية |
| PUT | `/api/backup/settings` | انظر للأسفل | تحديث الإعدادات |

**أهم حقول Settings:**

#### General (عام)
- `enabled`: boolean - تفعيل/تعطيل النظام
- `cron`: string - جدولة التشغيل التلقائي
- `timezone`: string - المنطقة الزمنية (مثل: Asia/Baghdad)
- `max_storage_mb`: integer - الحد الأقصى للمساحة

#### Backup Scope (نطاق النسخ)
- `include_files`: boolean - نسخ الملفات
- `include_paths`: array - مسارات إضافية للنسخ
- `exclude_paths`: array - مسارات للاستثناء
- `multi_db`: boolean - نسخ عدة قواعد بيانات
- `selected_databases`: array - قواعد البيانات المختارة

#### Storage (التخزين)
- `disk`: string - القرص المستخدم (local, google)
- `drive_folder`: string - مجلد Google Drive
- `temp_link_expiry`: integer - مدة صلاحية رابط التحميل (بالدقائق)
- `checksum_enabled`: boolean - تفعيل التحقق من Checksum

#### Notifications (الإشعارات)
- `notify_enabled`: boolean - تفعيل الإشعارات (مفتاح رئيسي)
- `notify_on`: string - متى نرسل (success, failure, both)
- `telegram_enabled`: boolean - تفعيل إشعارات Telegram
- `email_enabled`: boolean - تفعيل إشعارات Email
- `webhook_enabled`: boolean - تفعيل إشعارات Webhook
- `emails`: string - قائمة الإيميلات (مفصولة بفاصلة)
- `telegram_bot_token`: string - توكن بوت Telegram
- `telegram_chat_ids`: string - معرفات المحادثات (مفصولة بفاصلة)
- `webhook_urls`: string - روابط Webhook (مفصولة بفاصلة)
- `webhook_secret`: string - مفتاح Webhook السري

#### Retention Policy (سياسة الاحتفاظ)
- `keep_daily_days`: integer - الاحتفاظ بالنسخ اليومية (أيام)
- `keep_weekly_weeks`: integer - الاحتفاظ بالنسخ الأسبوعية (أسابيع)
- `keep_monthly_months`: integer - الاحتفاظ بالنسخ الشهرية (أشهر)
- `keep_yearly_years`: integer - الاحتفاظ بالنسخ السنوية (سنوات)

**مثال Response لـ `/api/backup/settings`:**
```json
{
  "id": 1,
  
  "cron": "0 2 * * *",
  "timezone": "Asia/Baghdad",
  "include_files": true,
  "disk": "local",
  "notify_enabled": true,
  "notify_on": "both",
  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,
  "keep_daily_days": 7,
  "created_at": "2025-01-28T12:34:56.000000Z"
}
```

---

### 👥 3. Admins Management (إدارة المسؤولين)

| Method | Endpoint | Parameters | الوصف |
|--------|----------|------------|-------|
| GET | `/api/backup/admins` | - | عرض جميع المسؤولين |
| POST | `/api/backup/admins` | انظر للأسفل | إضافة مسؤول جديد |
| PUT | `/api/backup/admins/{id}` | انظر للأسفل | تحديث بيانات مسؤول |
| DELETE | `/api/backup/admins/{id}` | - | حذف مسؤول |

**حقول Admin:**
- `name`: string - الاسم
- `email`: string - البريد الإلكتروني
- `telegram_id`: string - معرف Telegram
- `notify_via`: array - وسائل الإشعار `['email', 'telegram']`
- `active`: boolean - فعال أم لا

**مثال Request لـ `POST /api/backup/admins`:**
```json
{
  "name": "Ahmad Ali",
  "email": "ahmad@example.com",
  "telegram_id": "123456789",
  "notify_via": ["email", "telegram"],
  "active": true
}
```

**مثال Response:**
```json
{
  "id": 1,
  "name": "Ahmad Ali",
  "email": "ahmad@example.com",
  "telegram_id": "123456789",
  "notify_via": ["email", "telegram"],
  "active": true,
  "created_at": "2025-01-28T12:34:56.000000Z"
}
```

---

### 🏥 4. Health Check (فحص الحالة)

| Method | Endpoint | Parameters | الوصف |
|--------|----------|------------|-------|
| GET | `/api/health/backup` | - | فحص حالة النظام |

---

### 🧪 5. Testing (الاختبار)

| Method | Endpoint | Parameters | الوصف |
|--------|----------|------------|-------|
| POST | `/api/backup/test-email` | `email`, `event?: 'success'\|'failure'` | إرسال email تجريبي |
| GET | `/api/backup/preview-email` | `event?: 'success'\|'failure'` | معاينة Email في المتصفح |

---
 
## 🚀 المراحل التفصيلية

### **المرحلة 1: إعداد المشروع (يوم 1)**

#### 1.1 إنشاء المشروع:
```bash
npm create vite@latest backup-dashboard -- --template vue
cd backup-dashboard
npm install
```

#### 1.2 تثبيت المكتبات الأساسية:
```bash
# Core
npm install vue-router@4 pinia axios

# UI Framework
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# Utilities
npm install @vueuse/core
npm install chart.js vue-chartjs
npm install dayjs
npm install @headlessui/vue @heroicons/vue
npm install sweetalert2
```

#### 1.3 هيكلة المجلدات:
```bash
mkdir -p src/{components/{backup,settings,admins,logs,common},composables,stores,services,router,views,utils}
```

---

### **المرحلة 2: إعداد API Service (يوم 1-2)**

الملفات المطلوبة:
- `src/services/api.js` - Axios instance مع interceptors
- `src/services/backupService.js` - جميع API calls للـ backup
- `src/services/settingsService.js` - API calls للإعدادات
- `src/services/adminService.js` - API calls للمسؤولين

**الوظائف الأساسية:**
- Request interceptor (إضافة Token)
- Response interceptor (معالجة الأخطاء)
- Error handling موحد
- Loading states

---

### **المرحلة 3: State Management (يوم 2)**

الملفات المطلوبة:
- `src/stores/backup.js` - حالة النسخ الاحتياطية
- `src/stores/settings.js` - حالة الإعدادات
- `src/stores/admins.js` - حالة المسؤولين
- `src/stores/ui.js` - حالة الواجهة (loading, dialogs, etc)

**المسؤوليات:**
- إدارة البيانات المركزية
- Actions للعمليات غير المتزامنة
- Getters للبيانات المحسوبة
- Mutations للتحديثات

---

### **المرحلة 4: Composables (يوم 2-3)**

الملفات المطلوبة:
- `src/composables/useBackup.js` - منطق النسخ الاحتياطي
- `src/composables/useSettings.js` - منطق الإعدادات
- `src/composables/useAdmins.js` - منطق المسؤولين
- `src/composables/useNotifications.js` - Toast notifications
- `src/composables/useFormatters.js` - تنسيق البيانات (size, date, etc)

**الوظائف:**
- Business logic قابل لإعادة الاستخدام
- Reactive state
- Helper functions

---

### **المرحلة 5: المكونات الأساسية (يوم 3-5)**

#### Common Components:
- `AppLayout.vue` - Layout رئيسي مع Sidebar
- `Sidebar.vue` - قائمة التنقل
- `TopBar.vue` - شريط العلوي (user info, notifications)
- `LoadingSpinner.vue` - مؤشر التحميل
- `ConfirmDialog.vue` - dialog للتأكيد
- `StatCard.vue` - بطاقة الإحصائيات
- `Tabs.vue` - مكون التبويبات

#### Backup Components:
- `BackupCard.vue` - بطاقة نسخة احتياطية واحدة
- `BackupTable.vue` - جدول النسخ
- `BackupStats.vue` - إحصائيات النسخ
- `BackupRunDialog.vue` - dialog لتشغيل نسخة
- `BackupRestoreDialog.vue` - dialog للاستعادة
- `BackupFilters.vue` - فلاتر البحث والتصفية

#### Settings Components:
- `GeneralSettings.vue` - الإعدادات العامة
- `NotificationSettings.vue` - إعدادات الإشعارات
- `StorageSettings.vue` - إعدادات التخزين
- `ScheduleSettings.vue` - إعدادات الجدولة
- `RetentionSettings.vue` - سياسة الاحتفاظ
- `ScopeSettings.vue` - نطاق النسخ

#### Admins Components:
- `AdminTable.vue` - جدول المسؤولين
- `AdminForm.vue` - نموذج إضافة/تعديل مسؤول

---

### **المرحلة 6: الصفحات الرئيسية (يوم 5-7)**

#### 6.1 Dashboard.vue
**المحتوى:**
- إحصائيات (عدد النسخ، المساحة، آخر نسخة)
- رسم بياني للنسخ حسب التاريخ
- قائمة بآخر النسخ الاحتياطية
- حالة النظام

#### 6.2 BackupList.vue
**المحتوى:**
- زر "Run Backup" مع خيارات النوع
- جدول النسخ الاحتياطية
- فلاتر (التاريخ، النوع، الحجم)
- أزرار (Download, Delete, Restore)
- Search

#### 6.3 Settings.vue
**المحتوى:**
- تبويبات متعددة
- نموذج لكل تبويب
- زر Save في الأسفل
- Preview لـ Cron expression

#### 6.4 Admins.vue
**المحتوى:**
- جدول المسؤولين
- زر "Add Admin"
- Dialog للإضافة/التعديل
- Toggle للـ active status

#### 6.5 BackupLogs.vue
**المحتوى:**
- جدول السجلات
- فلاتر (الحالة، التاريخ، النوع)
- تفاصيل كل عملية
- Timeline view

#### 6.6 Health.vue
**المحتوى:**
- حالة النظام (OK, Warning, Error)
- آخر نسخة ناجحة
- المساحة المتاحة
- Configuration issues

---

### **المرحلة 7: Features المتقدمة (يوم 7-10)**

#### 7.1 Real-time Updates
- Polling كل 30 ثانية لتحديث حالة النسخ
- Auto-refresh للقوائم

#### 7.2 Progress Indicators
- Progress bar أثناء تشغيل النسخ الاحتياطي
- Progress للاستعادة

#### 7.3 Toast Notifications
- إشعارات النجاح/الفشل
- Toast للعمليات طويلة المدى

#### 7.4 Charts & Analytics
- رسم بياني لحجم النسخ عبر الزمن
- توزيع النسخ حسب النوع
- معدل النجاح/الفشل

#### 7.5 Export/Download
- تصدير القوائم إلى CSV
- تحميل تقرير شامل

---

## 🧩 أجزاء الميزة الرئيسية

### **الجزء 1: Dashboard (لوحة التحكم)**
**الغرض:** نظرة عامة سريعة

**APIs المستخدمة:**
- `GET /api/backup/list`
- `GET /api/backup/settings`
- `GET /api/health/backup`

**البيانات المعروضة:**
- إجمالي عدد النسخ
- المساحة المستخدمة الكلية
- آخر نسخة احتياطية (تاريخ + وقت)
- حالة النظام (enabled/disabled)
- رسم بياني للنسخ حسب التاريخ
- نسبة النجاح/الفشل

---

### **الجزء 2: Backup Management**
**الغرض:** إدارة شاملة للنسخ

**الميزات:**
1. **تشغيل نسخة جديدة:**
   - Dialog مع 3 خيارات (DB, Files, Both)
   - زر "Run Now"
   - Loading indicator

2. **عرض القائمة:**
   - جدول مع columns:
     - File Name
     - Size (formatted)
     - Type (badge: DB, Files, Both)
     - Date & Time
     - Actions
   - Pagination
   - Search
   - Sort (by date, size, name)

3. **فلاتر:**
   - Date range picker
   - Type filter (dropdown)
   - Size filter (slider)

4. **أزرار الإجراءات:**
   - Download (يطلب temp-link ثم يفتح الرابط)
   - Delete (مع confirm dialog)
   - Restore (مع dialog لاختيار الخيارات)
   - Delete All (مع confirm خاص)

---

### **الجزء 3: Settings**
**الغرض:** تكوين شامل للنظام

**التبويبات:**

#### Tab 1: General
- **Enable System** (Toggle switch)
- **Timezone** (Dropdown مع search)
- **Max Storage** (Input number + unit)

#### Tab 2: Schedule
- **Cron Expression** (Input text)
- **Visual Cron Builder** (اختياري - واجهة سهلة)
- **Preview** (يعرض متى التشغيل القادم)

#### Tab 3: Backup Scope
- **Include Files** (Toggle)
- **Multi Database** (Toggle)
- **Selected Databases** (Multi-select dropdown)
- **Include Paths** (Tags input)
- **Exclude Paths** (Tags input)

#### Tab 4: Storage
- **Disk** (Radio buttons: Local, Google Drive)
- **Google Drive Folder** (Input - يظهر فقط إذا اختار Google)
- **Temp Link Expiry** (Input number + minutes)
- **Enable Checksum** (Toggle)

#### Tab 5: Notifications
- **Enable Notifications** (Toggle - مفتاح رئيسي لتفعيل جميع الإشعارات)
- **Notify On** (Radio: Success, Failure, Both)

**قسم Telegram:**
- **Enable Telegram Notifications** (Toggle)
- **Telegram Bot Token** (Input - يظهر فقط إذا تم تفعيل Telegram)
- **Telegram Chat IDs** (Tags input - يظهر فقط إذا تم تفعيل Telegram)

**قسم Email:**
- **Enable Email Notifications** (Toggle)
- **Emails** (Tags input - multiple emails - يظهر فقط إذا تم تفعيل Email)
- **Test Email Button** (يفتح dialog لإدخال email وإرسال تجريبي)

**قسم Webhook:**
- **Enable Webhook Notifications** (Toggle)
- **Webhook URLs** (Tags input - يظهر فقط إذا تم تفعيل Webhook)
- **Webhook Secret** (Input password - يظهر فقط إذا تم تفعيل Webhook)

#### Tab 6: Retention Policy
- **Daily Backups** (Input number + days)
- **Weekly Backups** (Input number + weeks)
- **Monthly Backups** (Input number + months)
- **Yearly Backups** (Input number + years)
- **Preview** (يعرض كم نسخة سيتم الاحتفاظ بها تقريباً)

**زر Save في نهاية الصفحة**

**ملاحظات مهمة للتطوير:**
1. الحقول المتعلقة بكل قناة (Telegram/Email/Webhook) يجب أن تظهر/تختفي بناءً على حالة Toggle الخاص بها
2. يمكن تفعيل قناة واحدة أو أكثر في نفس الوقت
3. يجب التحقق من وجود البيانات المطلوبة (مثل Bot Token, Emails) قبل حفظ الإعدادات
4. عرض رسالة تحذير إذا تم تفعيل قناة بدون إدخال البيانات المطلوبة

**مثال على الـ Validation في Vue:**
```javascript
// في Composable أو Component
const validateNotifications = () => {
  const errors = []

  if (settings.value.telegram_enabled && !settings.value.telegram_bot_token) {
    errors.push('Telegram Bot Token is required when Telegram notifications are enabled')
  }

  if (settings.value.email_enabled && !settings.value.emails) {
    errors.push('At least one email is required when Email notifications are enabled')
  }

  if (settings.value.webhook_enabled && !settings.value.webhook_urls) {
    errors.push('At least one webhook URL is required when Webhook notifications are enabled')
  }

  return errors
}
```

---

### **الجزء 4: Admins Management**
**الغرض:** إدارة المسؤولين

**الواجهة:**
1. **جدول:**
   - Columns: Name, Email, Telegram ID, Notify Via (badges), Active (toggle), Actions
   - Sort & Search

2. **زر Add Admin:**
   - يفتح Dialog/Modal

3. **Admin Form (في Dialog):**
   - Name (input text - required)
   - Email (input email - required)
   - Telegram ID (input text - optional)
   - Notify Via (checkboxes: Email, Telegram)
   - Active (toggle - default true)
   - Save & Cancel buttons

4. **Actions:**
   - Edit (يفتح نفس الـ Dialog بالبيانات الحالية)
   - Delete (مع confirm dialog)

---

### **الجزء 5: Backup Logs**
**الغرض:** عرض تاريخ العمليات

**الواجهة:**
1. **جدول:**
   - Columns:
     - ID
     - Date & Time
     - Type (badge: Manual, Auto)
     - Status (badge: Success, Failed, Running)
     - Include Files (icon: yes/no)
     - Size (formatted)
     - Duration
     - Message
     - Actions (View Details)

2. **فلاتر:**
   - Status (dropdown: All, Success, Failed, Running)
   - Type (dropdown: All, Manual, Auto)
   - Date Range

3. **تفاصيل (في Dialog أو صفحة منفصلة):**
   - جميع المعلومات
   - قائمة Databases المنسوخة
   - قائمة Files المنسوخة
   - Checksums
   - Error details (إن وجدت)

---

### **الجزء 6: Health Status**
**الغرض:** مراقبة صحة النظام

**الواجهة:**
1. **Overall Status Card:**
   - Icon كبير (✓ أخضر، ⚠ أصفر، ✗ أحمر)
   - Status text (Healthy, Warning, Error)

2. **Checks List:**
   - ✓ Last Backup Success (with time)
   - ✓ Disk Space Available (percentage + bar)
   - ✓ Configuration Valid
   - ⚠ Stale Backup Warning (إن تأخرت النسخة)
   - ✗ Errors (list of issues)

3. **Actions:**
   - Refresh button
   - Run Health Check button

---

## 🎨 تصميم UI

### Color Palette:
```javascript
// tailwind.config.js
module.exports = {
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f5f7ff',
          100: '#ebf0ff',
          200: '#d6e0ff',
          300: '#b3c7ff',
          400: '#8aa3ff',
          500: '#667eea',
          600: '#5a67d8',
          700: '#4c51bf',
          800: '#3c3f99',
          900: '#2d3280'
        },
        success: {
          light: '#d4edda',
          DEFAULT: '#10b981',
          dark: '#047857'
        },
        warning: {
          light: '#fff3cd',
          DEFAULT: '#f59e0b',
          dark: '#d97706'
        },
        error: {
          light: '#f8d7da',
          DEFAULT: '#ef4444',
          dark: '#dc2626'
        },
        gray: {
          50: '#f9fafb',
          100: '#f3f4f6',
          200: '#e5e7eb',
          300: '#d1d5db',
          400: '#9ca3af',
          500: '#6b7280',
          600: '#4b5563',
          700: '#374151',
          800: '#1f2937',
          900: '#111827'
        }
      }
    }
  }
}
```

### Typography:
- **Headings:** font-bold
- **Body:** font-normal
- **Monospace:** للـ paths, checksums, cron expressions

### Spacing:
- استخدام Tailwind spacing (4, 8, 12, 16, 20, 24, 32...)
- Consistent padding & margins

### Components Style:
- **Cards:** bg-white shadow rounded-lg border
- **Buttons:** rounded-md shadow hover:shadow-lg transition
- **Inputs:** border rounded-md focus:ring-2 focus:ring-primary-500
- **Badges:** px-2 py-1 rounded-full text-xs font-medium
- **Tables:** border rounded-lg overflow-hidden

---

## 🎯 الأولويات

### **Phase 1: MVP (الحد الأدنى) - 3 أيام**
1. ✅ إعداد المشروع + API Services
2. ✅ Dashboard (إحصائيات بسيطة)
3. ✅ Backup List (عرض + تشغيل + تحميل)
4. ✅ Settings (General + Storage + Notifications فقط)

**الهدف:** نظام يعمل ويمكن تشغيل backup وتحميله

---

### **Phase 2: Core Features - 3 أيام**
5. ✅ Delete & Restore
6. ✅ Admins Management
7. ✅ Settings الباقية (Schedule + Retention + Scope)
8. ✅ Filters & Search

**الهدف:** إدارة كاملة للنسخ والإعدادات

---

### **Phase 3: Advanced Features - 4 أيام**
9. ✅ Logs & History
10. ✅ Health Monitoring
11. ✅ Real-time Updates (Polling)
12. ✅ Charts & Analytics
13. ✅ Toast Notifications
14. ✅ Progress Indicators

**الهدف:** تجربة مستخدم احترافية

---

### **Phase 4: Polish & Testing - 2 أيام**
15. ✅ Responsive Design (Mobile & Tablet)
16. ✅ Error Handling شامل
17. ✅ Loading States في كل مكان
18. ✅ Validation على الـ Forms
19. ✅ Testing (Unit + E2E)
20. ✅ Documentation

**الهدف:** منتج جاهز للإنتاج

---

## 📅 الجدول الزمني التفصيلي

| اليوم | المهمة الرئيسية | التفاصيل |
|------|-----------------|----------|
| **1** | إعداد + API | إنشاء المشروع، تثبيت المكتبات، إعداد API services |
| **2** | State + Composables | Pinia stores، Composables للمنطق |
| **3** | Common Components | Layout، Sidebar، Cards، Loading |
| **4** | Backup Components | BackupTable، BackupRunDialog، BackupCard |
| **5** | Dashboard + BackupList | الصفحة الرئيسية + صفحة القوائم |
| **6** | Settings Page | جميع التبويبات + Forms |
| **7** | Admins Page | جدول + Forms + CRUD |
| **8** | Logs + Health | صفحات السجلات والصحة |
| **9** | Advanced Features | Charts، Polling، Notifications |
| **10** | Testing + Polish | إصلاح الأخطاء، Responsive، Documentation |

---

## 📊 Data Flow (تدفق البيانات)

```
┌─────────────┐
│ User Action │
└──────┬──────┘
       ↓
┌─────────────────┐
│ Vue Component   │
└──────┬──────────┘
       ↓
┌─────────────────┐
│ Composable      │ ← Business Logic
└──────┬──────────┘
       ↓
┌─────────────────┐
│ Service         │ ← API Calls
└──────┬──────────┘
       ↓
┌─────────────────┐
│ Backend API     │
└──────┬──────────┘
       ↓
┌─────────────────┐
│ Response        │
└──────┬──────────┘
       ↓
┌─────────────────┐
│ Store (Pinia)   │ ← Update State
└──────┬──────────┘
       ↓
┌─────────────────┐
│ Component       │ ← Re-render
└─────────────────┘
```

---

## 🔧 ملف `.env`

```env
VITE_API_URL=http://localhost/api
VITE_APP_NAME=Backup Dashboard
VITE_APP_VERSION=1.0.0
```

---

## 📦 Dependencies النهائية

```json
{
  "dependencies": {
    "vue": "^3.4.0",
    "vue-router": "^4.2.0",
    "pinia": "^2.1.0",
    "axios": "^1.6.0",
    "@vueuse/core": "^10.7.0",
    "dayjs": "^1.11.0",
    "chart.js": "^4.4.0",
    "vue-chartjs": "^5.3.0",
    "sweetalert2": "^11.10.0",
    "@headlessui/vue": "^1.7.0",
    "@heroicons/vue": "^2.1.0"
  },
  "devDependencies": {
    "@vitejs/plugin-vue": "^5.0.0",
    "vite": "^5.0.0",
    "tailwindcss": "^3.4.0",
    "postcss": "^8.4.0",
    "autoprefixer": "^10.4.0",
    "vitest": "^1.0.0",
    "@vue/test-utils": "^2.4.0"
  }
}
```

---

## 📝 ملاحظات مهمة

### قواعد التسمية:
- ❌ **ممنوع المختصرات:** `const b = ...`, `const usr = ...`
- ✅ **أسماء واضحة:** `const backup = ...`, `const user = ...`
- استخدام camelCase للـ variables و functions
- استخدام PascalCase للـ Components و Classes

### Error Handling:
- try-catch في كل API call
- رسائل خطأ واضحة للمستخدم
- Logging للـ console في حالة التطوير

### Loading States:
- كل عملية غير متزامنة يجب أن يكون لها loading indicator
- Skeleton loaders للقوائم الطويلة
- Disable buttons أثناء العمليات

### Validation:
- Frontend validation على كل form
- رسائل خطأ واضحة تحت كل input
- Highlight للـ fields الخاطئة

### Responsiveness:
- Mobile-first approach
- Breakpoints: sm (640px), md (768px), lg (1024px), xl (1280px)
- Sidebar يتحول لـ drawer على Mobile

---

## 🎓 Resources مفيدة

- **Vue 3 Docs:** https://vuejs.org/
- **Pinia Docs:** https://pinia.vuejs.org/
- **Vue Router:** https://router.vuejs.org/
- **TailwindCSS:** https://tailwindcss.com/
- **VueUse:** https://vueuse.org/
- **Chart.js:** https://www.chartjs.org/
- **Headless UI:** https://headlessui.com/

---

## ✅ Checklist قبل البدء

- [ ] تأكد من تشغيل Laravel Backend
- [ ] اختبر جميع API endpoints بـ Postman/Insomnia
- [ ] حدد UI Framework (TailwindCSS مقترح)
- [ ] جهز الـ Design Assets (colors, icons, etc)
- [ ] حدد الأولويات حسب الوقت المتاح
- [ ] أنشئ Git repository للمشروع

---

**تم إنشاء هذه الخطة في:** 2025-01-28

**آخر تحديث:** 2025-11-05

**النسخة:** 1.2

---

## 📖 ملفات مرجعية إضافية

للحصول على توثيق شامل لـ APIs الخاصة بإدارة الأدمنز والإشعارات، راجع الملف:

**[ADMINS_NOTIFICATIONS_API_DOCS.md](./ADMINS_NOTIFICATIONS_API_DOCS.md)**

هذا الملف يحتوي على:
- توثيق شامل لجميع API Endpoints
- Data Structures كاملة
- Validation Rules (Backend)
- أمثلة Request/Response
- شرح آلية جمع المستلمين من المصدرين
- أمثلة عملية على جميع الحالات

**ملاحظة:** التصميم والكود الخاص بالـ Frontend متروك لك بالكامل. هذا الملف يحتوي على توثيق Backend فقط.

---

## 📝 سجل التحديثات (Changelog)

### النسخة 1.2 (2025-11-05)
- **إضافة نظام إدارة الأدمنز الشامل:**
  - جدول `backup_admins` لإدارة المستلمين
  - دعم حقل `webhook_url` في جدول الأدمنز
  - دعم **القيم المتعددة المفصولة بفاصلة** في جميع الحقول (email, telegram_id, webhook_url)
  - مثال: `"admin1@test.com,admin2@test.com,admin3@test.com"`
- **توحيد آلية الإشعارات:**
  - جمع المستلمين من مصدرين: `backup_admins` + `backup_settings`
  - إزالة التكرار تلقائياً
  - إرسال منفصل لكل مستلم
- **تحديث API endpoints:**
  - `GET /api/v1/backup/admins` - جلب قائمة الأدمنز
  - `POST /api/v1/backup/admins` - إضافة أدمن جديد
  - `PUT /api/v1/backup/admins/{id}` - تعديل أدمن
  - `DELETE /api/v1/backup/admins/{id}` - حذف أدمن
- **إنشاء ملف توثيق منفصل:** `ADMINS_NOTIFICATIONS_API_DOCS.md` (توثيق Backend APIs فقط)

### النسخة 1.1 (2025-11-02)
- إضافة متغيرات التحكم المستقل في قنوات الإشعارات:
  - `telegram_enabled`: تفعيل/تعطيل إشعارات Telegram بشكل مستقل
  - `email_enabled`: تفعيل/تعطيل إشعارات Email بشكل مستقل
  - `webhook_enabled`: تفعيل/تعطيل إشعارات Webhook بشكل مستقل
- تحديث واجهة إعدادات الإشعارات (Tab 5) لتشمل toggles منفصلة لكل قناة
- تحديث مثال Response لـ `/api/backup/settings` ليشمل المتغيرات الجديدة
- إضافة endpoint جديد `GET /api/backup/logs` لجلب سجلات النسخ الاحتياطي
- إضافة `BackupLogResource` في الـ Backend لتحويل البيانات حسب TypeScript interface
- دعم الـ pagination والتصفية (type, status) في سجلات النسخ الاحتياطي

### النسخة 1.0 (2025-01-28)
- النسخة الأولية من خطة Frontend
