<div dir="rtl">

# دليل النشر — Hostinger

الخطوات هنا هي نفسها التي نُفّذت فعليًا على استضافة Hostinger المشتركة. نفّذ **أمرًا واحدًا في كل مرة**، وتأكد من نتيجته قبل الانتقال للذي بعده.

> **الأسرار لا تدخل Git أبدًا:** الملف `.env` وكلمات المرور والمفاتيح موجودة على السيرفر فقط.

## بنية المجلدات على السيرفر

<div dir="ltr">

```text
~/domains/<الدومين>/
├── laravel_app/          المشروع كاملًا (مستودع Git مربوط بـ GitHub)
│   ├── .env              إعدادات الإنتاج (خارج Git، صلاحية 600)
│   ├── public/           المجلد الوحيد الظاهر للمتصفح
│   └── storage/app/private/receipts, proofs   المرفقات الخاصة
└── public_html -> laravel_app/public          رابط رمزي (symlink)
~/backups/                النسخ الاحتياطية (خارج مجلد الموقع)
~/.ssh/alraed_github      مفتاح النشر (Deploy Key) الخاص بالمستودع
```

</div>

`public_html` رابط رمزي إلى `laravel_app/public`، فلا يصل المتصفح إلى `.env` ولا إلى ملفات المشروع.

## الإعدادات المطلوبة في `.env`

| المفتاح | القيمة في الإنتاج |
| --- | --- |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` (إلزامي: `true` يكشف تفاصيل داخلية للزوار عند أي خطأ) |
| `APP_URL` | رابط الموقع بـ `https` |
| `DB_CONNECTION` / `DB_*` | `mysql` وبيانات قاعدة Hostinger |
| `SESSION_DRIVER` | `database` |
| `ALRAED_REQUIRE_MFA` | `true` أو بدون قيمة (الافتراضي إلزامي) |
| `ALRAED_WHATSAPP` / `ALRAED_WHATSAPP_DISPLAY` | رقم واتساب الرسمي (اختياري، وله قيمة افتراضية) |

القائمة الكاملة في `.env.example`. بعد أي تعديل على `.env` شغّل:

<div dir="ltr">

```bash
php artisan optimize:clear && php artisan optimize
```

</div>

## ربط السيرفر بـ GitHub (مرة واحدة)

1. أنشئ مفتاحًا خاصًا بالمستودع على السيرفر:
   ```bash
   ssh-keygen -t ed25519 -C "hostinger-al-raed-deploy" -f ~/.ssh/alraed_github -N "" && cat ~/.ssh/alraed_github.pub
   ```
2. أضف المفتاح العام في GitHub من **Settings ← Deploy keys**.
3. اربط المشروع بالمفتاح:
   ```bash
   git -C laravel_app config core.sshCommand "ssh -i $HOME/.ssh/alraed_github -o IdentitiesOnly=yes"
   ```

## تحديث الموقع (كل مرة)

داخل `laravel_app`:

<div dir="ltr">

```bash
php artisan down --retry=60
git pull --ff-only origin main
composer install --no-dev --optimize-autoloader --no-interaction   # فقط إذا تغيّر composer.lock
php artisan migrate --force                                       # فقط إذا وُجدت migrations جديدة
php artisan optimize:clear && php artisan optimize && php artisan filament:optimize
php artisan up
```

</div>

- `--ff-only` يرفض السحب إن وُجدت تعديلات محلية على السيرفر، فلا يحدث دمج غير متوقع.
- ملفات CSS المبنية موجودة في `public/build` داخل المستودع، فلا يحتاج السيرفر إلى Node.
- `RolesAndPermissionsSeeder` آمن عند تكرار تشغيله: يضيف الصلاحيات الجديدة فقط، ولا يمسح تعديلات المدير.

## النشر الأول (مرة واحدة)

بعد `git clone` ونسخ `.env`:

<div dir="ltr">

```bash
composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate                # فقط إذا كان APP_KEY فارغًا
php artisan migrate --force
php artisan db:seed --force             # الأدوار، والدول، والعملات، والخدمات
php artisan alraed:create-user --role=admin
php artisan optimize && php artisan filament:optimize
chmod 600 .env
```

</div>

عند أول دخول إلى `/alraed` يُطلب إعداد تطبيق مصادقة (Google Authenticator أو Microsoft Authenticator). احفظ رموز الاسترداد في مكان آمن.

## نسخة احتياطية من قاعدة البيانات

دوال `passthru` و`exec` معطّلة في استضافة Hostinger المشتركة، والصيغة `<(...)` لا تعمل هناك. الطريقة التالية هي التي تعمل: ملف اتصال مؤقت بصلاحية 600 يُحذف فورًا بعد الاستخدام، فلا تظهر كلمة المرور على الشاشة.

<div dir="ltr">

```bash
DB=$(php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); $c = config("database.connections.mysql"); umask(077); $q = fn ($v) => "\"" . addcslashes((string) $v, "\"\\") . "\""; file_put_contents(getenv("HOME") . "/.alraed-dump.cnf", "[client]\nuser=" . $q($c["username"]) . "\npassword=" . $q($c["password"]) . "\nhost=" . $q($c["host"]) . "\nport=" . (int) $c["port"] . "\n"); echo $c["database"];') && mysqldump --defaults-extra-file="$HOME/.alraed-dump.cnf" --single-transaction --routines --no-tablespaces "$DB" | gzip > ~/backups/db-$(date +%Y%m%d-%H%M).sql.gz; rm -f ~/.alraed-dump.cnf; chmod 600 ~/backups/db-*.sql.gz; ls -lh ~/backups/ | tail -3
```

</div>

خذ نسخة قبل كل: `migrate`، و`alraed:demo-data`، و`alraed:clear-data`، وأي تعديل يدوي على البيانات. ونزّل نسخة دورية إلى جهازك، لأن النسخة الموجودة على السيرفر وحده لا تحمي من فقدان الحساب.

## التراجع

- **الكود:** `git log --oneline -5`، ثم `git checkout <commit>` للنسخة السابقة، ثم `php artisan optimize:clear && php artisan optimize`.
- **قاعدة البيانات:** استرجع آخر نسخة احتياطية بـ `zcat ~/backups/<file>.sql.gz | mysql --defaults-extra-file=...`، بعد إنشاء ملف الاتصال المؤقت بالطريقة السابقة نفسها.
- **`.env`:** النسخ السابقة محفوظة في `~/backups/env-*.bak`.

## قائمة فحص بعد كل نشر

- [ ] `php artisan about --only=environment` يعرض `production` و`Debug Mode OFF`.
- [ ] الصفحة الرئيسية ونموذج الطلب يعملان على الهاتف.
- [ ] الدخول إلى `/alraed` يطلب التحقق بخطوتين.
- [ ] رابط إيصال من حوالة يفتح للموظف، ويعطي خطأ 404 أو 403 لغير المسجّل.
- [ ] `git status` على السيرفر نظيف (لا تعديلات محلية).

</div>
