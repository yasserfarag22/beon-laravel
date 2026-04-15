# beon-laravel 🚀

ابعت WhatsApp من Laravel في 3 سطور كود بس — مدعوم بـ Beon API.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/beon/laravel.svg?style=flat-square)](https://packagist.org/packages/beon/laravel)
[![Total Downloads](https://img.shields.io/packagist/dt/beon/laravel.svg?style=flat-square)](https://packagist.org/packages/beon/laravel)

---

## ✨ المميزات (Features)
- **Messaging:** إرسال القوالب المعتمدة (Templates) بأسهل طريقة.
- **OTP:** دعم كامل للتحقق عبر WhatsApp و SMS مع كود OTP تلقائي.
- **Webhooks:** نظام جاهز لاستقبال حالات الرسائل والردود عبر Laravel Events.
- **Developer First:** Facade بسيط وتوثيق واضح.

---

## 🚀 التثبيت (Installation)

1. ثبت الـ Package عبر Composer:
```bash
composer require beon/laravel
```

2. انشر ملف الإعدادات:
```bash
php artisan vendor:publish --tag="beon-config"
```

---

## 📖 الاستخدام (Usage)

### 1. إرسال قالب (Send Template)
لأسباب أمنية وتوافقاً مع Meta، يتم إرسال الرسائل عبر القوالب المعتمدة:

```php
use Beon;

Beon::sendMessage(
    to: '201000830792',
    name: 'Yasser Farag',
    templateId: 1234, // معرف القالب من Beon Dashboard
    templateContent: 'أهلاً {{1}}، كود التحقق هو {{2}}',
    templateJson: [
        'name' => 'welcome_msg',
        'language' => ['code' => 'ar'],
        'components' => [
            [
                'type' => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => 'Yasser'],
                    ['type' => 'text', 'text' => '5566']
                ]
            ]
        ]
    ]
);
```

### 2. إرسال OTP
أسرع طريقة لإرسال أكواد التحقق:

```php
// إرسال OTP تلقائياً (Beon يختار القناة الأنسب)
$response = Beon::sendOtp('201000830792', 'Yasser Farag');

// أو إرسال كود OTP عبر قالب WhatsApp مخصص
Beon::sendOtpTemplate('201000830792', '123456', 'ar');
```

---

## 🛠 الإعدادات (Configuration)

أضف هذه القيم في ملف `.env`:

```env
BEON_API_KEY=your_api_token_here
BEON_BASE_URL=https://v3.api.beon.chat
BEON_WEBHOOK_SECRET=your_chosen_secret
```

---

## 🔗 الـ Webhooks
لاستقبال حالات الرسائل (Sent, Delivered, Read) والردود:

1. أضف الـ Route في `routes/api.php`:
```php
Route::beonWebhook();
```

2. استقبل الأحداث في `EventServiceProvider`:
```php
protected $listen = [
    \Beon\Laravel\Events\MessageStatusUpdated::class => [
        // Your Listener logic
    ],
    \Beon\Laravel\Events\MessageReceived::class => [
        // Your Listener logic
    ],
];
```

---

## 👨‍💻 المساهمة (Author)
تم التطوير بواسطة **Yasser Farag Abdelhamid** (Backend Developer at BeOn).
للتواصل: [01000830792](tel:+201000830792)

## 📄 الترخيص (License)
هذا المشروع مفتوح المصدر تحت ترخيص [MIT License](LICENSE).
