<?php

namespace PlacetoPay\AppVersion\Helpers;

use PlacetoPay\AppVersion\Sentry\SentryApi;

class ApiFactory
{
    public static function sentryApi(): SentryApi
    {
        return app(SentryApi::class);
    }

    public function newRelicApi()
    {
    }
}
