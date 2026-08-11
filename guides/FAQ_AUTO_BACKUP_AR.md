# أسئلة شائعة - النسخ الاحتياطي التلقائي

## 🤔 للفرونت إند

---

### س1: شنو الحقول الجديدة الي لازم اضيفها؟

**ج:** 3 حقول فقط:

1. **auto_backup_enabled** - تفعيل/إيقاف (Toggle/Checkbox)
2. **auto_backup_interval** - الفترة الزمنية بالدقائق (Number Input)
3. **auto_backup_type** - نوع النسخة (Select/Radio: db/files/both)

---

### س2: الفترة الزمنية بشنو؟ ساعات ولا دقايق؟

**ج:** **دقايق** فقط!

- 60 = كل ساعة
- 1440 = كل يوم
- 10080 = كل أسبوع

**مهم:** حتى لو تعرض للمستخدم "كل يوم"، لازم ترسل `1440` (بالدقائق).

---

### س3: شلون اسوي Input للفترة الزمنية؟

**ج:** عندك 3 خيارات:

#### الخيار 1: Input بسيط
```
الفترة (بالدقائق): [_____]
```
المستخدم يكتب رقم مباشر.

#### الخيار 2: Select جاهز
```
[ ] كل ساعة (60)
[ ] يومياً (1440)
[ ] أسبوعياً (10080)
```

#### الخيار 3: Composite (الأحسن)
```
[___2___] [أيام ▼]
```
أنت تحسب: 2 × 1440 = 2880 دقيقة

---

### س4: شنو القيم الصحيحة لـ auto_backup_type؟

**ج:** 3 قيم فقط (strings):

- `"db"` - قاعدة البيانات فقط
- `"files"` - الملفات فقط
- `"both"` - الكامل (قاعدة بيانات + ملفات)

**مثال:**
```json
{
  "auto_backup_type": "both"
}
```

---

### س5: شنو last_auto_backup_at؟ لازم ارسله؟

**ج:** **لا ترسله أبداً!**

- هذا حقل **للقراءة فقط**
- السيرفر يحدثه تلقائياً
- فقط اعرضه في الـ UI (اختياري)

---

### س6: شلون اعرض last_auto_backup_at؟

**ج:** حوّله لـ "منذ X":

```javascript
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

### س7: الـ API شنو؟

**ج:**

**للجلب:**
```
GET /api/backup/settings
```

**للحفظ:**
```
POST /api/backup/settings
```

نفس الـ Endpoints القديمة، فقط أضف الحقول الجديدة.

---

### س8: شنو الـ Body الي لازم ارسله؟

**ج:** مثال كامل:

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
  "telegram_bot_token": "YOUR_TOKEN",
  "telegram_chat_ids": "YOUR_IDS",
  "webhook_urls": "YOUR_URL",
  "stale_hours": 48
}
```

**ملاحظة:** يفضل ترسل كل الحقول، مو بس الجديدة.

---

### س9: اكدر ارسل الحقول الجديدة فقط؟

**ج:** نعم، يشتغل:

```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both"
}
```

لكن يُفضل ترسل كل الحقول للتأكد.

---

### س10: شلون اختبر؟

**ج:**

1. افتح Postman
2. أرسل `GET /api/backup/settings`
3. خذ الـ Response وعدّل عليه
4. أرسل `POST /api/backup/settings` مع التعديلات
5. أرسل `GET` مرة ثانية وتأكد من الحفظ

---

### س11: البيانات ما تحفظ، شنو المشكلة؟

**ج:** تحقق من:

1. الأسماء صحيحة؟
   - `auto_backup_enabled` (مو `autoBackupEnabled`)
   - `auto_backup_interval` (مو `interval`)
   - `auto_backup_type` (مو `type`)

2. الأنواع صحيحة؟
   - `auto_backup_enabled`: boolean (true/false)
   - `auto_backup_interval`: number (مو string)
   - `auto_backup_type`: string ("db"/"files"/"both")

3. شوف الـ Network Tab في Developer Tools

---

### س12: احصل error validation، ليش؟

**ج:** أخطاء شائعة:

**الخطأ:** "must be at least 1"
```json
// خطأ:
"auto_backup_interval": 0

// صح:
"auto_backup_interval": 1
```

**الخطأ:** "invalid auto backup type"
```json
// خطأ:
"auto_backup_type": "database"

// صح:
"auto_backup_type": "db"
```

---

### س13: شلون اعرف النظام شغال؟

**ج:** بعد ما تفعّل النسخ التلقائي:

1. بعد الفترة المحددة، راح يتحدث `last_auto_backup_at`
2. راح تلاحظ نسخ جديدة في قائمة النسخ
3. راح تجيك إشعارات (إذا مفعّلة)

---

### س14: متى راح تبدأ النسخة الأولى؟

**ج:**

- إذا `last_auto_backup_at` يساوي `null`: **فوراً** (بعد دقيقة واحدة)
- إذا موجود: بعد مرور `auto_backup_interval` من آخر نسخة

**مثال:**
```
آخر نسخة: 10:00 صباحاً
الفترة: 1440 دقيقة (يوم)
النسخة التالية: 10:00 صباحاً اليوم التالي
```

---

### س15: اكدر اغير الفترة بعد التفعيل؟

**ج:** نعم، بس:

- التغيير يبدأ من **آخر نسخة**
- لو تريد نسخة فورية، استخدم "Run Now" (النسخ اليدوي)

---

### س16: شنو الفرق بين النسخ التلقائي واليدوي؟

**ج:**

| الميزة | تلقائي | يدوي |
|--------|---------|------|
| التشغيل | تلقائي حسب الفترة | يدوي بالضغط |
| النوع في Logs | `auto` | `manual` |
| يؤثر على `last_run_at` | لا | نعم |
| يؤثر على `last_auto_backup_at` | نعم | لا |

---

### س17: النسخ التلقائي يرسل إشعارات؟

**ج:** نعم، نفس النسخ اليدوي:

- يحترم إعدادات الإشعارات (`notify_enabled`)
- يرسل على القنوات المفعّلة (Telegram/Email/Webhook)
- يرسل لكل المستلمين (من الإعدادات + الأدمنز)

---

### س18: اكدر اطفي الإشعارات للنسخ التلقائي بس؟

**ج:** لا، الإشعارات موحدة:

- إذا تريد إشعارات، راح تجي للنسخ التلقائي واليدوي
- إذا ما تريد، طفيها من `notify_enabled: false`

---

### س19: شنو لو صار خطأ في النسخ التلقائي؟

**ج:**

1. راح يتسجل في `backup_logs` بحالة `failed`
2. راح يرسل إشعار فشل (إذا مفعّل)
3. النظام راح يحاول مرة ثانية في الموعد التالي

---

### س20: اكدر اختبر بدون ما انتظر؟

**ج:** نعم:

**في Postman:**
```json
{
  "auto_backup_enabled": true,
  "auto_backup_interval": 1,
  "auto_backup_type": "both"
}
```
احفظ، انتظر دقيقة وحدة، شوف `last_auto_backup_at` تحدث.

---

## 🎨 أسئلة عن التصميم

---

### س21: وين احط الحقول الجديدة؟

**ج:** في صفحة إعدادات النسخ الاحتياطي، خيارات:

1. **قسم منفصل** (Accordion): "النسخ التلقائي"
2. **ضمن الإعدادات الموجودة**: بعد الإعدادات العامة
3. **تبويب منفصل**: تبويب "Auto Backup"

التصميم على راحتك.

---

### س22: شلون اصمم Input الفترة الزمنية؟

**ج:** أحسن طريقة:

```
الفترة الزمنية:
[___] [Dropdown: دقائق / ساعات / أيام / أسابيع]
```

**مثال:**
```
المستخدم يكتب: 2 أيام
أنت تحسب: 2 × 1440 = 2880
ترسل: { "auto_backup_interval": 2880 }
```

---

### س23: شنو Labels الأحسن؟

**ج:**

**بالعربي:**
- تفعيل النسخ التلقائي
- الفترة الزمنية
- نوع النسخة الاحتياطية

**بالإنجليزي:**
- Enable Auto Backup
- Backup Interval
- Backup Type

---

### س24: لازم اعرض last_auto_backup_at؟

**ج:** لا، اختياري، بس يساعد المستخدم:

```
آخر نسخة تلقائية: منذ 3 ساعات
النسخة التالية: بعد 21 ساعة
```

---

### س25: شلون احسب "النسخة التالية"؟

**ج:**

```javascript
function getNextBackupTime(lastAutoBackupAt, interval) {
  if (!lastAutoBackupAt) return 'قريباً';

  const last = new Date(lastAutoBackupAt);
  const next = new Date(last.getTime() + interval * 60 * 1000);
  const now = new Date();

  if (next <= now) return 'قريباً';

  const diffMs = next - now;
  const diffMins = Math.floor(diffMs / 60000);

  if (diffMins < 60) return `بعد ${diffMins} دقيقة`;
  if (diffMins < 1440) return `بعد ${Math.floor(diffMins / 60)} ساعة`;
  return `بعد ${Math.floor(diffMins / 1440)} يوم`;
}
```

---

## 🛠️ أسئلة تقنية

---

### س26: Boolean يجي string من الـ Frontend؟

**ج:** ما عليك، السيرفر يتعامل مع الحالتين:

```json
// كلاهما يشتغل:
"auto_backup_enabled": true
"auto_backup_enabled": "true"
```

---

### س27: لازم ارسل Authorization؟

**ج:** نعم، مثل باقي الـ APIs:

```javascript
headers: {
  'Authorization': 'Bearer YOUR_TOKEN',
  'Content-Type': 'application/json'
}
```

---

### س28: Response شنو يرجع؟

**ج:** نفس الـ object الكامل بعد التحديث:

```json
{
  "id": 1,
  "auto_backup_enabled": true,
  "auto_backup_interval": 1440,
  "auto_backup_type": "both",
  "last_auto_backup_at": null,
  // ... باقي الحقول
}
```

---

### س29: في Resource خاص؟

**ج:** لا، الـ API يرجع الـ Model مباشرة.

---

### س30: شلون اختبر بدون Backend؟

**ج:** سوي Mock Data:

```javascript
const mockSettings = {
  id: 1,
  enabled: true,
  auto_backup_enabled: false,
  auto_backup_interval: 1440,
  auto_backup_type: "both",
  last_auto_backup_at: null,
  // ... باقي الحقول
};
```

---

## 📱 للمستخدم النهائي

---

### س31: شنو فايدة النسخ التلقائي؟

**ج:**
- ما تحتاج تدخل كل يوم تضغط "Run Now"
- النظام ياخذ نسخة تلقائياً حسب الفترة الي تحددها
- أأمن، ما تنسى تاخذ نسخة

---

### س32: اكدر اخلي نسخة يومية؟

**ج:** نعم:

```
☑️ تفعيل النسخ التلقائي
الفترة: [1] [يوم]
النوع: قاعدة البيانات + الملفات
```

---

### س33: شنو أحسن فترة؟

**ج:** يعتمد على نشاط الموقع:

- **موقع نشيط جداً**: كل 6-12 ساعة
- **موقع متوسط النشاط**: يومياً
- **موقع قليل النشاط**: أسبوعياً

---

### س34: النسخ التلقائي يستهلك مساحة؟

**ج:** نعم، بس النظام يحذف النسخ القديمة تلقائياً حسب:

- `keep_daily_days`
- `keep_weekly_weeks`
- `keep_monthly_months`

---

### س35: اكدر اطفي النسخ التلقائي وقتياً؟

**ج:** نعم، طفي الـ Toggle:

```
☐ تفعيل النسخ التلقائي
```

احفظ، وراح يتوقف.

---

## ✅ خلاصة سريعة

**للفرونت إند:**

1. أضف **3 حقول** في صفحة الإعدادات
2. استخدم نفس الـ **API الموجود** (`/api/backup/settings`)
3. **لا ترسل** `last_auto_backup_at`
4. تأكد من الأسماء والأنواع **صحيحة**
5. التصميم **على راحتك**

**هذا كل شيء! 🎉**

---

## 📞 محتاج مساعدة؟

راجع الملفات الأخرى:

1. **FRONTEND_AUTO_BACKUP_GUIDE.md** - الدليل الكامل
2. **API_EXAMPLES_AUTO_BACKUP.md** - أمثلة API
3. **AUTO_BACKUP_README.md** - التوثيق التقني
