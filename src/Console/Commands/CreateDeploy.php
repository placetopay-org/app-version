<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Illuminate\Config\Repository;
use Illuminate\Console\Command;
use PlacetoPay\AppVersion\Sentry\Exceptions\BadResponseCode;
use PlacetoPay\AppVersion\Sentry\SentryApi;

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
    protected $description = 'Creates a new Sentry deploy';

    /**
     * @param \Illuminate\Config\Repository $config
     * @param \PlacetoPay\AppVersion\Sentry\SentryApi $sentry
     * @return int
     */
    public function handle(Repository $config, SentryApi $sentry)
    {
        try {
            $sentry->createDeploy(
                $config->get('app-version.version'),
                $config->get('app.env'),
                );
        } catch (BadResponseCode $e) {
            $this->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
