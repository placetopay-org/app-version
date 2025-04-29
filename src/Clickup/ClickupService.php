<?php

namespace PlacetoPay\AppVersion\Clickup;

use Illuminate\Support\Facades\Http;
use PlacetoPay\AppVersion\Exceptions\ConnectionException;

class ClickupService
{
    private ?string $baseUrl;
    private ?string $apiKey;

    public function __construct()
    {
        $this->baseUrl = config('utilities.clickup.base_url');
        $this->apiKey = config('utilities.clickup.api_token');
    }

    /**
     * @throws ConnectionException
     */
    public function postCommentOnTask(string $taskId, string $message, ?string $team = null): void
    {
        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
        ])->contentType('application/json')
            ->accept('application/json')
            ->baseUrl($this->baseUrl)
            ->post(
                "/task/$taskId/comment" . (!empty($team) ? "?custom_task_ids=true&team_id=$team" : ''),
                ['comment_text' => $message]
            );

        if (!$response->successful()) {
            throw ConnectionException::forNoConnectionService($response->reason());
        }
    }
}
