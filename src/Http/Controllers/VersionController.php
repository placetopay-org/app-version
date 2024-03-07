<?php

declare(strict_types=1);

namespace PlacetoPay\AppVersion\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use PlacetoPay\AppVersion\VersionFile;

class VersionController extends Controller
{
    public function version(Request $request): JsonResponse
    {
        if ($request->get('token') !== config('app-version.token')) {
            abort(404);
        }

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
