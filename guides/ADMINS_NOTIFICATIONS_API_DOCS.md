# 📋 API Documentation: إدارة الأدمنز والإشعارات

## 📚 جدول المحتويات
- [نظرة عامة](#نظرة-عامة)
- [API Endpoints](#api-endpoints)
- [Data Structures](#data-structures)
- [Validation Rules](#validation-rules)
- [آلية العمل](#آلية-العمل)
- [أمثلة الاستخدام](#أمثلة-الاستخدام)

---

## 🎯 نظرة عامة

### الهدف:
توفير واجهات API لإدارة المستلمين للإشعارات وإعدادات الإشعارات.

### آلية العمل:
1. النظام يجمع المستلمين من **مصدرين**:
   - جدول `backup_admins` - الأدمنز المضافين يدوياً
   - حقول `backup_settings` - القيم الافتراضية (default)

2. **دعم القيم المتعددة:**
   - كل حقل (email, telegram_id, webhook_url) يدعم قيم متعددة مفصولة بفاصلة
   - مثال: `"admin1@test.com,admin2@test.com,admin3@test.com"`
   - المسافات قبل/بعد الفاصلة يتم تجاهلها تلقائياً

3. **إزالة التكرار:**
   - النظام يزيل التكرار تلقائياً عند جمع المستلمين من المصدرين
   - مثلاً: إذا كان نفس الـ email موجود في admins و settings، يُرسل له إشعار واحد فقط

4. **التحكم بالقنوات:**
   - كل قناة (Email, Telegram, Webhook) لها toggle منفصل
   - `telegram_enabled`, `email_enabled`, `webhook_enabled`

---

## 🗺️ API Endpoints

### 1. Admins Management

#### 1.1 GET `/api/v1/backup/admins`
**الوصف:** جلب قائمة جميع الأدمنز

**Headers:**
```
Accept: application/json
```

**Response:** `200 OK`
```json
[
  {
    "id": 1,
    "name": "Ahmad Ali",
    "email": "ahmad@example.com,ahmad2@example.com",
    "telegram_id": "123456789,987654321",
    "webhook_url": "https://webhook.site/test1,https://webhook.site/test2",
    "notify_via": ["email", "telegram", "webhook"],
    "active": true,
    "created_at": "2025-11-05T10:30:00.000000Z",
    "updated_at": "2025-11-05T10:30:00.000000Z"
  },
  {
    "id": 2,
    "name": "Sara Ahmed",
    "email": "sara@example.com",
    "telegram_id": null,
    "webhook_url": null,
    "notify_via": ["email"],
    "active": true,
    "created_at": "2025-11-05T10:35:00.000000Z",
    "updated_at": "2025-11-05T10:35:00.000000Z"
  }
]
```

---

#### 1.2 POST `/api/v1/backup/admins`
**الوصف:** إضافة أدمن جديد

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Ahmad Ali",
  "email": "ahmad@example.com,ahmad2@example.com",
  "telegram_id": "123456789,987654321",
  "webhook_url": "https://webhook.site/test1,https://webhook.site/test2",
  "notify_via": ["email", "telegram", "webhook"],
  "active": true
}
```

**Response:** `201 Created`
```json
{
  "id": 1,
  "name": "Ahmad Ali",
  "email": "ahmad@example.com,ahmad2@example.com",
  "telegram_id": "123456789,987654321",
  "webhook_url": "https://webhook.site/test1,https://webhook.site/test2",
  "notify_via": ["email", "telegram", "webhook"],
  "active": true,
  "created_at": "2025-11-05T10:30:00.000000Z",
  "updated_at": "2025-11-05T10:30:00.000000Z"
}
```

**Error Response:** `422 Unprocessable Entity`
```json
{
  "message": "The name field is required.",
  "errors": {
    "name": ["The name field is required."],
    "email": ["The email field is required when notify via contains email."]
  }
}
```

---

#### 1.3 PUT `/api/v1/backup/admins/{id}`
**الوصف:** تعديل بيانات أدمن موجود

**Headers:**
```
Content-Type: application/json
Accept: application/json
```

**Request Body:**
```json
{
  "name": "Ahmad Ali Updated",
  "email": "ahmad_new@example.com",
  "telegram_id": "123456789",
  "webhook_url": "https://webhook.site/new",
  "notify_via": ["email", "telegram"],
  "active": false
}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "name": "Ahmad Ali Updated",
  "email": "ahmad_new@example.com",
  "telegram_id": "123456789",
  "webhook_url": "https://webhook.site/new",
  "notify_via": ["email", "telegram"],
  "active": false,
  "created_at": "2025-11-05T10:30:00.000000Z",
  "updated_at": "2025-11-05T11:45:00.000000Z"
}
```

**Error Response:** `404 Not Found`
```json
{
  "message": "Admin not found"
}
```

---

#### 1.4 DELETE `/api/v1/backup/admins/{id}`
**الوصف:** حذف أدمن

**Headers:**
```
Accept: application/json
```

**Response:** `204 No Content`

**Error Response:** `404 Not Found`
```json
{
  "message": "Admin not found"
}
```

---

### 2. Settings (Notifications)

#### 2.1 GET `/api/v1/backup/settings`
**الوصف:** جلب جميع الإعدادات (بما فيها إعدادات الإشعارات)

**Response:** `200 OK`
```json
{
  "id": 1,
  
  "cron": "0 2 * * *",
  "timezone": "Asia/Baghdad",

  "notify_enabled": true,
  "notify_on": "both",

  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,

  "emails": "default1@example.com,default2@example.com",
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "111111111,222222222",
  "webhook_urls": "https://webhook.site/default1,https://webhook.site/default2",
  "webhook_secret": "my-secret-key",

  "created_at": "2025-11-05T10:00:00.000000Z",
  "updated_at": "2025-11-05T11:00:00.000000Z"
}
```

---

#### 2.2 PUT `/api/v1/backup/settings`
**الوصف:** تحديث الإعدادات

**Request Body (الحقول المتعلقة بالإشعارات فقط):**
```json
{
  "notify_enabled": true,
  "notify_on": "both",

  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,

  "emails": "default1@example.com,default2@example.com,default3@example.com",
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "111111111,222222222,333333333",
  "webhook_urls": "https://webhook.site/url1,https://webhook.site/url2",
  "webhook_secret": "new-secret-key"
}
```

**Response:** `200 OK`
(نفس الـ Response الخاص بـ GET)

---

## 📝 Data Structures

### Admin Object

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | - | معرّف الأدمن (Auto-increment) |
| `name` | string | ✅ Yes | اسم الأدمن (max: 100) |
| `email` | string | Conditional | Email أو عدة emails مفصولة بفاصلة (max: 500) |
| `telegram_id` | string | Conditional | Telegram Chat ID أو عدة IDs مفصولة بفاصلة (max: 200) |
| `webhook_url` | string | Conditional | Webhook URL أو عدة URLs مفصولة بفاصلة (max: 1000) |
| `notify_via` | array | ✅ Yes | قنوات الإشعار: `["email", "telegram", "webhook"]` |
| `active` | boolean | No | فعّال أم لا (default: true) |
| `created_at` | string (ISO 8601) | - | تاريخ الإنشاء |
| `updated_at` | string (ISO 8601) | - | تاريخ آخر تعديل |

**ملاحظات:**
- `email` مطلوب فقط إذا كان `"email"` موجود في `notify_via`
- `telegram_id` مطلوب فقط إذا كان `"telegram"` موجود في `notify_via`
- `webhook_url` مطلوب فقط إذا كان `"webhook"` موجود في `notify_via`
- يجب اختيار قناة واحدة على الأقل في `notify_via`

---

### Settings Object (الحقول المتعلقة بالإشعارات)

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `notify_enabled` | boolean | No | تفعيل/تعطيل نظام الإشعارات (Master toggle) |
| `notify_on` | string | No | متى نرسل: `"success"`, `"failure"`, `"both"` |
| `telegram_enabled` | boolean | No | تفعيل/تعطيل قناة Telegram |
| `email_enabled` | boolean | No | تفعيل/تعطيل قناة Email |
| `webhook_enabled` | boolean | No | تفعيل/تعطيل قناة Webhook |
| `emails` | string | No | Emails افتراضية مفصولة بفاصلة |
| `telegram_bot_token` | string | No | توكن بوت Telegram |
| `telegram_chat_ids` | string | No | Chat IDs افتراضية مفصولة بفاصلة |
| `webhook_urls` | string | No | Webhook URLs افتراضية مفصولة بفاصلة |
| `webhook_secret` | string | No | مفتاح سري للـ Webhook (اختياري) |

---

## ✅ Validation Rules

### Admin Validation

```
name:
  - required
  - string
  - max: 100

email:
  - nullable
  - string
  - max: 500
  - required_if: notify_via contains "email"

telegram_id:
  - nullable
  - string
  - max: 200
  - required_if: notify_via contains "telegram"

webhook_url:
  - nullable
  - string
  - max: 1000
  - required_if: notify_via contains "webhook"

notify_via:
  - required
  - array
  - min: 1 (يجب اختيار قناة واحدة على الأقل)
  - values: must be in ["email", "telegram", "webhook"]

active:
  - boolean
  - default: true
```

---

### Settings Validation (الإشعارات)

```
notify_enabled:
  - boolean

notify_on:
  - in: ["success", "failure", "both"]

telegram_enabled:
  - boolean

email_enabled:
  - boolean

webhook_enabled:
  - boolean

emails:
  - nullable
  - string

telegram_bot_token:
  - nullable
  - string

telegram_chat_ids:
  - nullable
  - string

webhook_urls:
  - nullable
  - string

webhook_secret:
  - nullable
  - string
```

---

## 🔄 آلية العمل

### 1. جمع المستلمين

عند إرسال الإشعارات، النظام يجمع المستلمين كالتالي:

**Emails:**
```
1. من backup_admins:
   - جلب جميع الأدمنز الفعّالين (active = true)
   - فلترة من لديهم "email" في notify_via
   - تقسيم حقل email بالفاصلة
   - مثال: "admin1@test.com,admin2@test.com" → ["admin1@test.com", "admin2@test.com"]

2. من backup_settings:
   - جلب حقل emails
   - تقسيمه بالفاصلة
   - مثال: "default1@example.com,default2@example.com" → ["default1@example.com", "default2@example.com"]

3. دمج وإزالة التكرار:
   - دمج القائمتين
   - إزالة التكرار (unique)
   - إزالة القيم الفارغة
```

**نفس الآلية تُطبق على:**
- Telegram Chat IDs
- Webhook URLs

---

### 2. التحقق من التفعيل

قبل إرسال الإشعارات، النظام يتحقق من:

```
1. notify_enabled = true (Master toggle)

2. notify_on:
   - إذا كان "success" → يرسل فقط عند النجاح
   - إذا كان "failure" → يرسل فقط عند الفشل
   - إذا كان "both" → يرسل في الحالتين

3. Channel-specific toggles:
   - telegram_enabled = true → يرسل عبر Telegram
   - email_enabled = true → يرسل عبر Email
   - webhook_enabled = true → يرسل عبر Webhook
```

---

### 3. مثال عملي

**الحالة:**
```
backup_admins:
  - Admin 1: email = "admin1@test.com,admin2@test.com", notify_via = ["email"]
  - Admin 2: email = "sara@test.com", notify_via = ["email"]

backup_settings:
  - emails = "default1@example.com,admin1@test.com"
  - email_enabled = true
```

**النتيجة:**
```
سيتم إرسال الإشعارات إلى:
1. admin1@test.com (من Admin 1)
2. admin2@test.com (من Admin 1)
3. sara@test.com (من Admin 2)
4. default1@example.com (من settings)

ملاحظة: admin1@test.com موجود في المصدرين، لكن سيُرسل له إشعار واحد فقط (بسبب unique)
```

---

## 📋 أمثلة الاستخدام

### مثال 1: إضافة أدمن بقيمة واحدة لكل حقل

**Request:**
```http
POST /api/v1/backup/admins
Content-Type: application/json

{
  "name": "Ali Ahmed",
  "email": "ali@example.com",
  "telegram_id": "123456789",
  "webhook_url": null,
  "notify_via": ["email", "telegram"],
  "active": true
}
```

**Response:**
```json
{
  "id": 3,
  "name": "Ali Ahmed",
  "email": "ali@example.com",
  "telegram_id": "123456789",
  "webhook_url": null,
  "notify_via": ["email", "telegram"],
  "active": true,
  "created_at": "2025-11-05T12:00:00.000000Z",
  "updated_at": "2025-11-05T12:00:00.000000Z"
}
```

---

### مثال 2: إضافة أدمن بقيم متعددة

**Request:**
```http
POST /api/v1/backup/admins
Content-Type: application/json

{
  "name": "Multi Channel Admin",
  "email": "admin1@test.com, admin2@test.com, admin3@test.com",
  "telegram_id": "111111111, 222222222",
  "webhook_url": "https://webhook.site/url1, https://webhook.site/url2",
  "notify_via": ["email", "telegram", "webhook"],
  "active": true
}
```

**Response:**
```json
{
  "id": 4,
  "name": "Multi Channel Admin",
  "email": "admin1@test.com, admin2@test.com, admin3@test.com",
  "telegram_id": "111111111, 222222222",
  "webhook_url": "https://webhook.site/url1, https://webhook.site/url2",
  "notify_via": ["email", "telegram", "webhook"],
  "active": true,
  "created_at": "2025-11-05T12:05:00.000000Z",
  "updated_at": "2025-11-05T12:05:00.000000Z"
}
```

**ملاحظة:** المسافات قبل/بعد الفاصلة يتم تجاهلها تلقائياً من قبل الـ Backend.

---

### مثال 3: تحديث إعدادات الإشعارات

**Request:**
```http
PUT /api/v1/backup/settings
Content-Type: application/json

{
  "notify_enabled": true,
  "notify_on": "both",

  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,

  "emails": "support@company.com, admin@company.com",
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "123456789, 987654321"
}
```

---

### مثال 4: Error - حقل مطلوب

**Request:**
```http
POST /api/v1/backup/admins
Content-Type: application/json

{
  "name": "Test Admin",
  "telegram_id": "123456789",
  "notify_via": ["email"],
  "active": true
}
```

**Response:** `422 Unprocessable Entity`
```json
{
  "message": "The email field is required when notify via contains email.",
  "errors": {
    "email": [
      "The email field is required when notify via contains email."
    ]
  }
}
```

**السبب:** تم اختيار `"email"` في `notify_via` لكن حقل `email` فارغ.

---

### مثال 5: Error - لم يتم اختيار أي قناة

**Request:**
```http
POST /api/v1/backup/admins
Content-Type: application/json

{
  "name": "Test Admin",
  "email": "test@example.com",
  "notify_via": [],
  "active": true
}
```

**Response:** `422 Unprocessable Entity`
```json
{
  "message": "The notify via field must have at least 1 items.",
  "errors": {
    "notify_via": [
      "The notify via field must have at least 1 items."
    ]
  }
}
```

---

## 🎯 النقاط المهمة

### 1. دعم القيم المتعددة
- جميع الحقول (email, telegram_id, webhook_url) تدعم قيم متعددة
- الفصل يكون بفاصلة `,`
- المسافات قبل/بعد الفاصلة يتم تجاهلها تلقائياً
- أمثلة صحيحة:
  - `"admin1@test.com,admin2@test.com"`
  - `"admin1@test.com, admin2@test.com"`
  - `"admin1@test.com , admin2@test.com , admin3@test.com"`

### 2. Conditional Validation
- `email` مطلوب فقط إذا كان `"email"` في `notify_via`
- `telegram_id` مطلوب فقط إذا كان `"telegram"` في `notify_via`
- `webhook_url` مطلوب فقط إذا كان `"webhook"` في `notify_via`

### 3. Master Toggle
- `notify_enabled` في Settings هو المفتاح الرئيسي
- إذا كان `false`، لن يتم إرسال أي إشعارات حتى لو كانت القنوات مفعّلة

### 4. Channel Toggles
- كل قناة لها toggle منفصل: `telegram_enabled`, `email_enabled`, `webhook_enabled`
- يمكن تفعيل قناة واحدة، أكثر من قناة، أو جميع القنوات

### 5. إزالة التكرار
- النظام يزيل التكرار تلقائياً عند جمع المستلمين
- إذا كان نفس Email موجود في admins و settings، يُرسل له إشعار واحد فقط

---

## 📞 للاستفسارات

إذا كان لديك أي استفسار حول:
- هيكل البيانات
- Validation Rules
- آلية العمل
- الـ Responses المتوقعة

يمكنك مراجعة هذا الملف أو اختبار الـ APIs مباشرة.

---

**تاريخ الإنشاء:** 2025-11-05
**النسخة:** 1.0
**آخر تحديث:** 2025-11-05
