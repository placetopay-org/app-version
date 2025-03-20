<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PlacetoPay\AppVersion\Helpers\ApiFactory;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;

class CreateDeploy extends Command
{
    private const GENERAL = 'GENERAL CONFIGURATION';
    private const NEWRELIC = 'NEWRELIC';
    private const SENTRY = 'SENTRY';

    private const RULES = [
        self::GENERAL => [
            'version.sha' => 'required|string',
        ],
        self::SENTRY => [
            'sentry.auth_token' => 'required|string',
            'sentry.organization' => 'required|string',
        ],
        self::NEWRELIC => [
            'newrelic.api_key' => 'required|string',
            'newrelic.entity_guid' => 'required|string',
        ],
    ];

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

    public function handle(Repository $config): int
    {
        try {
            $appVersion = $config->get('app-version');

            if (!$this->validateData(self::GENERAL, $appVersion)) {
                $this->info('you must execute app-version:create command before');
                return 0;
            }

            $sha = $appVersion['version']['sha'];
            if ($this->validateData(self::SENTRY, $appVersion)) {
                $this->sentryDeploy($config, $sha);
            }

            if ($this->validateData(self::NEWRELIC, $appVersion)) {
                $this->newrelicDeploy($config, $sha);
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
        $sentry = ApiFactory::sentryApi();
        $sentry->createDeploy(
            $version,
            $config->get('app.env')
        );

        $this->comment('[SENTRY DEPLOY] Deploy created successfully');
    }

    /**
     * @throws BadResponseCode
     */
    private function newrelicDeploy(Repository $config, string $version): void
    {
        $newrelic = ApiFactory::newRelicApi();
        $newrelic->createDeploy(
            $version,
            $config->get('app.env')
        );

        $this->comment('[NEWRELIC DEPLOY] Deploy created successfully');
    }

    private function validateData(string $type, array $data): bool
    {
        if (!$rules = self::RULES[$type]) {
            return true;
        }

        $validator = Validator::make($data, $rules);

        try {
            $validator->validate();
        } catch (ValidationException $e) {
            $this->warn(
                "[$type DEPLOY] configuration is not valid:\n\t- "
                . implode("\n\t- ", $validator->errors()->all())
            );
            return false;
        }

        return true;
    }
}
