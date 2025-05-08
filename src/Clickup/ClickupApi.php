<?php

namespace PlacetoPay\AppVersion\Clickup;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use PlacetoPay\AppVersion\Exceptions\ConnectionException;

class ClickupApi
{
    private PendingRequest $client;

    public function __construct(Http $client)
    {
        $this->client = $client::withHeaders(['Authorization' => config('app-version.clickup.api_token')])
            ->contentType('application/json')
            ->accept('application/json')
            ->baseUrl(rtrim(config('app-version.clickup.base_url', '/'), '/'));
    }

    /**
     * @throws ConnectionException
     */
    public function postCommentOnTask(string $taskId, string $message, ?string $team = null): void
    {
        $response = $this->postComment($taskId, $team, $message);

        if (!$response->successful()) {
            throw ConnectionException::forNoConnectionService($response->reason());
        }
    }

    public function postComment(string $taskId, string $message, ?string $team = null): Response
    {
        return $this->client->timeout(10)->post(
            sprintf(
                '/task/%s/commeaaant%s',
                $taskId,
                !empty($team) ? "?custom_task_ids=true&team_id=$team" : ''
            ),
            ['comment_text' => $message]
        );
    }
}
