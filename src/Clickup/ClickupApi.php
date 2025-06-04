<?php

namespace PlacetoPay\AppVersion\Clickup;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use PlacetoPay\AppVersion\Exceptions\BadResponseException;

class ClickupApi
{
    private PendingRequest $client;

    public function __construct(PendingRequest $client)
    {
        $this->client = $client->withHeaders(['Authorization' => config('app-version.clickup.api_token')])
            ->contentType('application/json')
            ->accept('application/json')
            ->baseUrl(rtrim(config('app-version.clickup.base_url', '/'), '/'));
    }

    /**
     * @throws BadResponseException
     */
    public function commentTask(array $task, string $message): void
    {
        $url = sprintf(
            '/task/%s/comment%s',
            $task['id'],
            !empty($team) ? '?custom_task_ids=true&team_id=' . $task['team'] : ''
        );

        $this->post($url, ['comment_text' => $message]);
    }

    /**
     * @throws BadResponseException
     */
    public function post(string $url, array $data = []): Response
    {
        $response = $this->client->timeout(10)->post($url, $data);

        if (!$response->successful()) {
            throw BadResponseException::forUnsuccessfulResponse($response->reason());
        }

        return $response;
    }
}
