<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Sends reminders from Tahselly's own WhatsApp Business number through Meta's Cloud API.
 * Off until WHATSAPP_TOKEN and WHATSAPP_PHONE_NUMBER_ID are set on the server.
 *
 * Meta only lets businesses start a chat with an approved template, so each stage has one
 * ("{prefix}_before_due", "_on_due", "_overdue", "_final") in Arabic and English with five
 * variables: {{1}} client, {{2}} company, {{3}} invoice number, {{4}} amount, {{5}} due date.
 */
class WhatsappCloud
{
    public function enabled(): bool
    {
        return filled(config('services.whatsapp.token')) && filled(config('services.whatsapp.phone_number_id'));
    }

    /**
     * Send the stage's template for an invoice and return Meta's message id.
     */
    public function sendReminder(Invoice $invoice, string $stage, string $locale, string $to): string
    {
        $invoice->loadMissing(['client', 'company']);
        $params = [
            $invoice->client->name,
            $invoice->company->name,
            $invoice->number,
            number_format($invoice->balance(), 2).' '.$invoice->currency,
            $invoice->due_date->format('Y-m-d'),
        ];

        $response = $this->request()->post($this->url('messages'), [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => config('services.whatsapp.template_prefix').'_'.$stage,
                'language' => ['code' => $locale === 'en' ? 'en' : 'ar'],
                'components' => [[
                    'type' => 'body',
                    'parameters' => array_map(fn (string $text) => ['type' => 'text', 'text' => $text], $params),
                ]],
            ],
        ]);

        $id = $response->json('messages.0.id');
        if ($response->failed() || ! $id) {
            throw new RuntimeException($response->json('error.error_data.details') ?? $response->json('error.message') ?? 'WhatsApp API error '.$response->status());
        }

        return $id;
    }

    /**
     * Checks the token and number id; used by the setup script.
     *
     * @return array{number: ?string, name: ?string}
     */
    public function testConnection(): array
    {
        $response = $this->request()->get($this->url(''), ['fields' => 'display_phone_number,verified_name']);
        if ($response->failed()) {
            throw new RuntimeException($response->json('error.message') ?? 'WhatsApp API error '.$response->status());
        }

        return ['number' => $response->json('display_phone_number'), 'name' => $response->json('verified_name')];
    }

    private function request()
    {
        return Http::withToken((string) config('services.whatsapp.token'))->acceptJson()->timeout(15);
    }

    private function url(string $path): string
    {
        return rtrim('https://graph.facebook.com/'.config('services.whatsapp.version').'/'.config('services.whatsapp.phone_number_id').'/'.$path, '/');
    }
}
