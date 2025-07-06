تمام، جهزت لك ملف dev-tools.md بسيط ومرتب تقدر تحطه في جذر مشروع Laravel تبعك:


---

dev-tools.md

# أدوات التطوير المفيدة في Laravel

## 1. اكتشاف الحزم غير المستخدمة
**composer-unused**  
تحليل الـ composer.json لاكتشاف الحزم غير المستخدمة فعليًا في كود المشروع.

تثبيت:
```bash
composer require --dev icanhazstring/composer-unused

تشغيل:

vendor/bin/composer-unused

إزالة:

composer remove --dev icanhazstring/composer-unused


---

2. تحسين الـ Autoload

تحديث الـ autoload files بعد التغييرات في الكود أو الحزم:

composer dump-autoload


---

3. تثبيت Debugbar (للتنقيح أثناء التطوير)

composer require --dev barryvdh/laravel-debugbar


---

4. تثبيت IDE Helper (لتحسين التكملة التلقائية في IDEs)

composer require --dev barryvdh/laravel-ide-helper

php artisan ide-helper:generate


---

ملف قابل للتوسعة بأي أدوات إضافية تحتاجها لاحقًا.

---

انسخه واحفظه كـ `dev-tools.md`، وراح يفيدك كثير لما تشتغل على مشاريع ثانية.

تحب أضيف أدوات ثانية مثل Faker أو Laravel Pint (تنسيق الكود)؟

