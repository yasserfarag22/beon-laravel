<?php

namespace Beon\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static array sendText(string $to, string $message)
 * @method static array sendTemplate(string $to, string $templateName, array $components = [], string $lang = 'ar')
 * @method static array sendMedia(string $to, string $type, string $url, ?string $caption = null, ?string $filename = null)
 * @method static array sendMediaById(string $to, string $type, string $mediaId, ?string $caption = null, ?string $filename = null)
 * @method static array sendInteractive(string $to, string $body, array $buttons)
 * @method static array sendInteractiveList(string $to, string $body, string $buttonTitle, array $sections)
 * @method static array sendInteractiveLink(string $to, string $body, array $links)
 * @method static array sendReaction(string $to, string $messageId, string $emoji)
 * @method static array sendOtp(string $to, string $name, string $countryCode = '20')
 * @method static array sendOtpTemplate(string $to, string $otpCode, string $lang = 'en')
 * @method static array uploadMedia(string $filePath, string $type, string $phoneNumberId, string $token, ?string $filename = null)
 * @method static array checkContact(string $phoneNumber, string $phoneNumberId, string $token)
 * @method static array getBusinessProfile(string $phoneNumberId, string $token)
 * @method static array updateBusinessProfile(string $phoneNumberId, string $token, array $data)
 * @method static array getPhoneNumberInfo(string $phoneNumberId, string $token)
 * @method static array uploadProfilePicture(string $appId, string $token, string $filePath)
 * @method static array updateProfilePicture(string $phoneNumberId, string $appId, string $token, string $filePath)
 *
 * @see \Beon\Laravel\BeonManager
 */
class Beon extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'beon';
    }
}
