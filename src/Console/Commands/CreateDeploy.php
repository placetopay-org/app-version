<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use PlacetoPay\AppVersion\Helpers\ApiFactory;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;

class CreateDeploy extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app-version:create-deploy';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new deploy on the available sources';

    /**
     * @param Repository $config
     * @return int
     */
    public function handle(Repository $config): int
    {
        try {
            $appVersion = $config->get('app-version.version.sha');

            if ($appVersion) {
                $this->sentryDeploy($config, $appVersion);
                $this->newrelicDeploy($config, $appVersion);
            }
        } catch (BadResponseCode $e) {
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * @param Repository $config
     * @param string $version
     * @throws BadResponseCode
     */
    private function sentryDeploy(Repository $config, string $version): void
    {
        $authToken = $config->get('app-version.sentry.auth_token');
        $organization = $config->get('app-version.sentry.organization');

        if ($authToken && $organization) {
            $sentry = ApiFactory::sentryApi();
            $sentry->createDeploy(
                $version,
                $config->get('app.env')
            );
        }
    }

    private function newrelicDeploy(Repository $config, string $version): void
    {
        $apiKey = $config->get('app-version.newrelic.api_key');
        $applicationId = $config->get('app-version.newrelic.application_id');
        if ($apiKey && $applicationId) {
            $newrelic = ApiFactory::newRelicApi();
            $newrelic->createDeploy(
                $version,
                $config->get('app.env')
            );
        }
    }
}
