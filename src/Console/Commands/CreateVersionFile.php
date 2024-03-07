<?php

namespace PlacetoPay\AppVersion\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use PlacetoPay\AppVersion\VersionFile;

class CreateVersionFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app-version:create
                            {--sha= : Commit hash being deployed}
                            {--time= : Timestamp of the current deployment formatted as YmdHis}
                            {--branch= : Branch being deployed}
                            {--tag= : Tag being deployed}
                            ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates or updates the version file, file is stored as \'storage/app/app-version.json\'';

    public function handle(Factory $validator): int
    {
        try {
            $options = $this->validateOptions($validator);
        } catch (ValidationException $e) {
            $this->error($e->validator->errors()->first());

            return 1;
        }

        VersionFile::generate([
            'sha' => $options['sha'] ?? exec('git rev-parse HEAD'),
            'time' => $options['time'] ?? date('c'),
            'branch' => $options['branch'] ?? exec('git symbolic-ref -q --short HEAD'),
            'version' => $options['tag'] ?? exec('git describe --tags'),
        ]);

        return 0;
    }

    /**
     * @param Factory $validator
     * @return array
     * @throws ValidationException
     */
    private function validateOptions(Factory $validator): array
    {
        return $validator->make($this->options(), [
            'sha' => 'nullable',
            'time' => 'nullable',
            'project' => 'nullable',
            'branch' => 'nullable',
            'tag' => 'nullable',
        ])->validate();
    }
}
