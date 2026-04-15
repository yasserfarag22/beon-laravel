<?php

namespace Beon\Laravel\Http;

use Beon\Laravel\Events\MessageReceived;
use Beon\Laravel\Events\MessageStatusUpdated;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BeonWebhookController extends Controller
{
    /**
     * Handle Meta/Beon webhook verification (GET).
     */
    public function verify(Request $request)
    {
        $verifyToken = config('beon.webhook_secret');

        if ($request->query('hub_verify_token') === $verifyToken) {
            return response($request->query('hub_challenge'), 200);
        }

        return response('Invalid verification token', 403);
    }

    /**
     * Handle incoming webhook payloads (POST).
     */
    public function handle(Request $request)
    {
        $payload = $request->all();

        // ── Status updates ────────────────────────────────
        if (isset($payload['entry'])) {
            foreach ($payload['entry'] as $entry) {
                foreach ($entry['changes'] ?? [] as $change) {
                    $value = $change['value'] ?? [];

                    // Delivery status updates
                    if (isset($value['statuses'])) {
                        foreach ($value['statuses'] as $status) {
                            event(new MessageStatusUpdated([
                                'message_id' => $status['id'] ?? null,
                                'status'     => $status['status'] ?? null,
                                'timestamp'  => $status['timestamp'] ?? null,
                                'recipient'  => $status['recipient_id'] ?? null,
                                'errors'     => $status['errors'] ?? [],
                                'raw'        => $status,
                            ]));
                        }
                    }

                    // Incoming messages
                    if (isset($value['messages'])) {
                        foreach ($value['messages'] as $message) {
                            $parsed = $this->parseMessage($message);

                            if ($parsed === null) {
                                continue; // skip unsupported/reactions handled internally
                            }

                            event(new MessageReceived($parsed));
                        }
                    }
                }
            }
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Parse a raw WhatsApp message into a normalized array.
     * Returns null for unsupported message types that should be silently skipped.
     */
    protected function parseMessage(array $message): ?array
    {
        $type = $message['type'] ?? 'text';
        $id   = $message['id'] ?? null;
        $from = $message['from'] ?? null;
        $ts   = $message['timestamp'] ?? null;

        if ($type === 'unsupported') {
            return null;
        }

        $normalized = [
            'id'        => $id,
            'from'      => $from,
            'timestamp' => $ts,
            'type'      => $type,
            'raw'       => $message,
        ];

        switch ($type) {
            case 'text':
                $normalized['text'] = $message['text']['body'] ?? null;
                break;

            case 'image':
            case 'video':
            case 'audio':
            case 'document':
                $normalized['media_id']  = $message[$type]['id'] ?? null;
                $normalized['mime_type'] = $message[$type]['mime_type'] ?? null;
                $normalized['caption']   = $message[$type]['caption'] ?? null;
                if ($type === 'document') {
                    $normalized['filename'] = $message[$type]['filename'] ?? null;
                }
                break;

            case 'sticker':
                $normalized['media_id']  = $message['sticker']['id'] ?? null;
                $normalized['mime_type'] = $message['sticker']['mime_type'] ?? 'image/webp';
                break;

            case 'location':
                $normalized['latitude']  = $message['location']['latitude'] ?? null;
                $normalized['longitude'] = $message['location']['longitude'] ?? null;
                $normalized['name']      = $message['location']['name'] ?? null;
                $normalized['address']   = $message['location']['address'] ?? null;
                break;

            case 'contacts':
                $normalized['contacts'] = array_map(fn($c) => [
                    'name'  => $c['name']['formatted_name'] ?? null,
                    'phone' => $c['phones'][0]['phone'] ?? null,
                ], $message['contacts'] ?? []);
                break;

            case 'interactive':
                $interactive = $message['interactive'] ?? [];
                if (isset($interactive['button_reply'])) {
                    $normalized['reply_type']  = 'button';
                    $normalized['reply_id']    = $interactive['button_reply']['id'] ?? null;
                    $normalized['reply_title'] = $interactive['button_reply']['title'] ?? null;
                } elseif (isset($interactive['list_reply'])) {
                    $normalized['reply_type']        = 'list';
                    $normalized['reply_id']          = $interactive['list_reply']['id'] ?? null;
                    $normalized['reply_title']       = $interactive['list_reply']['title'] ?? null;
                    $normalized['reply_description'] = $interactive['list_reply']['description'] ?? null;
                }
                break;

            case 'button':
                $normalized['text']      = $message['button']['text'] ?? null;
                $normalized['payload']   = $message['button']['payload'] ?? null;
                break;

            case 'reaction':
                $normalized['emoji']        = $message['reaction']['emoji'] ?? null;
                $normalized['message_id']   = $message['reaction']['message_id'] ?? null;
                break;

            case 'order':
                $orderData = $message['order'] ?? [];
                $normalized['catalog_id']     = $orderData['catalog_id'] ?? null;
                $normalized['text']           = $orderData['text'] ?? null;
                $normalized['product_items']  = $orderData['product_items'] ?? [];
                break;

            default:
                $normalized['type'] = 'unknown';
                break;
        }

        return $normalized;
    }
}
