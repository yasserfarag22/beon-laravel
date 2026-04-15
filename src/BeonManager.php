<?php

namespace Beon\Laravel;

use Beon\Laravel\Events\MessageReceived;
use Beon\Laravel\Events\MessageStatusUpdated;

class BeonManager
{
    protected BeonClient $client;

    public function __construct(BeonClient $client)
    {
        $this->client = $client;
    }

    /**
     * Send an OTP via Beon's OTP API.
     * Returns the OTP code from the API for server-side verification.
     *
     * @param  string $to           Phone number (e.g. "201012345678")
     * @param  string $name         Recipient's name
     * @param  string $lang         Language (ar|en)
     * @param  string $type         'whatsapp' | 'sms'
     */
    public function sendOtp(string $to, string $name, string $lang = 'ar', string $type = 'whatsapp'): array
    {
        return $this->client->post('/api/v3/messages/otp', [
            'phoneNumber' => $to,
            'name'        => $name,
            'type'        => $type,
            'lang'        => $lang,
        ]);
    }

    /**
     * Send a WhatsApp message using a template.
     *
     * @param  string $to               Recipient phone number
     * @param  string $name             Recipient name
     * @param  int    $templateId       Template ID from Beon Dashboard
     * @param  string $templateContent  Full template text content
     * @param  array  $templateJson     Meta template JSON structure (name, language, components)
     * @param  array  $customAttrs      Optional parameters for placeholders
     */
    public function sendMessage(
        string $to,
        string $name,
        int $templateId,
        string $templateContent,
        array $templateJson,
        array $customAttrs = []
    ): array {
        return $this->client->post('/api/v3/messages/whatsapp/template', [
            'phoneNumber'      => $to,
            'name'             => $name,
            'template_id'      => $templateId,
            'template_content' => $templateContent,
            'template'         => $templateJson,
            'custom_attribute' => $customAttrs,
        ]);
    }


    /**
     * Send an OTP using an approved WhatsApp Authentication template.
     * The template includes an auto-copy button for the OTP code.
     *
     * @param  string $to       Phone with country code (e.g. "201012345678")
     * @param  string $otpCode  The OTP code to embed
     * @param  string $lang     Template language code (default: 'en')
     */
    public function sendOtpTemplate(string $to, string $otpCode, string $lang = 'en'): array
    {
        return $this->sendMessage(
            $to,
            'otp',
            0, // template_id - Beon will find the auth template
            'OTP code: ' . $otpCode,
            [
                'name' => 'otp_template', // placeholder, Beon finds it
                'language' => ['code' => $lang],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [['type' => 'text', 'text' => $otpCode]]
                    ],
                    [
                        'type' => 'button',
                        'sub_type' => 'url',
                        'index' => 0,
                        'parameters' => [['type' => 'text', 'text' => $otpCode]]
                    ]
                ]
            ]
        );
    }


    // ─────────────────────────────────────────────
    // 📁 MEDIA UPLOAD
    // ─────────────────────────────────────────────

    /**
     * Upload a media file to Meta and get a media_id.
     * Use the returned media_id with sendMediaById() for reliable delivery.
     *
     * @param  string      $filePath     Absolute path to the file
     * @param  string      $type         image|video|audio|document
     * @param  string      $phoneNumberId Meta phone number ID
     * @param  string      $token         Meta WhatsApp access token
     * @param  string|null $filename      Optional display filename
     */
    public function uploadMedia(string $filePath, string $type, string $phoneNumberId, string $token, ?string $filename = null): array
    {
        if (! file_exists($filePath)) {
            return ['success' => false, 'error' => "File not found: {$filePath}"];
        }

        $filename  = $filename ?? basename($filePath);
        $mimeType  = mime_content_type($filePath) ?: 'application/octet-stream';
        $fileSize  = filesize($filePath);
        $maxSize   = 100 * 1024 * 1024; // 100MB

        if ($fileSize > $maxSize) {
            return ['success' => false, 'error' => 'File exceeds WhatsApp 100MB limit'];
        }

        $apiUrl   = "https://graph.facebook.com/v22.0/{$phoneNumberId}/media";
        $maxTries = 3;
        $backoff   = [5, 15, 30];

        for ($attempt = 1; $attempt <= $maxTries; $attempt++) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => [
                    'file'              => new \CURLFile($filePath, $mimeType, $filename),
                    'messaging_product' => 'whatsapp',
                    'type'              => $type,
                    'filename'          => $filename,
                ],
                CURLOPT_CONNECTTIMEOUT => 60,
                CURLOPT_TIMEOUT        => max(180, min(900, (int) ceil($fileSize / 1024 / 1024) * 30)),
                CURLOPT_FRESH_CONNECT  => true,
                CURLOPT_FORBID_REUSE   => true,
                CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
                CURLOPT_IPRESOLVE      => CURL_IPRESOLVE_V4,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer {$token}",
                    'Expect:',
                ],
            ]);

            $body     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr  = curl_error($ch);
            curl_close($ch);

            if ($curlErr) {
                if ($attempt < $maxTries) { sleep($backoff[$attempt - 1]); continue; }
                return ['success' => false, 'error' => "cURL error: {$curlErr}"];
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($body, true);
                return ['success' => true, 'media_id' => $data['id'] ?? null, 'data' => $data];
            }

            if (in_array($httpCode, [429, 500, 502, 503, 504]) && $attempt < $maxTries) {
                sleep($backoff[$attempt - 1]);
                continue;
            }

            return ['success' => false, 'error' => $body, 'status_code' => $httpCode];
        }

        return ['success' => false, 'error' => 'Media upload failed after retries'];
    }

    // ─────────────────────────────────────────────
    // ✅ CONTACT VALIDATION
    // ─────────────────────────────────────────────

    /**
     * Check if a phone number is registered on WhatsApp.
     *
     * @param  string $phoneNumber   Phone number to check
     * @param  string $phoneNumberId Meta phone number ID
     * @param  string $token         Meta WhatsApp access token
     */
    public function checkContact(string $phoneNumber, string $phoneNumberId, string $token): array
    {
        $apiUrl = "https://graph.facebook.com/v19/{$phoneNumberId}/contacts";

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $apiUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'blocking'    => 'wait',
                    'contacts'    => [$phoneNumber],
                    'force_check' => true,
                ]),
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bearer {$token}",
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
            $body     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($body, true);
            return ['success' => $httpCode >= 200 && $httpCode < 300, 'data' => $data, 'status_code' => $httpCode];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────
    // 👤 BUSINESS PROFILE
    // ─────────────────────────────────────────────

    /**
     * Get WhatsApp Business Profile (about, email, websites, picture, vertical).
     */
    public function getBusinessProfile(string $phoneNumberId, string $token): array
    {
        $url = "https://graph.facebook.com/v22.0/{$phoneNumberId}/whatsapp_business_profile";

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url . '?fields=about,address,description,email,profile_picture_url,websites,vertical',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
                CURLOPT_TIMEOUT        => 30,
            ]);
            $body     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $data = json_decode($body, true);
            $profileData = $data['data'][0] ?? [];

            return [
                'success'     => $httpCode >= 200 && $httpCode < 300,
                'status_code' => $httpCode,
                'data'        => $profileData,
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Update WhatsApp Business Profile fields.
     *
     * @param  string $phoneNumberId
     * @param  string $token
     * @param  array  $data  Keys: about, address, description, email, websites, vertical, profile_picture_handle
     */
    public function updateBusinessProfile(string $phoneNumberId, string $token, array $data): array
    {
        $allowed = ['about', 'address', 'description', 'email', 'websites', 'vertical', 'profile_picture_handle'];
        $payload = array_intersect_key($data, array_flip($allowed));
        $payload['messaging_product'] = 'whatsapp';

        $url = "https://graph.facebook.com/v22.0/{$phoneNumberId}/whatsapp_business_profile";

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_HTTPHEADER     => [
                    "Authorization: Bearer {$token}",
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT => 30,
            ]);
            $body     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [
                'success'     => $httpCode >= 200 && $httpCode < 300,
                'status_code' => $httpCode,
                'data'        => json_decode($body, true),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get phone number info (quality rating, messaging limits, status, etc.).
     */
    public function getPhoneNumberInfo(string $phoneNumberId, string $token): array
    {
        $fields = 'verified_name,quality_rating,quality_score,name_status,status,display_phone_number,messaging_limit_tier,is_official_business_account,account_mode,platform_type,throughput';
        $url    = "https://graph.facebook.com/v22.0/{$phoneNumberId}?fields={$fields}";

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}"],
                CURLOPT_TIMEOUT        => 30,
            ]);
            $body     = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return [
                'success'     => $httpCode >= 200 && $httpCode < 300,
                'status_code' => $httpCode,
                'data'        => json_decode($body, true),
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Upload a profile picture to Meta and return the file handle.
     *
     * @param  string $appId    Meta App ID
     * @param  string $token    Meta access token
     * @param  string $filePath Absolute path to the image (JPEG/PNG, max 5MB)
     */
    public function uploadProfilePicture(string $appId, string $token, string $filePath): array
    {
        if (! file_exists($filePath)) {
            return ['success' => false, 'error' => "File not found: {$filePath}"];
        }

        $mimeType    = mime_content_type($filePath);
        $allowed     = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileSize    = filesize($filePath);
        $fileContent = file_get_contents($filePath);
        $fileName    = basename($filePath);

        if (! in_array($mimeType, $allowed)) {
            return ['success' => false, 'error' => 'Only JPEG/PNG images are allowed for profile pictures'];
        }

        if ($fileSize > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'Profile picture must not exceed 5MB'];
        }

        // Step 1: Create upload session
        $sessionUrl = "https://graph.facebook.com/v22.0/{$appId}/uploads";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $sessionUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['file_length' => $fileSize, 'file_type' => $mimeType, 'file_name' => $fileName]),
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}", 'Content-Type: application/json'],
            CURLOPT_TIMEOUT        => 30,
        ]);
        $sessionBody = curl_exec($ch);
        $sessionCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $sessionData = json_decode($sessionBody, true);
        if ($sessionCode < 200 || $sessionCode >= 300 || empty($sessionData['id'])) {
            return ['success' => false, 'error' => $sessionData['error']['message'] ?? 'Failed to create upload session'];
        }

        $uploadSessionId = $sessionData['id'];

        // Step 2: Upload the file
        $uploadUrl = "https://graph.facebook.com/v22.0/{$uploadSessionId}";
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $uploadUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $fileContent,
            CURLOPT_HTTPHEADER     => ["Authorization: Bearer {$token}", "file_offset: 0", "Content-Type: {$mimeType}"],
            CURLOPT_TIMEOUT        => 60,
        ]);
        $uploadBody = curl_exec($ch);
        $uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $uploadData = json_decode($uploadBody, true);
        if ($uploadCode < 200 || $uploadCode >= 300 || empty($uploadData['h'])) {
            return ['success' => false, 'error' => $uploadData['error']['message'] ?? 'Failed to upload file'];
        }

        return ['success' => true, 'handle' => $uploadData['h']];
    }

    /**
     * Upload + apply profile picture in one call.
     */
    public function updateProfilePicture(string $phoneNumberId, string $appId, string $token, string $filePath): array
    {
        $upload = $this->uploadProfilePicture($appId, $token, $filePath);

        if (! $upload['success']) {
            return $upload;
        }

        return $this->updateBusinessProfile($phoneNumberId, $token, [
            'profile_picture_handle' => $upload['handle'],
        ]);
    }
}
