<?php

namespace PlacetoPay\AppVersion\Helpers;

use PlacetoPay\AppVersion\NewRelic\NewRelicApi;
use PlacetoPay\AppVersion\Sentry\SentryApi;

class ApiFactory
{
    public static function sentryApi(): SentryApi
    {
        return app(SentryApi::class);
    }

    public static function newRelicApi(): NewRelicApi
    {
        return app(NewRelicApi::class);
    }
}
