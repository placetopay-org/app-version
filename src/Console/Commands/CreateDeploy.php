<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PlacetoPay\AppVersion\Exceptions\ChangelogException;
use PlacetoPay\AppVersion\Helpers\ApiFactory;
use PlacetoPay\AppVersion\Helpers\Logger;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;
use PlacetoPay\AppVersion\Sentry\Exceptions\InvalidData;
use Symfony\Component\Console\Command\Command as CommandStatus;

class CreateDeploy extends Command
{
    private const NEWRELIC = 'NEWRELIC';
    private const SENTRY = 'SENTRY';

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
            $versionSha = Arr::get($appVersion, 'version.sha', $appVersion['version'] ?? null);

            if (!$versionSha) {
                $this->error('You must execute app-version:create command before.');
                return CommandStatus::FAILURE;
            }

            if ($this->isValidData(
                self::SENTRY,
                ['sentry.auth_token' => 'required|string', 'sentry.organization' => 'required|string'],
                $appVersion
            )) {
                $this->sentryDeploy($config, $versionSha);
            }

            if ($this->isValidData(
                self::NEWRELIC,
                ['newrelic.api_key' => 'required|string', 'newrelic.entity_guid' => 'required|string'],
                $appVersion
            )) {
                $this->newrelicDeploy($config, $versionSha);
            }
        } catch (BadResponseCode $e) {
            Logger::error('Error creating deploy', ['exception' => $e]);
            $this->error($e->getMessage());
            return CommandStatus::FAILURE;
        }

        return CommandStatus::SUCCESS;
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

        $this->comment(self::SENTRY . ' deployment created successfully');
    }

    /**
     * @throws InvalidData
     * @throws BadResponseCode
     */
    private function newrelicDeploy(Repository $config, string $versionSha): void
    {
        $newrelic = ApiFactory::newRelicApi();
        $response = $newrelic->createDeploy(
            $versionSha,
            $config->get('app.env'),
            $config->get('app-version.changelog_file_name')
        );

        if (Arr::exists($response, 'errors')) {
            Logger::error('Error creating newrelic deployment', ['response' => $response]);
            throw new InvalidData('Error creating newrelic deployment');
        }

        $this->comment(self::NEWRELIC . ' deployment created successfully');
    }

    private function isValidData(string $type, array $rules, array $data): bool
    {
        $validator = Validator::make($data, $rules);

        try {
            $validator->validate();
        } catch (ValidationException $e) {
            $this->warn(
                "$type configuration is not valid:\n\t- "
                . implode("\n\t- ", $validator->errors()->all())
            );
            return false;
        }

        return true;
    }
}
