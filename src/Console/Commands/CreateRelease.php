<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use PlacetoPay\AppVersion\Helpers\ApiFactory;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;

class CreateRelease extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app-version:create-release';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates a new release';

    public function handle(Repository $config): int
    {
        try {
            $sentry = ApiFactory::sentryApi();
            $sentry->createRelease(
                $config->get('app-version.version'),
                $config->get('app-version.sentry.repository'),
                $config->get('app-version.sentry.project')
            );
        } catch (BadResponseCode $e) {
            $this->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
