# دليل مبرمج الفرونت إند - ميزة النسخ الاحتياطي التلقائي

## 📌 المطلوب منك بالضبط

تم إضافة ميزة جديدة اسمها **النسخ الاحتياطي التلقائي**. المطلوب منك إضافة 3 حقول جديدة في صفحة إعدادات النسخ الاحتياطي.

---

## 🎯 الحقول الجديدة (3 حقول فقط)

### 1️⃣ حقل التفعيل/الإيقاف

**اسم الحقل:** `auto_backup_enabled`
**النوع:** Boolean (true/false)
**القيمة الافتراضية:** `false`

**شكل الـ UI:**
```
☑️ تفعيل النسخ الاحتياطي التلقائي
```
أو بالإنجليزي:
```
☑️ Enable Auto Backup
```

**ملاحظات:**
- Toggle Switch أو Checkbox
- عندما يكون مُفعّل: تظهر الحقول الأخرى
- عندما يكون مُطفي: تخفي أو تعطّل الحقول الأخرى

---

### 2️⃣ حقل الفترة الزمنية

**اسم الحقل:** `auto_backup_interval`
**النوع:** Integer (رقم صحيح)
**القيمة:** **بالدقائق**
**القيمة الدنيا:** 1
**القيمة الافتراضية:** 1440 (يوم واحد)

**شكل الـ UI (اختر أحد الخيارات):**

#### الخيار الأول: Input بسيط
```
الفترة الزمنية (بالدقائق): [_____]
```

#### الخيار الثاني: Select مع خيارات جاهزة (مُوصى به)
```
الفترة الزمنية:
[ ] كل ساعة (60 دقيقة)
[ ] كل 6 ساعات (360 دقيقة)
[ ] كل 12 ساعة (720 دقيقة)
[ ] يومياً (1440 دقيقة)
[ ] أسبوعياً (10080 دقيقة)
[ ] مخصص: [_____] دقيقة
```

#### الخيار الثالث: Composite Input (الأفضل)
```
الفترة الزمنية:
[___] [Dropdown: دقائق / ساعات / أيام / أسابيع]
```

**في حالة اختيار الخيار الثالث، الحسبة:**
```javascript
// المستخدم يدخل: 2 ساعات
// أنت تحسب: 2 × 60 = 120 دقيقة
// ترسل للـ API: auto_backup_interval = 120

const conversions = {
  'minutes': 1,
  'hours': 60,
  'days': 1440,
  'weeks': 10080
};

const minutes = inputValue * conversions[selectedUnit];
```

**أمثلة:**
- كل ساعة = `60`
- كل 6 ساعات = `360`
- يومياً = `1440`
- أسبوعياً = `10080`

---

### 3️⃣ حقل نوع النسخة

**اسم الحقل:** `auto_backup_type`
**النوع:** String (Enum)
**القيم المسموحة:** `"db"` أو `"files"` أو `"both"`
**القيمة الافتراضية:** `"both"`

**شكل الـ UI (اختر أحد الخيارات):**

#### الخيار الأول: Radio Buttons
```
نوع النسخة الاحتياطية:
○ قاعدة البيانات فقط (db)
○ الملفات فقط (files)
○ قاعدة البيانات + الملفات (both)
```

#### الخيار الثاني: Dropdown/Select
```
نوع النسخة الاحتياطية:
[Dropdown ▼]
- قاعدة البيانات + الملفات (both) ← الافتراضي
- قاعدة البيانات فقط (db)
- الملفات فقط (files)
```

**القيم التي ترسلها:**
- قاعدة البيانات فقط → `"db"`
- الملفات فقط → `"files"`
- قاعدة البيانات + الملفات → `"both"`

---

## 🔄 حقل رابع (للقراءة فقط - لا ترسله)

### 4️⃣ آخر وقت تشغيل تلقائي

**اسم الحقل:** `last_auto_backup_at`
**النوع:** DateTime (String)
**للقراءة فقط:** ✅ **لا ترسله في الـ Request أبداً**

**شكل الـ UI (اختياري):**
```
آخر نسخة تلقائية: منذ ساعتين
```
أو:
```
Last Auto Backup: 2 hours ago
```

**كود مساعد:**
```javascript
// تحويل التاريخ إلى "منذ X"
function timeAgo(dateString) {
  if (!dateString) return 'لم يتم بعد';

  const date = new Date(dateString);
  const now = new Date();
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 60) return `منذ ${diffMins} دقيقة`;
  if (diffMins < 1440) return `منذ ${Math.floor(diffMins / 60)} ساعة`;
  return `منذ ${Math.floor(diffMins / 1440)} يوم`;
}
```

---

## 📡 الـ API

### 1. جلب الإعدادات (عند فتح الصفحة)

**Request:**
```http
GET /api/backup/settings
```

**Response:**
```json
{
  "id": 1,
  

  // الحقول القديمة (موجودة سابقاً)
  "cron": "0 15 * * *",
  "timezone": "Asia/Baghdad",
  "max_storage_mb": 50000,
  // ... إلخ

  // ✨ الحقول الجديدة (هذي المطلوبة منك)
  "auto_backup_enabled": false,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both",
  "last_auto_backup_at": null,

  "created_at": "2025-11-06T10:00:00.000000Z",
  "updated_at": "2025-11-06T10:00:00.000000Z"
}
```

**خطوات العمل:**
1. أرسل `GET /api/backup/settings`
2. خذ القيم من الـ Response
3. اعرضها في الـ Form

---

### 2. حفظ الإعدادات (عند الضغط على Save)

**Request:**
```http
POST /api/backup/settings
Content-Type: application/json
```

**Body:**
```json
{
  // الحقول القديمة (ترسلها كالعادة)
  
  "cron": "0 15 * * *",
  "timezone": "Asia/Baghdad",
  // ... إلخ

  // ✨ الحقول الجديدة (أضفها فقط)
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both"

  // ⚠️ لا ترسل last_auto_backup_at
}
```

**⚠️ مهم جداً:**
- **لا ترسل** `last_auto_backup_at` في الـ Request
- هذا الحقل يتحدث تلقائياً من قبل السيرفر
- فقط اعرضه في الـ UI (إذا أردت)

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

## ✅ التحقق من البيانات (Validation)

قبل إرسال الـ Request، تحقق من:

### 1. إذا كان Auto Backup مُفعّل:

```javascript
if (autoBackupEnabled === true) {
  // تحقق من الفترة الزمنية
  if (!autoBackupInterval || autoBackupInterval < 1) {
    errors.push('الفترة الزمنية يجب أن تكون على الأقل 1 دقيقة');
  }

  // تحقق من نوع النسخة
  if (!['db', 'files', 'both'].includes(autoBackupType)) {
    errors.push('نوع النسخة غير صحيح');
  }
}
```

### 2. رسائل الخطأ المحتملة من السيرفر:

#### خطأ في الفترة الزمنية:
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

#### خطأ في نوع النسخة:
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

## 🎨 تصميم الـ UI (مقترحات)

### المقترح الأول: Accordion/Collapse

```
┌─────────────────────────────────────────────┐
│ ⚙️ إعدادات النسخ الاحتياطي                │
├─────────────────────────────────────────────┤
│                                             │
│ [الإعدادات القديمة هنا...]                 │
│                                             │
│ ▼ النسخ الاحتياطي التلقائي                │
│ ┌─────────────────────────────────────────┐ │
│ │ ☑️ تفعيل النسخ التلقائي                │ │
│ │                                         │ │
│ │ الفترة الزمنية:                        │ │
│ │ [___2___] [أيام ▼]                      │ │
│ │                                         │ │
│ │ نوع النسخة:                            │ │
│ │ ○ قاعدة البيانات فقط                   │ │
│ │ ○ الملفات فقط                          │ │
│ │ ⦿ قاعدة البيانات + الملفات            │ │
│ │                                         │ │
│ │ آخر نسخة تلقائية: منذ 3 ساعات          │ │
│ └─────────────────────────────────────────┘ │
│                                             │
│ [حفظ التغييرات]                            │
└─────────────────────────────────────────────┘
```

### المقترح الثاني: Inline في الصفحة الرئيسية

```
┌─────────────────────────────────────────────┐
│ ⚙️ إعدادات النسخ الاحتياطي                │
├─────────────────────────────────────────────┤
│                                             │
│ [الإعدادات القديمة هنا...]                 │
│                                             │
│ ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━ │
│                                             │
│ 🤖 النسخ التلقائي                          │
│                                             │
│ ☑️ تفعيل    [كل يوم ▼]    [DB + Files ▼]  │
│                                             │
│ آخر تشغيل: منذ 5 ساعات                     │
│                                             │
│ [حفظ التغييرات]                            │
└─────────────────────────────────────────────┘
```

---

## 💻 كود مساعد (JavaScript/TypeScript)

### مثال كامل - Form Component

```javascript
// State
const [autoBackupEnabled, setAutoBackupEnabled] = useState(false);
const [autoBackupInterval, setAutoBackupInterval] = useState(1440);
const [autoBackupType, setAutoBackupType] = useState('both');

// Fetch settings
async function fetchSettings() {
  const response = await fetch('/api/backup/settings');
  const data = await response.json();

  setAutoBackupEnabled(data.auto_backup_enabled);
  setAutoBackupInterval(data.auto_backup_interval);
  setAutoBackupType(data.auto_backup_type);
  // ... باقي الحقول
}

// Save settings
async function saveSettings() {
  const payload = {
    // الحقول القديمة
    enabled: true,
    cron: "0 15 * * *",
    // ... إلخ

    // الحقول الجديدة
    auto_backup_enabled: autoBackupEnabled,
    auto_backup_interval: autoBackupInterval,
    auto_backup_type: autoBackupType,

    // ⚠️ لا ترسل last_auto_backup_at
  };

  const response = await fetch('/api/backup/settings', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  });

  if (response.ok) {
    alert('تم الحفظ بنجاح');
  }
}
```

### Helper: تحويل الوحدات

```javascript
// تحويل من دقائق إلى عرض مفهوم
function formatInterval(minutes) {
  if (minutes < 60) {
    return `${minutes} دقيقة`;
  } else if (minutes < 1440) {
    return `${Math.floor(minutes / 60)} ساعة`;
  } else if (minutes < 10080) {
    return `${Math.floor(minutes / 1440)} يوم`;
  } else {
    return `${Math.floor(minutes / 10080)} أسبوع`;
  }
}

// أمثلة
formatInterval(60);    // "1 ساعة"
formatInterval(1440);  // "1 يوم"
formatInterval(10080); // "1 أسبوع"
```

### Helper: Composite Input Conversion

```javascript
// للاستخدام مع Composite Input
const UNITS = {
  minutes: 1,
  hours: 60,
  days: 1440,
  weeks: 10080
};

// تحويل من Input إلى دقائق
function toMinutes(value, unit) {
  return value * UNITS[unit];
}

// تحويل من دقائق إلى Input
function fromMinutes(minutes) {
  if (minutes % 10080 === 0) {
    return { value: minutes / 10080, unit: 'weeks' };
  }
  if (minutes % 1440 === 0) {
    return { value: minutes / 1440, unit: 'days' };
  }
  if (minutes % 60 === 0) {
    return { value: minutes / 60, unit: 'hours' };
  }
  return { value: minutes, unit: 'minutes' };
}

// مثال
const input = fromMinutes(1440);
// { value: 1, unit: 'days' }

const minutes = toMinutes(2, 'hours');
// 120
```

---

## 📋 Checklist - قائمة التحقق

قبل ما تسلّم الشغل، تأكد من:

### UI:
- [ ] أضفت Toggle/Checkbox للتفعيل (`auto_backup_enabled`)
- [ ] أضفت Input للفترة الزمنية (`auto_backup_interval`)
- [ ] أضفت Select/Radio لنوع النسخة (`auto_backup_type`)
- [ ] (اختياري) عرضت `last_auto_backup_at` كـ "منذ X"

### Functionality:
- [ ] عند فتح الصفحة، يتم جلب القيم من API
- [ ] عند الحفظ، يتم إرسال الحقول الجديدة
- [ ] **لا** يتم إرسال `last_auto_backup_at` في الـ Request
- [ ] التحقق من القيم قبل الإرسال (Validation)

### التحويلات:
- [ ] إذا استخدمت Composite Input، يتم التحويل إلى دقائق قبل الإرسال
- [ ] القيمة المرسلة هي **دقائق** (Integer)

### Testing:
- [ ] جربت تفعيل/إيقاف النسخ التلقائي
- [ ] جربت تغيير الفترة الزمنية
- [ ] جربت تغيير نوع النسخة
- [ ] تأكدت من حفظ الإعدادات بنجاح
- [ ] تأكدت من عرض القيم الصحيحة عند إعادة فتح الصفحة

---

## 🐛 استكشاف الأخطاء

### المشكلة: البيانات لا تُحفظ

**الحل:**
1. افتح Developer Console (F12)
2. راجع الـ Request الذي يُرسل
3. تأكد من أن الحقول بالأسماء الصحيحة:
   - `auto_backup_enabled` (boolean)
   - `auto_backup_interval` (number)
   - `auto_backup_type` (string: "db"/"files"/"both")

### المشكلة: خطأ Validation

**الحل:**
- تأكد من أن `auto_backup_interval` رقم صحيح وليس string
- تأكد من أن `auto_backup_type` أحد القيم: `"db"` أو `"files"` أو `"both"`

### المشكلة: القيم لا تظهر بعد الحفظ

**الحل:**
- تأكد من تحديث الـ State بعد الحفظ
- أو أعد جلب البيانات من الـ API بعد الحفظ

---

## 📞 للتواصل

إذا واجهت أي مشكلة أو عندك أي سؤال:

1. راجع هذا الملف مرة أخرى
2. جرّب الـ API من Postman/Insomnia
3. تأكد من أسماء الحقول صحيحة
4. راجع الـ Network Tab في Developer Tools

---

## ✨ الخلاصة

**المطلوب بالضبط:**

1. ✅ أضف **3 حقول جديدة** في صفحة الإعدادات:
   - `auto_backup_enabled` (Toggle)
   - `auto_backup_interval` (Number Input بالدقائق)
   - `auto_backup_type` (Select/Radio)

2. ✅ عند جلب البيانات: استخدم `GET /api/backup/settings`

3. ✅ عند الحفظ: أضف الحقول الجديدة إلى الـ Request

4. ✅ **لا ترسل** `last_auto_backup_at` (للقراءة فقط)

5. ✅ (اختياري) اعرض `last_auto_backup_at` كـ "منذ X"

**هذا كل شيء! 🎉**

التصميم على راحتك - المهم الحقول الثلاثة يكونوا موجودين ويشتغلوا.
