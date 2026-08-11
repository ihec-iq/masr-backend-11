# دليل الواجهة الأمامية - إعدادات النسخ الاحتياطي
## Frontend Guide for Backup Settings

---

## نظرة عامة | Overview

هذا الدليل يوضح جميع حقول إعدادات النسخ الاحتياطي المتاحة في API، وكيفية تنظيمها في الواجهة الأمامية.

---

## API Endpoints

### 1. الحصول على الإعدادات | Get Settings

**Endpoint:**
```
GET /api/backup/settings
```

**Authentication:** Required (Bearer Token)

**Response:** `200 OK`

---

### 2. تحديث الإعدادات | Update Settings

**Endpoint:**
```
POST /api/backup/settings
```

**Authentication:** Required (Bearer Token)

**Request Body:** JSON Object (يرسل فقط الحقول المراد تحديثها)

**Response:** `200 OK`

---

## الحقول المتاحة | Available Fields

### القسم الأول: إعدادات النسخ التلقائي | Auto Backup Settings

#### 1. `auto_backup_enabled` (boolean)
- **الوصف:** تفعيل/تعطيل النسخ الاحتياطي التلقائي
- **القيمة الافتراضية:** `false`
- **ملاحظات:**
  - هذا هو **المفتاح الرئيسي والوحيد** للتحكم بكل النظام
  - عند التفعيل: يبدأ النظام بأخذ نسخ احتياطية تلقائية حسب الفترة المحددة
  - عند التعطيل: يتوقف النسخ التلقائي تماماً
  - **لا يوجد** حقل `enabled` منفصل - تم إزالته لتبسيط النظام
- **واجهة المستخدم:**
  - Toggle/Switch بارز في قسم "Auto Backup"
  - يفضل أن يكون بلون مميز (مثلاً أخضر عند التفعيل)
  - عند التفعيل: يظهر باقي حقول النسخ التلقائي
  - عند التعطيل: يمكن إخفاء أو تعطيل باقي الحقول

#### 2. `auto_backup_interval` (integer)
- **الوصف:** الفترة الزمنية بين كل نسخة تلقائية (بالدقائق)
- **القيمة الافتراضية:** `1440` (24 ساعة = يوم واحد)
- **القيم الصالحة:** `>= 1` (على الأقل دقيقة واحدة)
- **ملاحظات:**
  - القيمة يجب أن تكون بالدقائق
  - الفترات الشائعة:
    - `60` = كل ساعة
    - `360` = كل 6 ساعات
    - `720` = كل 12 ساعة
    - `1440` = يومياً
    - `10080` = أسبوعياً
    - `43200` = شهرياً (30 يوم)
- **واجهة المستخدم:**
  - **خيار 1:** Dropdown/Select مع خيارات جاهزة:
    - "كل ساعة" = 60
    - "كل 6 ساعات" = 360
    - "كل 12 ساعة" = 720
    - "يومياً" = 1440
    - "أسبوعياً" = 10080
    - "شهرياً" = 43200
    - "مخصص" = يظهر input للإدخال اليدوي
  - **خيار 2:** Number Input مع Label توضيحي (بالدقائق)
  - **خيار 3:** مجموعة Radio Buttons للخيارات الشائعة + Custom Input
  - **توصية:** إضافة نص توضيحي يحول الدقائق إلى ساعات/أيام (مثلاً: "1440 دقيقة = يوم واحد")

#### 3. `auto_backup_type` (enum)
- **الوصف:** نوع النسخة الاحتياطية التلقائية
- **القيمة الافتراضية:** `"both"`
- **القيم الصالحة:**
  - `"db"` = قاعدة البيانات فقط
  - `"files"` = الملفات فقط
  - `"both"` = قاعدة البيانات + الملفات
- **واجهة المستخدم:**
  - **خيار 1:** Radio Buttons (موصى به):
    - ○ Database Only
    - ○ Files Only
    - ⦿ Database + Files (default)
  - **خيار 2:** Segmented Control (iOS style)
  - **خيار 3:** Dropdown/Select
  - يفضل إضافة أيقونات للتوضيح:
    - 🗄️ Database
    - 📁 Files
    - 📦 Both

#### 4. `last_auto_backup_at` (timestamp, nullable, READ-ONLY)
- **الوصف:** آخر وقت تم فيه تشغيل نسخة تلقائية
- **القيمة:** `null` إذا لم يتم تشغيل نسخة تلقائية من قبل، أو timestamp
- **ملاحظات:**
  - **هذا الحقل READ-ONLY** - لا يتم إرساله في الـ POST
  - يتم تحديثه تلقائياً من قبل النظام
  - يُستخدم لحساب الوقت المتبقي للنسخة التالية
- **واجهة المستخدم:**
  - عرض في قسم "Status" أو "Information"
  - **إذا `null`:** عرض "لم يتم تشغيل نسخة تلقائية بعد"
  - **إذا موجود:** عرض التاريخ والوقت بتنسيق مناسب
  - **توصية:** إضافة حساب للوقت المتبقي للنسخة التالية:
    - `next_backup_at = last_auto_backup_at + auto_backup_interval`
    - عرض Countdown Timer أو Progress Bar

---

### القسم الثاني: إعدادات التخزين | Storage Settings

#### 5. `backup_path` (string)
- **الوصف:** مسار حفظ النسخ الاحتياطية
- **القيمة الافتراضية:** `storage/app/backups`
- **واجهة المستخدم:** Text Input (مع توضيح أنه مسار نسبي من جذر المشروع)

#### 6. `max_storage_mb` (integer, nullable)
- **الوصف:** الحد الأقصى للمساحة المستخدمة للنسخ الاحتياطية (بالميجابايت)
- **القيمة الافتراضية:** `null` (غير محدود)
- **القيم الصالحة:** `>= 100` أو `null`
- **واجهة المستخدم:**
  - Checkbox: "تفعيل حد أقصى للمساحة"
  - Number Input (يظهر عند التفعيل)
  - عرض المساحة الحالية المستخدمة (إن وجدت)

---

### القسم الثالث: إعدادات الاحتفاظ | Retention Settings

#### 7. `keep_daily_days` (integer, nullable)
- **الوصف:** عدد الأيام للاحتفاظ بالنسخ اليومية
- **القيمة الافتراضية:** `7`
- **القيم الصالحة:** `>= 1` أو `null`
- **واجهة المستخدم:** Number Input مع Label "الاحتفاظ بالنسخ اليومية لـ X أيام"

#### 8. `keep_weekly_weeks` (integer, nullable)
- **الوصف:** عدد الأسابيع للاحتفاظ بالنسخ الأسبوعية
- **القيمة الافتراضية:** `4`
- **القيم الصالحة:** `>= 1` أو `null`
- **واجهة المستخدم:** Number Input مع Label "الاحتفاظ بالنسخ الأسبوعية لـ X أسابيع"

#### 9. `keep_monthly_months` (integer, nullable)
- **الوصف:** عدد الأشهر للاحتفاظ بالنسخ الشهرية
- **القيمة الافتراضية:** `6`
- **القيم الصالحة:** `>= 1` أو `null`
- **واجهة المستخدم:** Number Input مع Label "الاحتفاظ بالنسخ الشهرية لـ X أشهر"

---

### القسم الرابع: إعدادات الإشعارات | Notification Settings

#### 10. `notify_enabled` (boolean)
- **الوصف:** تفعيل/تعطيل الإشعارات عند اكتمال النسخ الاحتياطي
- **القيمة الافتراضية:** `false`
- **واجهة المستخدم:** Toggle/Switch في قسم "Notifications"

#### 11. `notify_on_success` (boolean)
- **الوصف:** إرسال إشعار عند نجاح النسخة
- **القيمة الافتراضية:** `true`
- **واجهة المستخدم:** Checkbox (يظهر عند تفعيل `notify_enabled`)

#### 12. `notify_on_failure` (boolean)
- **الوصف:** إرسال إشعار عند فشل النسخة
- **القيمة الافتراضية:** `true`
- **واجهة المستخدم:** Checkbox (يظهر عند تفعيل `notify_enabled`)

#### 13. `stale_hours` (integer, nullable)
- **الوصف:** عدد الساعات بدون نسخة ناجحة قبل إرسال تنبيه
- **القيمة الافتراضية:** `48`
- **القيم الصالحة:** `>= 1` أو `null`
- **ملاحظات:** إذا مرت X ساعة بدون نسخة ناجحة، يُرسل تنبيه
- **واجهة المستخدم:** Number Input مع Label "تنبيه بعد X ساعة بدون نسخة ناجحة"

---

### القسم الخامس: قنوات الإشعارات - Telegram

#### 14. `telegram_enabled` (boolean)
- **الوصف:** تفعيل إشعارات Telegram
- **القيمة الافتراضية:** `false`
- **واجهة المستخدم:** Toggle/Switch

#### 15. `telegram_bot_token` (string, nullable)
- **الوصف:** Telegram Bot Token
- **واجهة المستخدم:**
  - Text Input (type="password" أو مع زر لإظهار/إخفاء)
  - Placeholder: "123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11"

#### 16. `telegram_chat_ids` (array, nullable)
- **الوصف:** قائمة Chat IDs لإرسال الإشعارات إليها
- **تنسيق البيانات:**
  - **في GET Response:** Array of strings مثل `["123456789", "-100123456789"]`
  - **في POST Request:** Array of strings
- **واجهة المستخدم:**
  - **خيار 1:** Multiple Text Inputs (Add/Remove)
  - **خيار 2:** Tags Input (يمكن إضافة/حذف Chat IDs كـ Tags)
  - **خيار 3:** Textarea (كل ID في سطر منفصل)
  - يجب التحقق من أن كل ID رقم صحيح أو يبدأ بـ "-" للمجموعات

---

### القسم السادس: قنوات الإشعارات - Email

#### 17. `email_enabled` (boolean)
- **الوصف:** تفعيل إشعارات Email
- **القيمة الافتراضية:** `false`
- **واجهة المستخدم:** Toggle/Switch

#### 18. `email_recipients` (array, nullable)
- **الوصف:** قائمة البريد الإلكتروني لإرسال الإشعارات
- **تنسيق البيانات:**
  - **في GET Response:** Array of strings مثل `["admin@example.com", "backup@example.com"]`
  - **في POST Request:** Array of strings
- **واجهة المستخدم:**
  - **خيار 1:** Multiple Email Inputs (Add/Remove)
  - **خيار 2:** Tags Input مع Validation للإيميل
  - **خيار 3:** Textarea (كل إيميل في سطر منفصل)
  - يجب التحقق من صحة تنسيق البريد الإلكتروني

---

### القسم السابع: قنوات الإشعارات - Webhook

#### 19. `webhook_enabled` (boolean)
- **الوصف:** تفعيل إشعارات Webhook
- **القيمة الافتراضية:** `false`
- **واجهة المستخدم:** Toggle/Switch

#### 20. `webhook_url` (string, nullable)
- **الوصف:** URL الـ Webhook لإرسال الإشعارات
- **واجهة المستخدم:**
  - Text Input (type="url")
  - Placeholder: "https://example.com/webhook/backup"
  - Validation: يجب أن يبدأ بـ https://

#### 21. `webhook_secret` (string, nullable)
- **الوصف:** Secret Key للتوقيع على طلبات Webhook
- **واجهة المستخدم:**
  - Text Input (type="password" أو مع زر لإظهار/إخفاء)
  - زر "Generate" لتوليد secret عشوائي

---

### القسم الثامن: إشعارات المشرفين | Admin Notifications

#### 22. `notify_admins` (boolean)
- **الوصف:** إرسال إشعارات لجميع المشرفين المسجلين
- **القيمة الافتراضية:** `false`
- **ملاحظات:**
  - يُرسل للمشرفين الذين لديهم `notification_token` في جدول `admins`
  - يعمل بشكل مستقل عن `telegram_chat_ids` و `email_recipients`
- **واجهة المستخدم:** Checkbox أو Toggle في قسم Notifications

---

## تنظيم الواجهة الأمامية | UI Organization

### هيكل الصفحة المقترح | Suggested Page Structure

#### **1. Header Section**
- عنوان الصفحة: "Backup Settings" / "إعدادات النسخ الاحتياطي"
- زر "Save Settings" / "حفظ الإعدادات" (ثابت في الأعلى أو يطفو)

---

#### **2. Auto Backup Settings Card** ⭐ (الأهم والرئيسي)
- **العنوان:** "Automatic Backup" / "النسخ التلقائي"
- **الترتيب:**
  1. `auto_backup_enabled` Toggle (بارز ومميز)
  2. **عند التفعيل، يظهر:**
     - `auto_backup_interval` (Dropdown/Select مع خيارات جاهزة)
     - `auto_backup_type` (Radio Buttons مع أيقونات)
     - **Status Panel:**
       - `last_auto_backup_at` (عرض فقط)
       - الوقت المتبقي للنسخة التالية (Countdown)
       - Progress Bar للفترة الزمنية
- **ملاحظة هامة:**
  - هذا هو **المفتاح الوحيد** للتحكم بكل النظام
  - نص توضيحي: "تفعيل هذا الخيار يبدأ النسخ التلقائي حسب الإعدادات المحددة"

---

#### **3. Storage Settings Card**
- `backup_path` Input
- `max_storage_mb` (Checkbox + Number Input)
- عرض المساحة الحالية المستخدمة (إن أمكن)

---

#### **4. Retention Policy Card**
- `keep_daily_days` Input
- `keep_weekly_weeks` Input
- `keep_monthly_months` Input
- شرح مبسط لسياسة الاحتفاظ

---

#### **5. Notifications Card**
- `notify_enabled` Toggle (رئيسي)
- **عند التفعيل:**
  - `notify_on_success` Checkbox
  - `notify_on_failure` Checkbox
  - `stale_hours` Input
  - `notify_admins` Checkbox

---

#### **6. Notification Channels Section**

**6.1 Telegram Sub-Card**
- `telegram_enabled` Toggle
- **عند التفعيل:**
  - `telegram_bot_token` Input
  - `telegram_chat_ids` Multiple Inputs/Tags
  - زر "Test Connection" (اختياري)

**6.2 Email Sub-Card**
- `email_enabled` Toggle
- **عند التفعيل:**
  - `email_recipients` Multiple Inputs/Tags
  - زر "Send Test Email" (اختياري)

**6.3 Webhook Sub-Card**
- `webhook_enabled` Toggle
- **عند التفعيل:**
  - `webhook_url` Input
  - `webhook_secret` Input (مع زر Generate)
  - زر "Test Webhook" (اختياري)

---

#### **7. Footer Section**
- زر "Save Settings" (رئيسي، لون بارز)
- زر "Reset to Defaults" (ثانوي)
- آخر تحديث للإعدادات (إن وجد)

---

## Validation Rules | قواعد التحقق

### Client-Side Validation

#### Boolean Fields
- `auto_backup_enabled`, `notify_enabled`, `notify_on_success`, `notify_on_failure`, `telegram_enabled`, `email_enabled`, `webhook_enabled`, `notify_admins`
- **القيمة:** `true` أو `false` فقط

#### Integer Fields
- `auto_backup_interval`: `>= 1`
- `max_storage_mb`: `>= 100` أو `null`
- `keep_daily_days`: `>= 1` أو `null`
- `keep_weekly_weeks`: `>= 1` أو `null`
- `keep_monthly_months`: `>= 1` أو `null`
- `stale_hours`: `>= 1` أو `null`

#### String Fields
- `backup_path`: غير فارغ
- `telegram_bot_token`: غير فارغ إذا `telegram_enabled: true`
- `webhook_url`:
  - يجب أن يبدأ بـ `https://`
  - تنسيق URL صحيح
- `webhook_secret`: غير فارغ إذا `webhook_enabled: true`

#### Array Fields
- `telegram_chat_ids`:
  - كل عنصر يجب أن يكون رقم صحيح أو يبدأ بـ "-"
  - غير فارغ إذا `telegram_enabled: true`
- `email_recipients`:
  - كل عنصر يجب أن يكون بريد إلكتروني صحيح
  - غير فارغ إذا `email_enabled: true`

#### Enum Fields
- `auto_backup_type`: يجب أن يكون أحد `"db"`, `"files"`, `"both"`

---

## Server-Side Validation Responses

### عند إرسال Request خاطئ:

**Response:** `422 Unprocessable Entity`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "auto_backup_interval": [
      "The auto backup interval must be at least 1."
    ],
    "telegram_chat_ids": [
      "The telegram chat ids field is required when telegram enabled is true."
    ]
  }
}
```

**كيفية المعالجة:**
- عرض الأخطاء بجانب كل حقل
- تمييز الحقول الخاطئة باللون الأحمر
- عرض رسالة خطأ عامة في أعلى الصفحة

---

## حالات خاصة | Special Cases

### 1. تحويل String إلى Boolean تلقائياً

الـ Backend يحول تلقائياً:
- `"true"`, `"1"`, `"yes"` → `true`
- `"false"`, `"0"`, `"no"` → `false`

**لكن:** الأفضل إرسال `true`/`false` مباشرة من Frontend

---

### 2. تحويل String إلى Integer تلقائياً

الـ Backend يحول تلقائياً:
- `"123"` → `123`

**لكن:** الأفضل إرسال رقم مباشرة من Frontend

---

### 3. الحقول المرسلة فقط | Partial Updates

**يمكن** إرسال فقط الحقول المراد تحديثها:

```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 720
}
```

الحقول الأخرى **لن تتغير**.

---

### 4. حقل `last_auto_backup_at`

**لا ترسله أبداً** في POST Request.
- إذا تم إرساله، سيتم **تجاهله**
- يُحدث تلقائياً من قبل النظام

---

## UX Recommendations | توصيات تجربة المستخدم

### 1. التفاعل مع `auto_backup_enabled`

**عند التفعيل:**
- إظهار Animation لظهور باقي الحقول
- إظهار رسالة توضيحية: "سيتم تشغيل النسخ التلقائي حسب الفترة المحددة"

**عند التعطيل:**
- إظهار Confirmation Dialog: "هل أنت متأكد من تعطيل النسخ التلقائي؟"
- إخفاء أو تعطيل باقي الحقول

---

### 2. عرض الوقت المتبقي

حساب الوقت المتبقي للنسخة التالية:
- `last_auto_backup_at + auto_backup_interval = next_backup_at`
- عرض Countdown Timer
- تحديث كل دقيقة أو كل ثانية

**مثال:**
- "النسخة التالية خلال: 3 ساعات و 25 دقيقة"
- Progress Bar يمتلئ مع مرور الوقت

---

### 3. Preset Options لـ `auto_backup_interval`

**خيارات جاهزة موصى بها:**
- كل ساعة (60)
- كل 6 ساعات (360)
- كل 12 ساعة (720)
- يومياً (1440) ⭐ (الافتراضي)
- كل 3 أيام (4320)
- أسبوعياً (10080)
- شهرياً (43200)
- مخصص (Custom Input)

---

### 4. Visual Indicators

**Status Badges:**
- 🟢 "Active" إذا `auto_backup_enabled: true`
- 🔴 "Inactive" إذا `auto_backup_enabled: false`

**Notification Channels:**
- عرض عدد القنوات المفعلة: "2 channels active"
- أيقونات للقنوات المفعلة (Telegram ✓, Email ✓, Webhook ✗)

---

### 5. Error Handling

**عند فشل الحفظ:**
- عرض Toast/Alert بالخطأ
- تمييز الحقول الخاطئة
- عدم إغلاق Modal/Page حتى يتم التصحيح

**عند نجاح الحفظ:**
- عرض Success Message
- تحديث البيانات في الواجهة
- (اختياري) إعادة التوجيه أو إغلاق Modal

---

### 6. Loading States

**عند تحميل الإعدادات:**
- Skeleton Loader أو Spinner
- تعطيل جميع الحقول حتى يتم التحميل

**عند حفظ الإعدادات:**
- تعطيل زر "Save"
- عرض Spinner في الزر
- منع التعديل على الحقول

---

### 7. Help Text / Tooltips

**إضافة نصوص مساعدة:**
- بجانب كل حقل غير واضح
- Tooltip عند Hover
- أيقونة "?" للمساعدة

**أمثلة:**
- `auto_backup_interval`: "الفترة الزمنية بين كل نسخة تلقائية. مثلاً: 1440 دقيقة = يوم واحد"
- `stale_hours`: "سيتم إرسال تنبيه إذا لم تكن هناك نسخة ناجحة خلال هذه الفترة"
- `telegram_chat_ids`: "يمكنك الحصول على Chat ID من @userinfobot على Telegram"

---

## Testing Checklist | قائمة الاختبار

### قبل إطلاق الواجهة:

- [ ] GET Request يعمل بشكل صحيح
- [ ] POST Request يحفظ جميع الحقول
- [ ] Validation يعمل على جميع الحقول
- [ ] Error Messages تظهر بشكل واضح
- [ ] `auto_backup_enabled` Toggle يعمل بشكل صحيح
- [ ] عرض الوقت المتبقي للنسخة التالية يعمل
- [ ] Array Fields (telegram_chat_ids, email_recipients) يتم إرسالها بشكل صحيح
- [ ] Enum Field (auto_backup_type) يُرسل كـ String
- [ ] حقل `last_auto_backup_at` لا يُرسل في POST
- [ ] Partial Updates تعمل (إرسال بعض الحقول فقط)
- [ ] Loading States تظهر عند التحميل والحفظ
- [ ] Success/Error Messages تظهر بشكل مناسب
- [ ] Responsive Design يعمل على جميع الشاشات
- [ ] RTL Support (إذا كان التطبيق يدعم العربية)

---

## API Request/Response Examples

### مثال 1: GET Settings

**Request:**
```
GET /api/backup/settings
Authorization: Bearer {token}
```

**Response Structure:**
```json
{
  "id": 1,
  "auto_backup_enabled": false,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both",
  "last_auto_backup_at": null,
  "backup_path": "storage/app/backups",
  "max_storage_mb": null,
  "keep_daily_days": 7,
  "keep_weekly_weeks": 4,
  "keep_monthly_months": 6,
  "notify_enabled": false,
  "notify_on_success": true,
  "notify_on_failure": true,
  "stale_hours": 48,
  "telegram_enabled": false,
  "telegram_bot_token": null,
  "telegram_chat_ids": null,
  "email_enabled": false,
  "email_recipients": null,
  "webhook_enabled": false,
  "webhook_url": null,
  "webhook_secret": null,
  "notify_admins": false,
  "created_at": "2025-11-06T10:00:00.000000Z",
  "updated_at": "2025-11-06T10:00:00.000000Z"
}
```

---

### مثال 2: POST Settings - تفعيل النسخ التلقائي

**Request:**
```
POST /api/backup/settings
Authorization: Bearer {token}
Content-Type: application/json
```

**Body:**
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both"
}
```

**Response:** `200 OK` (نفس الـ Structure أعلاه مع القيم المحدثة)

---

### مثال 3: POST Settings - تفعيل Telegram

**Body:**
```json
{
  "telegram_enabled": true,
  "telegram_bot_token": "123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11",
  "telegram_chat_ids": ["123456789", "-100123456789"]
}
```

---

### مثال 4: Validation Error Response

**Response:** `422 Unprocessable Entity`

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "auto_backup_interval": [
      "The auto backup interval must be at least 1."
    ],
    "telegram_chat_ids": [
      "The telegram chat ids field is required when telegram enabled is true."
    ],
    "webhook_url": [
      "The webhook url must be a valid URL."
    ]
  }
}
```

---

## ملاحظات مهمة للفرونت | Important Notes

### 1. المفتاح الوحيد للنظام
- **`auto_backup_enabled` هو المفتاح الوحيد** للتحكم بكل النظام
- تم إزالة حقل `enabled` لتبسيط النظام
- عند التفعيل: النسخ التلقائي يشتغل حسب الإعدادات

### 2. حقل `last_auto_backup_at`
- **READ-ONLY** - لا ترسله في POST
- استخدمه لحساب الوقت المتبقي فقط
- إذا `null` = لم يتم تشغيل نسخة تلقائية من قبل

### 3. Array Fields
- `telegram_chat_ids` و `email_recipients` يجب إرسالها كـ **Array of Strings**
- إذا فارغة: أرسل `[]` أو `null`
- لا ترسل Array فارغ مع تفعيل القناة

### 4. Enum Field
- `auto_backup_type` يجب إرساله كـ **String** وليس Integer

### 5. Nullable Fields
- الحقول التي تقبل `null`:
  - `max_storage_mb`
  - `keep_daily_days`
  - `keep_weekly_weeks`
  - `keep_monthly_months`
  - `stale_hours`
  - `telegram_bot_token`
  - `telegram_chat_ids`
  - `email_recipients`
  - `webhook_url`
  - `webhook_secret`
  - `last_auto_backup_at`

### 6. Partial Updates
- يمكن إرسال بعض الحقول فقط
- الحقول غير المرسلة **لن تتغير**
- يفضل إرسال جميع الحقول المعدلة فقط

---

## أولويات التطوير | Development Priorities

### المرحلة الأولى - الأساسيات (High Priority)
1. ✅ GET Settings Endpoint Integration
2. ✅ POST Settings Endpoint Integration
3. ✅ `auto_backup_enabled` Toggle
4. ✅ `auto_backup_interval` Dropdown/Select
5. ✅ `auto_backup_type` Radio Buttons
6. ✅ Form Validation
7. ✅ Error Handling
8. ✅ Success Messages

### المرحلة الثانية - التحسينات (Medium Priority)
1. ⏳ عرض `last_auto_backup_at`
2. ⏳ حساب الوقت المتبقي للنسخة التالية
3. ⏳ Countdown Timer
4. ⏳ Status Indicators (Active/Inactive)
5. ⏳ Help Text / Tooltips
6. ⏳ Loading States
7. ⏳ Responsive Design

### المرحلة الثالثة - الميزات المتقدمة (Low Priority)
1. 🔲 Progress Bar للفترة الزمنية
2. 🔲 Test Buttons (Telegram, Email, Webhook)
3. 🔲 Generate Secret Button
4. 🔲 عرض المساحة المستخدمة
5. 🔲 Dashboard Widget للنسخ التلقائي
6. 🔲 Notifications في الواجهة عند اقتراب موعد النسخة

---

## Contact & Support

للأسئلة أو المساعدة:
- راجع التوثيق الكامل في `guides/AUTO_BACKUP_README.md`
- تحقق من `storage/logs/laravel.log` للأخطاء

---

## Version

**Document Version:** 1.0
**Last Updated:** 2025-11-06
**Laravel Version:** 12.9.2
**API Version:** v1
