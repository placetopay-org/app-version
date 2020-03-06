<?php

namespace PlacetoPay\AppVersion\Http\Controllers;

use Illuminate\Routing\Controller;

class VersionController extends Controller
{
    protected function envoyerVersionFile()
    {
        return storage_path('version.txt');
    }
    
    protected function envoyerVersionHook()
    {
        return file_exists($this->envoyerVersionFile());
    }
    
    protected function parseEnvoyerText()
    {
        $content = file_get_contents($this->envoyerVersionFile());
        $data = explode("\n", $content);
        
        return [
            'hash' => $data[0],
            'branch' => $data[2],
            'release' => $data[1],
            'date' => date('c'),
        ];
    }

    public function version()
    {
        if ($this->envoyerVersionHook()) {
            return response()
                ->json($this->parseEnvoyerText())->withHeaders([
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