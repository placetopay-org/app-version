<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PlacetoPay\AppVersion\Helpers\ApiFactory;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;
use Illuminate\Validation\Validator as DataValidator;
class CreateDeploy extends Command
{
    private const GENERAL = 'General';
    private const NEWRELIC = 'Newrelic';
    private const SENTRY = 'Sentry';
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
            if (!$this->isValidGeneralData($config)) {
                return 0;
            }
            $appVersion = $config->get('app-version.version.sha');

            if ($this->isValidSentryConfigurationData($config)) {
                $this->sentryDeploy($config, $appVersion);
            }

            if ($this->isValidNewRelicConfigurationData($config)) {
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
        $sentry = ApiFactory::sentryApi();
        $sentry->createDeploy(
            $version,
            $config->get('app.env')
        );
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
    }

    private function isValidNewRelicConfigurationData(Repository $config): bool
    {
        $validator = Validator::make([
            'newrelic.api_key' => $config->get('app-version.newrelic.api_key'),
            'newrelic.entity_guid' => $config->get('app-version.newrelic.entity_guid'),
        ], [
            'newrelic.api_key' => 'required|string',
            'newrelic.entity_guid' => 'required|string',
        ]);

        return $this->validate(self::NEWRELIC, $validator);

    }

    private function isValidSentryConfigurationData(Repository $config): bool
    {
        $validator = Validator::make([
            'sentry.auth_token' => $config->get('app-version.sentry.auth_token'),
            'sentry.organization' => $config->get('app-version.sentry.organization'),
        ], [
            'sentry.auth_token' => 'required|string',
            'sentry.organization' => 'required|string',
        ]);

        return $this->validate(self::SENTRY, $validator);
    }

    private function isValidGeneralData(Repository $config): bool
    {
        $validator = Validator::make([
            'version.sha' => $config->get('app-version.version.sha'),
        ], [
            'version.sha' => 'required|string',
        ]);

        return $this->validate(self::GENERAL, $validator);
    }

    private function validate(string $deployType, DataValidator $validator): bool
    {
        try {
            $validator->validate();
        } catch (ValidationException $e) {
            $this->error("$deployType configuration is not valid: \n" . implode("\n", $validator->errors()->all()));
            return false;
        }

        return true;
    }
}
