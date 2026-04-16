<?php

namespace Beon\Laravel;

use Beon\Laravel\Exceptions\ApiException;

trait MetaDirectCalls
{
    /**
     * Upload a media file to Meta and get a media_id.
     */
    public function uploadMedia(string $filePath, string $type, string $phoneNumberId, string $token, ?string $filename = null): array
    {
        if (! file_exists($filePath)) {
            throw new ApiException("File not found: {$filePath}");
        }

        $filename  = $filename ?? basename($filePath);
        $mimeType  = mime_content_type($filePath) ?: 'application/octet-stream';
        $fileSize  = filesize($filePath);
        $maxSize   = 100 * 1024 * 1024; // 100MB

        if ($fileSize > $maxSize) {
            throw new ApiException('File exceeds WhatsApp 100MB limit');
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
                throw new ApiException("cURL error: {$curlErr}");
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($body, true);
                return ['success' => true, 'media_id' => $data['id'] ?? null, 'data' => $data];
            }

            if (in_array($httpCode, [429, 500, 502, 503, 504]) && $attempt < $maxTries) {
                sleep($backoff[$attempt - 1]);
                continue;
            }

            throw new ApiException($body, $httpCode, json_decode($body, true) ?: []);
        }

        throw new ApiException('Media upload failed after retries');
    }

    /**
     * Check if a phone number is registered on WhatsApp.
     */
    public function checkContact(string $phoneNumber, string $phoneNumberId, string $token): array
    {
        $apiUrl = "https://graph.facebook.com/v19/{$phoneNumberId}/contacts";

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
        
        if ($httpCode >= 400) {
            throw new ApiException($body, $httpCode, $data ?: []);
        }

        return ['success' => true, 'data' => $data, 'status_code' => $httpCode];
    }

    /**
     * Get WhatsApp Business Profile.
     */
    public function getBusinessProfile(string $phoneNumberId, string $token): array
    {
        $url = "https://graph.facebook.com/v22.0/{$phoneNumberId}/whatsapp_business_profile";

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
        if ($httpCode >= 400) {
            throw new ApiException($body, $httpCode, $data ?: []);
        }

        $profileData = $data['data'][0] ?? [];

        return [
            'success'     => true,
            'status_code' => $httpCode,
            'data'        => $profileData,
        ];
    }

    /**
     * Update WhatsApp Business Profile fields.
     */
    public function updateBusinessProfile(string $phoneNumberId, string $token, array $data): array
    {
        $allowed = ['about', 'address', 'description', 'email', 'websites', 'vertical', 'profile_picture_handle'];
        $payload = array_intersect_key($data, array_flip($allowed));
        $payload['messaging_product'] = 'whatsapp';

        $url = "https://graph.facebook.com/v22.0/{$phoneNumberId}/whatsapp_business_profile";

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

        $resData = json_decode($body, true);
        if ($httpCode >= 400) {
            throw new ApiException($body, $httpCode, $resData ?: []);
        }

        return [
            'success'     => true,
            'status_code' => $httpCode,
            'data'        => $resData,
        ];
    }

    /**
     * Get phone number info.
     */
    public function getPhoneNumberInfo(string $phoneNumberId, string $token): array
    {
        $fields = 'verified_name,quality_rating,quality_score,name_status,status,display_phone_number,messaging_limit_tier,is_official_business_account,account_mode,platform_type,throughput';
        $url    = "https://graph.facebook.com/v22.0/{$phoneNumberId}?fields={$fields}";

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

        $data = json_decode($body, true);
        if ($httpCode >= 400) {
            throw new ApiException($body, $httpCode, $data ?: []);
        }

        return [
            'success'     => true,
            'status_code' => $httpCode,
            'data'        => $data,
        ];
    }

    /**
     * Upload a profile picture to Meta and return the file handle.
     */
    public function uploadProfilePicture(string $appId, string $token, string $filePath): array
    {
        if (! file_exists($filePath)) {
            throw new ApiException("File not found: {$filePath}");
        }

        $mimeType    = mime_content_type($filePath);
        $allowed     = ['image/jpeg', 'image/png', 'image/jpg'];
        $fileSize    = filesize($filePath);
        $fileContent = file_get_contents($filePath);
        $fileName    = basename($filePath);

        if (! in_array($mimeType, $allowed)) {
            throw new ApiException('Only JPEG/PNG images are allowed for profile pictures');
        }

        if ($fileSize > 5 * 1024 * 1024) {
            throw new ApiException('Profile picture must not exceed 5MB');
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
            throw new ApiException($sessionData['error']['message'] ?? 'Failed to create upload session', $sessionCode, $sessionData);
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
            throw new ApiException($uploadData['error']['message'] ?? 'Failed to upload file', $uploadCode, $uploadData);
        }

        return ['success' => true, 'handle' => $uploadData['h']];
    }
}
