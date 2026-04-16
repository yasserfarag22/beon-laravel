<?php

namespace Beon\Laravel;

use Beon\Laravel\Events\MessageReceived;
use Beon\Laravel\Events\MessageStatusUpdated;

class BeonManager
{
    use MetaDirectCalls;

    protected BeonClient $client;
    protected ?int $channelId = null;

    public function __construct(BeonClient $client)
    {
        $this->client = $client;
        $this->channelId = config('beon.default_channel_id');
    }

    /**
     * Start a fluent message chain.
     */
    public function to(string $to, ?string $name = null): Message
    {
        return (new Message($this))->to($to, $name);
    }

    /**
     * Set the channel ID for the next request.
     */
    public function channel(int $id): self
    {
        $this->channelId = $id;
        return $this;
    }

    /**
     * Send a Message object.
     */
    public function send(Message $message): array
    {
        $payload = $message->toPayload();

        if ($this->channelId) {
            $payload['channel_id'] = $this->channelId;
        }

        if ($message->getType() === 'template') {
            return $this->client->post('/api/v3/messages/whatsapp/template', $payload);
        }

        // For session messages, we first ensure a conversation exists
        $conversationId = $this->getOrCreateConversation($payload['phoneNumber'], $payload['name'] ?? null);
        $payload['conversation_id'] = $conversationId;

        return $this->client->post('/api/v3/conversation/message/send', $payload);
    }

    /**
     * Get or create a conversation for a phone number.
     */
    public function getOrCreateConversation(string $phone, ?string $name = null): int
    {
        $response = $this->client->post('/api/v3/conversation/start', [
            'phone' => $phone,
            'name'  => $name ?? 'Customer',
            'channel_id' => $this->channelId,
        ]);

        return $response['data']['conversation_id'] ?? $response['conversation_id'];
    }

    /**
     * React to a specific message.
     */
    public function react(string $messageId, string $emoji, string $phone, int $conversationId): array
    {
        return $this->client->post('/api/v3/conversation/react', [
            'message_id'      => $messageId,
            'conversation_id' => $conversationId,
            'channel_id'      => $this->channelId,
            'react'           => $emoji,
        ]);
    }

    /**
     * List available WhatsApp templates.
     */
    public function templates(): array
    {
        return $this->client->get('/api/v3/template');
    }

    /**
     * Send an OTP via Beon's OTP API.
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
     * Send a WhatsApp message using a template (Legacy/Raw support).
     */
    public function sendMessage(
        string $to,
        string $name,
        int $templateId,
        string $templateContent,
        array $templateJson,
        array $customAttrs = []
    ): array {
        return $this->to($to, $name)
            ->template($templateJson['name'] ?? '', $templateId, $templateContent)
            ->withVariables($this->extractVariables($templateJson))
            ->withAttributes($customAttrs)
            ->language($templateJson['language']['code'] ?? 'ar')
            ->send();
    }

    /**
     * Helper to extract variables from the legacy meta structure.
     */
    protected function extractVariables(array $templateJson): array
    {
        $variables = [];
        foreach ($templateJson['components'] ?? [] as $component) {
            if ($component['type'] === 'body') {
                foreach ($component['parameters'] ?? [] as $param) {
                    if ($param['type'] === 'text') {
                        $variables[] = $param['text'];
                    }
                }
            }
        }
        return $variables;
    }

    /**
     * Send an OTP using an approved WhatsApp Authentication template.
     */
    public function sendOtpTemplate(string $to, string $otpCode, string $lang = 'en'): array
    {
        return $this->to($to, 'otp')
            ->template('otp_template', 0, 'OTP code: ' . $otpCode)
            ->withVariables([$otpCode])
            ->language($lang)
            ->send();
    }

    /**
     * Upload + apply profile picture in one call.
     */
    public function updateProfilePicture(string $phoneNumberId, string $appId, string $token, string $filePath): array
    {
        $upload = $this->uploadProfilePicture($appId, $token, $filePath);

        return $this->updateBusinessProfile($phoneNumberId, $token, [
            'profile_picture_handle' => $upload['handle'],
        ]);
    }
}

