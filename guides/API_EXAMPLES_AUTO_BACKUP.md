# أمثلة API حقيقية - النسخ الاحتياطي التلقائي

## 🧪 أمثلة للاختبار في Postman/Insomnia

---

## 1️⃣ جلب الإعدادات الحالية

### Request:
```http
GET /api/backup/settings
Accept: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

### Response الحالي:
```json
{
  "id": 1,
  
  "auto_backup_enabled": false,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both",
  "last_auto_backup_at": null,
  "cron": "0 15 * * *",
  "timezone": "Asia/Baghdad",
  "max_storage_mb": 50000,
  "include_files": true,
  "include_paths": null,
  "exclude_paths": null,
  "multi_db": false,
  "selected_databases": null,
  "keep_daily_days": 7,
  "keep_weekly_weeks": 4,
  "keep_monthly_months": 6,
  "keep_yearly_years": 10,
  "disk": "local",
  "drive_folder": "Backups",
  "temp_link_expiry": 60,
  "checksum_enabled": true,
  "notify_enabled": true,
  "notify_on": "both",
  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,
  "emails": null,
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "563390643,1606627929",
  "webhook_urls": "https://webhook.site/default",
  "webhook_secret": null,
  "stale_hours": 48,
  "last_run_at": "2025-11-05T07:33:12.000000Z",
  "created_at": "2025-10-23T10:00:23.000000Z",
  "updated_at": "2025-11-06T05:37:39.000000Z"
}
```

---

## 2️⃣ تفعيل النسخ التلقائي (كل يوم)

### Request:
```http
POST /api/backup/settings
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

### Body:
```json
{
  
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both",
  "cron": "0 15 * * *",
  "timezone": "Asia/Baghdad",
  "max_storage_mb": 50000,
  "include_files": true,
  "multi_db": false,
  "keep_daily_days": 7,
  "keep_weekly_weeks": 4,
  "keep_monthly_months": 6,
  "keep_yearly_years": 10,
  "disk": "local",
  "drive_folder": "Backups",
  "temp_link_expiry": 60,
  "checksum_enabled": true,
  "notify_enabled": true,
  "notify_on": "both",
  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "563390643,1606627929",
  "webhook_urls": "https://webhook.site/default",
  "stale_hours": 48
}
```

### Response Success:
```json
{
  "id": 1,
  
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both",
  "last_auto_backup_at": null,
  "cron": "0 15 * * *",
  "timezone": "Asia/Baghdad",
  "max_storage_mb": 50000,
  "include_files": true,
  "include_paths": null,
  "exclude_paths": null,
  "multi_db": false,
  "selected_databases": null,
  "keep_daily_days": 7,
  "keep_weekly_weeks": 4,
  "keep_monthly_months": 6,
  "keep_yearly_years": 10,
  "disk": "local",
  "drive_folder": "Backups",
  "temp_link_expiry": 60,
  "checksum_enabled": true,
  "notify_enabled": true,
  "notify_on": "both",
  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,
  "emails": null,
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "563390643,1606627929",
  "webhook_urls": "https://webhook.site/default",
  "webhook_secret": null,
  "stale_hours": 48,
  "last_run_at": "2025-11-05T07:33:12.000000Z",
  "created_at": "2025-10-23T10:00:23.000000Z",
  "updated_at": "2025-11-06T06:15:22.000000Z"
}
```

---

## 3️⃣ نسخ قاعدة البيانات فقط كل 6 ساعات

### Request:
```http
POST /api/backup/settings
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

### Body (الحقول الجديدة فقط):
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 360,
  "auto_backup_type": "db"
}
```

**ملاحظة:** يمكنك إرسال الحقول الجديدة فقط، لكن يُفضل إرسال جميع الحقول.

---

## 4️⃣ نسخ الملفات فقط كل أسبوع

### Body:
```json
{
  
  "auto_backup_enabled": true,
  "auto_backup_interval": 10080,
  "auto_backup_type": "files",

  "cron": "0 15 * * *",
  "timezone": "Asia/Baghdad",
  "max_storage_mb": 50000,
  "include_files": true,
  "multi_db": false,
  "keep_daily_days": 7,
  "keep_weekly_weeks": 4,
  "keep_monthly_months": 6,
  "keep_yearly_years": 10,
  "disk": "local",
  "drive_folder": "Backups",
  "temp_link_expiry": 60,
  "checksum_enabled": true,
  "notify_enabled": true,
  "notify_on": "both",
  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "563390643,1606627929",
  "webhook_urls": "https://webhook.site/default",
  "stale_hours": 48
}
```

---

## 5️⃣ إيقاف النسخ التلقائي

### Body:
```json
{
  
  "auto_backup_enabled": false,

  "cron": "0 15 * * *",
  "timezone": "Asia/Baghdad",
  "max_storage_mb": 50000,
  "include_files": true,
  "multi_db": false,
  "keep_daily_days": 7,
  "keep_weekly_weeks": 4,
  "keep_monthly_months": 6,
  "keep_yearly_years": 10,
  "disk": "local",
  "drive_folder": "Backups",
  "temp_link_expiry": 60,
  "checksum_enabled": true,
  "notify_enabled": true,
  "notify_on": "both",
  "telegram_enabled": true,
  "email_enabled": true,
  "webhook_enabled": false,
  "telegram_bot_token": "7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8",
  "telegram_chat_ids": "563390643,1606627929",
  "webhook_urls": "https://webhook.site/default",
  "stale_hours": 48
}
```

---

## 6️⃣ أخطاء محتملة

### خطأ: interval أقل من 1

**Request:**
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 0,
  "auto_backup_type": "both"
}
```

**Response Error (422):**
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

---

### خطأ: نوع غير صحيح

**Request:**
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "invalid_type"
}
```

**Response Error (422):**
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

## 7️⃣ تحديث جزئي (Partial Update)

يمكنك إرسال الحقول الجديدة فقط بدون باقي الحقول:

### Request:
```http
POST /api/backup/settings
Content-Type: application/json
Authorization: Bearer YOUR_TOKEN_HERE
```

### Body (حقول جديدة فقط):
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 720,
  "auto_backup_type": "both"
}
```

**ملاحظة:** هذا يعمل، لكن يُفضل إرسال جميع الحقول للتأكد.

---

## 📌 جدول القيم الشائعة

### الفترات الزمنية (بالدقائق):

| الوصف | القيمة |
|-------|--------|
| كل ساعة | 60 |
| كل 3 ساعات | 180 |
| كل 6 ساعات | 360 |
| كل 12 ساعة | 720 |
| يومياً | 1440 |
| كل 3 أيام | 4320 |
| أسبوعياً | 10080 |
| كل أسبوعين | 20160 |
| شهرياً (30 يوم) | 43200 |

### أنواع النسخ:

| النوع | القيمة | الوصف |
|-------|--------|-------|
| قاعدة البيانات فقط | `"db"` | نسخ MySQL فقط |
| الملفات فقط | `"files"` | نسخ storage/app/public فقط |
| الكامل | `"both"` | نسخ قاعدة البيانات + الملفات |

---

## 🔧 Testing Workflow

### السيناريو الكامل:

#### 1. جلب الإعدادات الحالية
```bash
curl -X GET http://your-domain.com/api/backup/settings \
  -H "Authorization: Bearer YOUR_TOKEN"
```

#### 2. تفعيل النسخ التلقائي
```bash
curl -X POST http://your-domain.com/api/backup/settings \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{
    
    "auto_backup_enabled": true,
    "auto_backup_interval": 1440,
    "auto_backup_type": "both",
    "cron": "0 15 * * *",
    "timezone": "Asia/Baghdad",
    "max_storage_mb": 50000,
    "include_files": true,
    "multi_db": false,
    "keep_daily_days": 7,
    "keep_weekly_weeks": 4,
    "keep_monthly_months": 6,
    "keep_yearly_years": 10,
    "disk": "local",
    "drive_folder": "Backups",
    "temp_link_expiry": 60,
    "checksum_enabled": true,
    "notify_enabled": true,
    "notify_on": "both",
    "telegram_enabled": true,
    "email_enabled": true,
    "webhook_enabled": false,
    "telegram_bot_token": "YOUR_TOKEN",
    "telegram_chat_ids": "YOUR_CHAT_IDS",
    "webhook_urls": "https://webhook.site/your-id",
    "stale_hours": 48
  }'
```

#### 3. التحقق من الحفظ
```bash
curl -X GET http://your-domain.com/api/backup/settings \
  -H "Authorization: Bearer YOUR_TOKEN"
```

**تحقق من:**
- `auto_backup_enabled`: يجب أن يكون `true`
- `auto_backup_interval`: يجب أن يكون `1440`
- `auto_backup_type`: يجب أن يكون `"both"`

---

## 💡 نصائح للمطور

### 1. استخدم Postman Collection

يمكنك إنشاء Collection في Postman يحتوي على:
- **GET Settings** - للجلب
- **Update Settings - Daily** - تفعيل يومي
- **Update Settings - Hourly** - تفعيل كل ساعة
- **Update Settings - Disable** - إيقاف

### 2. Environment Variables

أنشئ Environment في Postman:
```json
{
  "base_url": "http://your-domain.com",
  "token": "your_auth_token"
}
```

### 3. Pre-request Script

للحصول على Token تلقائياً:
```javascript
// في الـ Collection Pre-request Script
pm.environment.set("token", "your_token_here");
```

### 4. Tests Script

للتحقق من النجاح:
```javascript
// في الـ Tests tab
pm.test("Status is 200", function() {
  pm.response.to.have.status(200);
});

pm.test("Auto backup is enabled", function() {
  const data = pm.response.json();
  pm.expect(data.auto_backup_enabled).to.be.true;
});
```

---

## 🎯 خلاصة سريعة

**للمطور الفرونت:**

1. استخدم `GET /api/backup/settings` لجلب البيانات
2. اعرض القيم في Form
3. عند الحفظ، أرسل `POST /api/backup/settings` مع الحقول الجديدة
4. **لا ترسل** `last_auto_backup_at`
5. تأكد من أن `auto_backup_interval` رقم صحيح (Integer)
6. تأكد من أن `auto_backup_type` أحد القيم: `"db"`, `"files"`, `"both"`

**هذا كل شيء! 🎉**
