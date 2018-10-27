<?php

namespace PlacetoPay\AppVersion\Http\Controllers;

use Illuminate\Routing\Controller;

class VersionController extends Controller
{

    public function version()
    {
        return [
            'hash' => exec('git rev-parse HEAD'),
            'version' => exec('git describe --tags'),
            'branch' => exec('git symbolic-ref -q --short HEAD'),
            'date' => date('c'),
        ];
    }

}