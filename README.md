# beon-laravel

> Send WhatsApp messages from Laravel in 3 lines — powered by [Beon](https://beon.chat)

```php
use Beon\Laravel\Facades\Beon;

Beon::sendText('201000830792', 'Hello from Laravel 🚀');
```

[![Latest Version on Packagist](https://img.shields.io/packagist/v/beon/laravel.svg?style=flat-square)](https://packagist.org/packages/beon/laravel)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue)](https://packagist.org/packages/beon/laravel)
[![Laravel](https://img.shields.io/badge/laravel-10%20%7C%2011-orange)](https://laravel.com)
[![License](https://img.shields.io/badge/license-MIT-brightgreen)](LICENSE)

---

## Features

- ✅ Send text, template, media, interactive & reaction messages
- 🔐 OTP via WhatsApp/SMS
- 🪝 Webhook handler (all message types + status updates)
- 👤 Business Profile management (get / update / picture)
- 📁 Media upload with auto-retry

---

## Installation

```bash
composer require beon/laravel
```

Publish the config file:

```bash
php artisan vendor:publish --tag=beon-config
```

Add to your `.env`:

```env
BEON_API_KEY=your_beon_api_key
BEON_BASE_URL=https://v3.api.beon.chat
BEON_WEBHOOK_SECRET=your_webhook_secret
```

---

## Usage

### Send a Text Message

```php
use Beon\Laravel\Facades\Beon;

Beon::sendText('201012345678', 'Hello 👋');
```

### Send a Template

```php
Beon::sendTemplate('201000830792', 'welcome_msg', [
    ['type' => 'body', 'parameters' => [
        ['type' => 'text', 'text' => 'Ahmed'],
    ]],
], 'ar');
```

### Send an Image

```php
Beon::sendMedia('201000830792', 'image', 'https://example.com/photo.jpg', 'Check this out!');
```

### Send Media by ID (after uploading)

```php
$upload = Beon::uploadMedia('/path/to/file.pdf', 'document', $phoneNumberId, $metaToken);

Beon::sendMediaById('201000830792', 'document', $upload['media_id'], null, 'invoice.pdf');
```

### Send Interactive Buttons

```php
Beon::sendInteractive('201000830792', 'Do you confirm?', [
    ['type' => 'reply', 'reply' => ['id' => '1', 'title' => 'Yes ✅']],
    ['type' => 'reply', 'reply' => ['id' => '2', 'title' => 'No ❌']],
]);
```

### Send Interactive List

```php
Beon::sendInteractiveList('201000830792', 'Pick a plan:', 'See Plans', [
    ['title' => 'Starter', 'rows' => [
        ['id' => 'starter', 'title' => 'Starter — Free'],
        ['id' => 'pro',     'title' => 'Pro — $29/mo'],
    ]],
]);
```

### Send a Reaction

```php
Beon::sendReaction('201000830792', 'wamid.MESSAGE_ID', '👍');
```

### Send OTP (Beon API)

```php
$result = Beon::sendOtp('1012345678', 'Ahmed', '20'); // Egypt
// $result['otp'] contains the generated OTP code for verification
```

### Send OTP (WhatsApp Auth Template)

```php
Beon::sendOtpTemplate('201000830792', '482931', 'ar');
```

### Check if a Number is on WhatsApp

```php
$check = Beon::checkContact('+201012345678', $phoneNumberId, $metaToken);
```

### Get Business Profile

```php
$profile = Beon::getBusinessProfile($phoneNumberId, $metaToken);
// $profile['data']['about'], ['email'], ['websites'], ['profile_picture_url']
```

### Update Business Profile

```php
Beon::updateBusinessProfile($phoneNumberId, $metaToken, [
    'about'   => 'Your business tagline',
    'email'   => 'support@example.com',
    'websites' => ['https://example.com'],
    'vertical' => 'RETAIL',
]);
```

### Update Profile Picture

```php
Beon::updateProfilePicture($phoneNumberId, $appId, $metaToken, '/path/to/logo.png');
```

---

## Webhooks

Register the webhook routes in `routes/web.php`:

```php
Route::beonWebhook('/beon/webhook');
// Registers GET (verification) + POST (events)
```

Listen to events in your `EventServiceProvider` or anywhere using `Event::listen`:

```php
use Beon\Laravel\Events\MessageReceived;
use Beon\Laravel\Events\MessageStatusUpdated;

// New incoming message
Event::listen(MessageReceived::class, function ($event) {
    $from = $event->payload['from'];
    $type = $event->payload['type'];

    if ($type === 'text') {
        $text = $event->payload['text'];
    }

    if ($type === 'image') {
        $mediaId = $event->payload['media_id'];
        $caption = $event->payload['caption'];
    }
});

// Delivery status update
Event::listen(MessageStatusUpdated::class, function ($event) {
    $messageId = $event->payload['message_id'];
    $status    = $event->payload['status']; // sent | delivered | read | failed
});
```

**Supported incoming message types:**

| Type | Extra payload keys |
|------|--------------------|
| `text` | `text` |
| `image` / `video` / `audio` / `document` | `media_id`, `mime_type`, `caption`, `filename` |
| `sticker` | `media_id`, `mime_type` |
| `location` | `latitude`, `longitude`, `name`, `address` |
| `contacts` | `contacts[]` (name, phone) |
| `interactive` | `reply_type`, `reply_id`, `reply_title` |
| `button` | `text`, `payload` |
| `reaction` | `emoji`, `message_id` |
| `order` | `catalog_id`, `text`, `product_items[]` |

---

## Testing

```bash
composer install
./vendor/bin/phpunit
```

---

## Changelog

### v1.0.0
- Initial release

---

## Author

**Yasser Farag Abdelhamid** — Backend Engineer @ [Beon](https://beon.chat)

📱 WhatsApp: [+201000830792](https://wa.me/201000830792)

---

## License

MIT — © [Beon](https://beon.chat)
