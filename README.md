# beon-laravel 🚀

**The Professional WhatsApp SDK for Laravel.**

`beon-laravel` offers a streamlined, object-oriented approach to integrating WhatsApp messaging into Laravel applications. Designed for reliability and ease of use, it features a fluent API, comprehensive error handling, and native integration with BeOn's messaging and CRM ecosystem.

-----

## ✨ Key Features

  - **Fluent Messaging Interface:** Build complex messages using an intuitive, chainable syntax.
  - **Automated CRM Sync:** Seamlessly update contact attributes and tags during the messaging lifecycle.
  - **Smart Template Engine:** Resolve and send WhatsApp Business templates via configuration or dynamic IDs.
  - **Multi-Media Support:** Native handling for images, videos, documents, and interactive buttons.
  - **Robust Exception Handling:** Descriptive, catchable exceptions for API, validation, and connection errors.
  - **Webhook Integration:** Built-in route macros and Laravel events for incoming messages and status updates.

-----

## 🚀 Installation

1.  Install the package via Composer:

<!-- end list -->

```bash
composer require beon/laravel
```

2.  Publish the configuration file:

<!-- end list -->

```bash
php artisan vendor:publish --tag="beon-config"
```

3.  Configure your credentials in `.env`:

<!-- end list -->

```env
BEON_API_KEY=your_beon_token_here
BEON_BASE_URL=https://v3.api.beon.chat
```

-----

## 📖 Usage Guide

### 1\. Sending Templates (Fluent API)

The most efficient way to communicate using approved WhatsApp Business templates:

```php
use Beon\Laravel\Facades\Beon;

Beon::to('201000830792', 'Yasser Farag')
    ->template('welcome_msg', 5103)
    ->withVariables(['Yasser', 'January 30th'])
    ->withAttributes(['customer_tier' => 'gold']) // Updates CRM attributes automatically
    ->send();
```

### 2\. Session & Direct Messaging

Send rich media messages during an active 24-hour customer session:

```php
// Simple Text
Beon::to($to)->text('How can we assist you today?')->send();

// Image with Caption
Beon::to($to)->image('https://example.com/promo.jpg', 'Exclusive Offer')->send();

// Document Attachment
Beon::to($to)->document('https://example.com/invoice.pdf', 'invoice_123.pdf')->send();
```

### 3\. OTP & Authentication

Reliable delivery for one-time passwords via WhatsApp API:

```php
// Direct OTP Delivery
Beon::sendOtp('201000830792', 'Yasser');

// OTP via Authenticated Template (with Auto-copy button)
Beon::sendOtpTemplate('201000830792', '123456');
```

-----

## ❌ Error Management

The package eliminates manual response checking by throwing specific exceptions:

```php
try {
    Beon::to($to)->template('non_existent')->send();
} catch (\Beon\Laravel\Exceptions\ApiException $e) {
    // API-level errors (e.g., Auth, Insufficient Balance)
} catch (\Beon\Laravel\Exceptions\ValidationException $e) {
    // Local input validation errors
} catch (\Beon\Laravel\Exceptions\BeonException $e) {
    // General package-level issues
}
```

-----

## 🔗 Webhook & Events

Handle incoming customer interactions using Laravel's native Event system.

1.  Register the webhook route in `routes/api.php`:

<!-- end list -->

```php
Route::beonWebhook();
```

2.  Attach listeners in your `EventServiceProvider`:

<!-- end list -->

```php
protected $listen = [
    \Beon\Laravel\Events\MessageStatusUpdated::class => [
        \App\Listeners\UpdateMessageStatus::class,
    ],
    \Beon\Laravel\Events\MessageReceived::class => [
        \App\Listeners\HandleCustomerReply::class,
    ],
];
```

-----

## 🧪 Testing

The package includes a comprehensive test suite to ensure stability:

```bash
vendor/bin/phpunit
```

-----

## 👨‍💻 Contribution & Support

Maintained by **Yasser Farag Abdelhamid** (Backend Developer at BeOn).
For technical support or inquiries: [01000830792](https://www.google.com/search?q=tel:%2B201000830792)

## 📄 License

The MIT License (MIT). Please see the [License File](https://www.google.com/search?q=LICENSE) for more details.