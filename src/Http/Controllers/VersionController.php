<?php

namespace PlacetoPay\AppVersion\Http\Controllers;

use Illuminate\Routing\Controller;

class VersionController extends Controller
{

    public function version()
    {
        return response()
            ->json([
                'hash' => exec('git rev-parse HEAD'),
                'version' => exec('git describe --tags'),
                'branch' => exec('git symbolic-ref -q --short HEAD'),
                'date' => date('c'),
            ])->withHeaders([
                'Access-Control-Allow-Origin' => '*'
            ]);
    }

}