# 📚 Backend API Documentation

هذا المجلد يحتوي على توثيق شامل لـ APIs الخاصة بنظام النسخ الاحتياطي.

## 📄 الملفات المتاحة:

### 1. [BACKUP_FRONTEND_PLAN.md](./BACKUP_FRONTEND_PLAN.md)
**الخطة الشاملة للـ Backend APIs**

يحتوي على:
- نظرة عامة على النظام
- جميع API Endpoints
- هيكل البيانات
- أمثلة Request/Response
- Changelog

---

### 2. [ADMINS_NOTIFICATIONS_API_DOCS.md](./ADMINS_NOTIFICATIONS_API_DOCS.md) ⭐
**توثيق APIs الخاصة بإدارة الأدمنز والإشعارات**

يحتوي على:
- ✅ شرح مفصل لآلية العمل
- ✅ جميع API Endpoints مع أمثلة Request/Response
- ✅ Data Structures كاملة
- ✅ Validation Rules (Backend)
- ✅ أمثلة على جميع الحالات (نجاح، فشل، errors)
- ✅ شرح آلية جمع المستلمين من المصدرين
- ✅ أمثلة عملية

**الميزات الخاصة:**
- دعم القيم المتعددة المفصولة بفاصلة
- Conditional Validation
- Master Toggle و Channel Toggles
- إزالة التكرار التلقائي

---

## 🎯 ملخص النظام

### نظام إدارة الأدمنز:
- إضافة/تعديل/حذف المسؤولين عن استلام الإشعارات
- دعم **القيم المتعددة المفصولة بفاصلة**
  - مثال: `"admin1@test.com,admin2@test.com,admin3@test.com"`
- كل أدمن يحتوي على:
  - `email` (قد يكون متعدد)
  - `telegram_id` (قد يكون متعدد)
  - `webhook_url` (قد يكون متعدد)
  - `notify_via` (array من القنوات: email, telegram, webhook)
  - `active` (boolean - فعّال أم لا)

### نظام الإشعارات:
- **مصدرين للمستلمين:**
  1. `backup_admins` - الأدمنز المضافين يدوياً
  2. `backup_settings` - القيم الافتراضية (defaults)

- **التحكم بالقنوات:**
  - `notify_enabled` - Master Toggle (تفعيل/تعطيل جميع الإشعارات)
  - `telegram_enabled` - تفعيل/تعطيل قناة Telegram
  - `email_enabled` - تفعيل/تعطيل قناة Email
  - `webhook_enabled` - تفعيل/تعطيل قناة Webhook

- **إزالة التكرار:**
  - النظام يجمع المستلمين من المصدرين
  - يزيل التكرار تلقائياً
  - كل شخص يستلم إشعار واحد فقط

---

## 📋 API Endpoints السريعة

### Admins:
```
GET    /api/v1/backup/admins        جلب قائمة الأدمنز
POST   /api/v1/backup/admins        إضافة أدمن جديد
PUT    /api/v1/backup/admins/{id}   تعديل أدمن
DELETE /api/v1/backup/admins/{id}   حذف أدمن
```

### Settings:
```
GET    /api/v1/backup/settings      جلب الإعدادات
PUT    /api/v1/backup/settings      تحديث الإعدادات
```

### Logs:
```
GET    /api/v1/backup/logs          جلب سجلات النسخ الاحتياطي
```

---

## 🔑 النقاط المهمة

### 1. دعم القيم المتعددة:
جميع الحقول (email, telegram_id, webhook_url) تدعم قيم متعددة مفصولة بفاصلة:

```
✅ صحيح: "admin1@test.com,admin2@test.com"
✅ صحيح: "admin1@test.com, admin2@test.com"
✅ صحيح: "admin@test.com"
```

### 2. Conditional Validation:
- `email` مطلوب فقط إذا كان `"email"` في `notify_via`
- `telegram_id` مطلوب فقط إذا كان `"telegram"` في `notify_via`
- `webhook_url` مطلوب فقط إذا كان `"webhook"` في `notify_via`

### 3. Master Toggle:
- `notify_enabled` في Settings هو المفتاح الرئيسي
- إذا كان `false`، لن يتم إرسال أي إشعارات

### 4. Channel Toggles:
كل قناة لها toggle منفصل في Settings:
- `telegram_enabled`
- `email_enabled`
- `webhook_enabled`

---

## 📖 للبدء

1. اقرأ [ADMINS_NOTIFICATIONS_API_DOCS.md](./ADMINS_NOTIFICATIONS_API_DOCS.md) كاملاً
2. راجع Data Structures
3. راجع Validation Rules
4. راجع الأمثلة العملية
5. ابدأ بتطوير الواجهات حسب تصميمك الخاص

---

**ملاحظة مهمة:**
هذه الملفات تحتوي على **توثيق Backend فقط**. التصميم والكود الخاص بالـ Frontend متروك لك بالكامل حسب تصميمك الخاص.

---

**آخر تحديث:** 2025-11-05
