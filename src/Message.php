<?php

namespace Beon\Laravel;

use Beon\Laravel\Exceptions\ValidationException;

class Message
{
    protected BeonManager $manager;
    protected string $to;
    protected ?string $recipientName = null;
    protected ?string $templateName = null;
    protected ?int $templateId = null;
    protected ?string $templateContent = null;
    protected array $variables = [];
    protected array $customAttributes = [];
    protected ?string $type = 'template';
    protected ?string $content = null;
    protected ?string $mediaUrl = null;
    protected ?string $caption = null;
    protected ?string $fileName = null;
    protected ?string $replayId = null;
    protected ?string $uid = null;
    protected string $language = 'ar';

    public function __construct(BeonManager $manager)
    {
        $this->manager = $manager;
    }

    public function to(string $to, ?string $name = null): self
    {
        $this->to = $to;
        $this->recipientName = $name;
        return $this;
    }

    /**
     * Send a WhatsApp template.
     */
    public function template(string $name, ?int $id = null, ?string $content = null): self
    {
        $this->type = 'template';
        $this->templateName = $name;
        $this->templateId = $id;
        $this->templateContent = $content;

        // Try to resolve from config if not fully provided
        $this->resolveTemplateFromConfig();

        return $this;
    }

    /**
     * Send a direct text message.
     */
    public function text(string $content): self
    {
        $this->type = 'text';
        $this->content = $content;
        return $this;
    }

    /**
     * Send an image message.
     */
    public function image(string $url, ?string $caption = null): self
    {
        $this->type = 'image';
        $this->mediaUrl = $url;
        $this->caption = $caption;
        return $this;
    }

    /**
     * Send a document message.
     */
    public function document(string $url, ?string $filename = null, ?string $caption = null): self
    {
        $this->type = 'document';
        $this->mediaUrl = $url;
        $this->fileName = $filename;
        $this->caption = $caption;
        return $this;
    }

    /**
     * Send a video message.
     */
    public function video(string $url, ?string $caption = null): self
    {
        $this->type = 'video';
        $this->mediaUrl = $url;
        $this->caption = $caption;
        return $this;
    }

    /**
     * Send an audio message.
     */
    public function audio(string $url): self
    {
        $this->type = 'audio';
        $this->mediaUrl = $url;
        return $this;
    }

    /**
     * Reply to a specific message ID.
     */
    public function replyTo(string $messageId): self
    {
        $this->replayId = $messageId;
        return $this;
    }

    /**
     * Set a unique ID for the message (for tracking/deduplication).
     */
    public function uid(string $uid): self
    {
        $this->uid = $uid;
        return $this;
    }

    public function withVariables(array $variables): self
    {
        $this->variables = $variables;
        return $this;
    }

    public function withAttributes(array $attributes): self
    {
        $this->customAttributes = $attributes;
        return $this;
    }

    public function language(string $lang): self
    {
        $this->language = $lang;
        return $this;
    }

    protected function resolveTemplateFromConfig(): void
    {
        $templates = config('beon.templates', []);

        if (isset($templates[$this->templateName])) {
            $tpl = $templates[$this->templateName];
            $this->templateId = $this->templateId ?? ($tpl['id'] ?? null);
            $this->templateContent = $this->templateContent ?? ($tpl['content'] ?? null);
            $this->language = $this->language ?? ($tpl['language'] ?? 'ar');
        }
    }

    public function send(): array
    {
        $this->validate();
        return $this->manager->send($this);
    }

    public function getType(): string
    {
        return $this->type;
    }

    protected function validate(): void
    {
        if (empty($this->to)) {
            throw new ValidationException("Recipient phone number is required.");
        }

        if ($this->type === 'template' && empty($this->templateName) && empty($this->templateId)) {
            throw new ValidationException("Template name or ID is required for template messages.");
        }

        if ($this->type === 'text' && empty($this->content)) {
            throw new ValidationException("Content is required for text messages.");
        }

        if (in_array($this->type, ['image', 'video', 'document', 'audio']) && empty($this->mediaUrl)) {
            throw new ValidationException("Media URL is required for media messages.");
        }
    }

    public function toPayload(): array
    {
        if ($this->type === 'template') {
            return [
                'phoneNumber'      => $this->to,
                'name'             => $this->recipientName ?? 'Customer',
                'template_id'      => $this->templateId ?? 0,
                'template_content' => $this->templateContent ?? '',
                'template'         => [
                    'name'       => $this->templateName,
                    'language'   => ['code' => $this->language],
                    'components' => [
                        [
                            'type'       => 'body',
                            'parameters' => array_map(fn($v) => ['type' => 'text', 'text' => (string) $v], $this->variables),
                        ],
                    ],
                ],
                'custom_attribute' => $this->customAttributes,
            ];
        }

        // Session/Conversation message payload
        return [
            'phoneNumber' => $this->to,
            'type'      => $this->type,
            'content'   => $this->content,
            'media_url' => $this->mediaUrl,
            'caption'   => $this->caption,
            'file_name' => $this->fileName,
            'replay_id' => $this->replayId,
            'uid'       => $this->uid,
            'name'      => $this->recipientName,
            // Note: The controller/manager will resolve the conversation_id from phone number
        ];

    }
}

