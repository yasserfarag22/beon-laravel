<?php

namespace Beon\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Beon\Laravel\Message to(string $to, string $name = null)
 * @method static \Beon\Laravel\BeonManager channel(int $id)
 * @method static array send(\Beon\Laravel\Message $message)
 * @method static array react(string $messageId, string $emoji, string $phone, int $conversationId)
 * @method static array templates()
 * @method static array sendMessage(string $to, string $name, int $templateId, string $templateContent, array $templateJson, array $customAttrs = [])
 * @method static array sendOtp(string $to, string $name, string $lang = 'ar', string $type = 'whatsapp')
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

