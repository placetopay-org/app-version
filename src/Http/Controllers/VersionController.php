<?php

namespace PlacetoPay\AppVersion\Http\Controllers;

use Illuminate\Routing\Controller;
use PlacetoPay\AppVersion\VersionFile;

class VersionController extends Controller
{
    public function version()
    {
        if (VersionFile::exists()) {
            return response()
                ->json(VersionFile::read())
                ->withHeaders([
                    'Access-Control-Allow-Origin' => '*',
                    'Access-Control-Allow-Methods' => 'GET',
                ]);
        }

        return response()
            ->json([
                'hash' => exec('git rev-parse HEAD'),
                'version' => exec('git describe --tags'),
                'branch' => exec('git symbolic-ref -q --short HEAD'),
                'date' => date('c'),
            ])->withHeaders([
                'Access-Control-Allow-Origin' => '*',
                'Access-Control-Allow-Methods' => 'GET',
            ]);
    }
}
