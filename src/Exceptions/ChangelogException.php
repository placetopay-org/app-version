<?php

namespace PlacetoPay\AppVersion\Exceptions;

use Exception;

class ChangelogException extends Exception
{
    public static function forNoDeployConfiguration(): self
    {
        return new self('Could not get commit or branch information from the deployment.');
    }

    public static function forDifferentBranches(): self
    {
        return new self('The deployment branch does not match the current branch.');
    }
}
