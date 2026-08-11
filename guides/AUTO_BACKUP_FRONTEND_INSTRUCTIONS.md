# تعليمات النسخ الاحتياطي التلقائي - للفرونت إند

## نظرة عامة
تم إضافة ميزة النسخ الاحتياطي التلقائي (Auto Backup) إلى النظام. هذه الميزة تسمح للمستخدم بتحديد فترة زمنية معينة يقوم النظام تلقائياً بإنشاء نسخة احتياطية عندها.

---

## الحقول الجديدة في الإعدادات

تم إضافة 4 حقول جديدة إلى جدول `backup_settings`:

### 1. `auto_backup_enabled` (Boolean)
- **الوصف**: تفعيل أو إلغاء تفعيل النسخ الاحتياطي التلقائي
- **القيم المحتملة**: `true` أو `false`
- **القيمة الافتراضية**: `false`
- **ملاحظة**: يمكن إرسال القيمة كـ boolean أو string ("true"/"false")، النظام يقوم بالتحويل تلقائياً

### 2. `auto_backup_interval` (Integer)
- **الوصف**: الفترة الزمنية بين كل نسخة احتياطية تلقائية (بالدقائق)
- **النوع**: رقم صحيح (Integer)
- **القيمة الدنيا**: 1 دقيقة
- **القيمة الافتراضية**: 1440 دقيقة (يوم واحد)
- **أمثلة شائعة**:
  - `60` = كل ساعة
  - `720` = كل 12 ساعة
  - `1440` = كل 24 ساعة (يوم واحد)
  - `10080` = كل أسبوع
  - `43200` = كل شهر (30 يوم)

### 3. `auto_backup_type` (Enum)
- **الوصف**: نوع النسخة الاحتياطية التلقائية
- **القيم المحتملة**:
  - `"db"` = قاعدة البيانات فقط
  - `"files"` = الملفات فقط
  - `"both"` = قاعدة البيانات + الملفات
- **القيمة الافتراضية**: `"both"`

### 4. `last_auto_backup_at` (DateTime)
- **الوصف**: آخر وقت تم فيه تشغيل نسخة احتياطية تلقائية
- **النوع**: Timestamp/DateTime
- **للقراءة فقط**: هذا الحقل يتم تحديثه تلقائياً من قبل النظام، **لا تقم بإرساله عند التحديث**
- **القيمة**: `null` إذا لم يتم تشغيل نسخة تلقائية من قبل
- **Format**: ISO 8601 (مثال: `"2025-11-06T12:30:00.000000Z"`)

---

## API Endpoint

### جلب الإعدادات (GET)
```
GET /api/backup/settings
```

**Response:**
```json
{
  "status": "success",
  "data": {
    "id": 1,
    

    // ... باقي الحقول الموجودة سابقاً

    // الحقول الجديدة
    "auto_backup_enabled": false,
    "auto_backup_interval": 1440,
    "auto_backup_type": "both",
    "last_auto_backup_at": null,

    "created_at": "2025-11-06T10:00:00.000000Z",
    "updated_at": "2025-11-06T10:00:00.000000Z"
  }
}
```

### تحديث الإعدادات (POST/PUT)
```
POST /api/backup/settings
PUT /api/backup/settings
```

**Request Body (مثال كامل):**
```json
{
  

  // ... باقي الحقول الموجودة سابقاً

  // حقول النسخ الاحتياطي التلقائي
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both"
}
```

**ملاحظة مهمة**:
- لا ترسل `last_auto_backup_at` في الـ Request، هذا الحقل يتم تحديثه تلقائياً من قبل النظام
- يمكن إرسال `auto_backup_enabled` كـ boolean أو string

**Response:**
```json
{
  "status": "success",
  "message": "Backup settings updated successfully",
  "data": {
    "id": 1,
    
    "auto_backup_enabled": true,
    "auto_backup_interval": 1440,
    "auto_backup_type": "both",
    "last_auto_backup_at": null,
    // ... باقي البيانات
  }
}
```

---

## Validation Rules

### قواعد التحقق من البيانات:

```
auto_backup_enabled: boolean (اختياري)
auto_backup_interval: integer, min:1 (اختياري)
auto_backup_type: enum('db', 'files', 'both') (اختياري)
```

### أخطاء محتملة:

**إذا كان `auto_backup_interval` أقل من 1:**
```json
{
  "message": "The auto backup interval field must be at least 1.",
  "errors": {
    "auto_backup_interval": [
      "The auto backup interval field must be at least 1."
    ]
  }
}
```

**إذا كان `auto_backup_type` قيمة غير صحيحة:**
```json
{
  "message": "The selected auto backup type is invalid.",
  "errors": {
    "auto_backup_type": [
      "The selected auto backup type is invalid."
    ]
  }
}
```

---

## كيف يعمل النظام

### 1. التفعيل/التعطيل
- يجب أن يكون `enabled: true` (النظام الرئيسي مفعّل)
- يجب أن يكون `auto_backup_enabled: true` (النسخ التلقائي مفعّل)
- إذا كان أي منهما `false`، لن يتم تشغيل النسخ التلقائي

### 2. الجدولة (Scheduling)
- النظام يفحص كل **دقيقة واحدة** إذا حان وقت النسخ التلقائي
- يقوم بحساب الفرق الزمني بين الآن و `last_auto_backup_at`
- إذا كان الفرق أكبر من أو يساوي `auto_backup_interval`، يتم تشغيل النسخ الاحتياطي
- إذا كان `last_auto_backup_at` يساوي `null` (أول مرة)، يتم التشغيل فوراً

### 3. نوع النسخة
- يتم تحديد نوع النسخة بناءً على `auto_backup_type`
- `"db"`: نسخ قاعدة البيانات فقط
- `"files"`: نسخ الملفات فقط (من `storage/app/public`)
- `"both"`: نسخ قاعدة البيانات + الملفات

### 4. الإشعارات
- يتم إرسال الإشعارات حسب إعدادات القنوات الموجودة:
  - `telegram_enabled`
  - `email_enabled`
  - `webhook_enabled`
- الإشعار يتم إرساله **بعد** انتهاء النسخة الاحتياطية (نجاح أو فشل)

### 5. السجلات (Logs)
- كل نسخة تلقائية يتم تسجيلها في `backup_logs` بنوع `type: "auto"`
- النسخ اليدوية تكون `type: "manual"`

---

## أمثلة عملية

### مثال 1: تفعيل النسخ التلقائي كل يوم
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both"
}
```

### مثال 2: نسخ قاعدة البيانات فقط كل 6 ساعات
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 360,
  "auto_backup_type": "db"
}
```

### مثال 3: نسخ الملفات فقط كل أسبوع
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 10080,
  "auto_backup_type": "files"
}
```

### مثال 4: إيقاف النسخ التلقائي
```json
{
  "auto_backup_enabled": false
}
```

---

## UI/UX - توجيهات التصميم

### 1. قسم النسخ الاحتياطي التلقائي
يُنصح بإنشاء قسم منفصل أو Accordion/Collapse للنسخ التلقائي يحتوي على:

#### أ. Toggle Switch - تفعيل/تعطيل
```
[Toggle] Enable Auto Backup
```
- عند التفعيل، تظهر باقي الخيارات
- عند التعطيل، تختفي أو تصبح disabled

#### ب. Interval Input - الفترة الزمنية
يمكن تصميمها بطريقتين:

**الطريقة 1: Input مباشر بالدقائق**
```
Backup Interval (minutes): [____]
```

**الطريقة 2: Select مع خيارات جاهزة**
```
Backup Frequency:
[ ] Every hour (60 min)
[ ] Every 6 hours (360 min)
[ ] Every 12 hours (720 min)
[ ] Daily (1440 min)
[ ] Weekly (10080 min)
[ ] Custom: [____] minutes
```

**الطريقة 3 (الموصى بها): Composite Input**
```
Backup Interval:
[____] [Dropdown: Minutes/Hours/Days/Weeks]
```
يتم الحساب في الفرونت وإرسال القيمة بالدقائق:
- Minutes: القيمة × 1
- Hours: القيمة × 60
- Days: القيمة × 1440
- Weeks: القيمة × 10080

#### ج. Backup Type - نوع النسخة
```
Backup Type:
( ) Database only
( ) Files only
( ) Both (Database + Files)
```
أو كـ Select/Dropdown:
```
What to backup:
[Dropdown: Both / Database only / Files only]
```

#### د. معلومات إضافية (Info Display)
```
Last Auto Backup: 2 hours ago
Next Auto Backup: in 22 hours
Status: Active / Disabled
```

### 2. Conversion Helpers (للفرونت إند)

**تحويل من دقائق إلى عرض سهل القراءة:**
```javascript
function formatInterval(minutes) {
  if (minutes < 60) return `${minutes} minute(s)`;
  if (minutes < 1440) return `${Math.floor(minutes / 60)} hour(s)`;
  if (minutes < 10080) return `${Math.floor(minutes / 1440)} day(s)`;
  return `${Math.floor(minutes / 10080)} week(s)`;
}

// مثال
formatInterval(1440) // "1 day(s)"
formatInterval(360) // "6 hour(s)"
```

**حساب الوقت المتبقي للنسخة التالية:**
```javascript
function getNextBackupTime(lastAutoBackupAt, interval) {
  if (!lastAutoBackupAt) return 'Soon';

  const last = new Date(lastAutoBackupAt);
  const next = new Date(last.getTime() + interval * 60 * 1000);
  const now = new Date();

  if (next <= now) return 'Soon';

  const diffMinutes = Math.floor((next - now) / 60000);
  return formatInterval(diffMinutes);
}
```

### 3. Validation في الفرونت إند
```javascript
// التحقق من القيم قبل الإرسال
if (autoBackupEnabled) {
  if (!autoBackupInterval || autoBackupInterval < 1) {
    errors.push('Interval must be at least 1 minute');
  }

  if (!['db', 'files', 'both'].includes(autoBackupType)) {
    errors.push('Invalid backup type');
  }
}
```

---

## Troubleshooting

### المشكلة: النسخ التلقائي لا يعمل
**الحلول المحتملة:**
1. تأكد من `enabled: true` و `auto_backup_enabled: true`
2. تحقق من أن Laravel Scheduler يعمل (`php artisan schedule:run` أو Cron Job)
3. راجع الـ Logs في `storage/logs/laravel.log`

### المشكلة: الفترة الزمنية لا تعمل كما هو متوقع
**الحل:**
- تأكد من إرسال القيمة بالدقائق (Minutes) وليس بالساعات أو الأيام
- تحقق من قيمة `last_auto_backup_at` في قاعدة البيانات

### المشكلة: البيانات لا تُحدّث
**الحل:**
- تأكد من عدم إرسال `last_auto_backup_at` في الـ Request
- هذا الحقل للقراءة فقط ويتم تحديثه تلقائياً

---

## Laravel Scheduler Setup (للسيرفر)

**ملاحظة للمبرمج**: النظام يستخدم Laravel Scheduler، يجب إضافة Cron Job على السيرفر:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

هذا Cron Job يتم تشغيله **كل دقيقة** ويقوم بتشغيل جميع المهام المجدولة في Laravel.

---

## سيناريو كامل للاستخدام

### الخطوة 1: المستخدم يفتح صفحة الإعدادات
- يتم جلب البيانات من `GET /api/backup/settings`
- عرض القيم الحالية في الـ Form

### الخطوة 2: المستخدم يفعّل النسخ التلقائي
- يقوم بتفعيل Toggle "Enable Auto Backup"
- يختار الفترة: مثلاً "كل يوم" = 1440 دقيقة
- يختار النوع: "Both"

### الخطوة 3: حفظ التعديلات
```json
POST /api/backup/settings
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both"
}
```

### الخطوة 4: النظام يبدأ العمل
- كل دقيقة، النظام يفحص هل حان وقت النسخة
- بعد مرور 1440 دقيقة (24 ساعة)، يتم تشغيل نسخة احتياطية تلقائياً
- يتم تحديث `last_auto_backup_at` إلى الوقت الحالي
- يتم إرسال الإشعارات حسب الإعدادات

### الخطوة 5: المستخدم يمكنه مراجعة السجلات
```
GET /api/backup/logs
```
- يظهر السجل بنوع `type: "auto"`
- يمكن رؤية حالة النسخة (نجحت أو فشلت)

---

## ملاحظات مهمة

1. **Boolean Values**: يمكن إرسال القيم البوليانية كـ `true`/`false` أو كـ strings `"true"`/`"false"` - النظام يتعامل مع الحالتين

2. **Interval بالدقائق**: **جميع** القيم يجب أن تُرسل بالدقائق، حتى لو كان المستخدم يختار "أيام" أو "أسابيع"، قم بالتحويل في الفرونت إند

3. **Read-only Field**: لا ترسل `last_auto_backup_at` أبداً، هذا حقل للقراءة فقط

4. **Timezone**: النظام يستخدم الـ timezone المحدد في `backup_settings.timezone` لحساب الأوقات

5. **Dependencies**: النسخ التلقائي يعتمد على:
   - `enabled: true` (النظام الرئيسي)
   - `auto_backup_enabled: true` (النسخ التلقائي)
   - Laravel Scheduler يعمل على السيرفر

6. **Notifications**: الإشعارات تُرسل بعد كل نسخة تلقائية حسب القنوات المفعّلة (Telegram/Email/Webhook)

---

## الخلاصة

تم إضافة 4 حقول جديدة فقط:
- `auto_backup_enabled` (Boolean)
- `auto_backup_interval` (Integer - بالدقائق)
- `auto_backup_type` (Enum: db/files/both)
- `last_auto_backup_at` (DateTime - للقراءة فقط)

الـ API يعمل بنفس الطريقة السابقة (GET/POST/PUT)، فقط أضف هذه الحقول إلى الـ Form الموجود.
