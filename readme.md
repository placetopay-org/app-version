# Application information

This composer package offers a Twitter Bootstrap optimized flash messaging setup for your Laravel applications.

## Installation

First add the private repository information on `composer.json`

```json
{
  "repositories": [{
    "type": "composer",
    "url": "https://dev.placetopay.com/repository"
  }]
}
```

Begin by pulling in the package through Composer.

```bash
composer require placetopay/app-version
```

## Usage

Once the package is installed the `/version` will be available to check the current git version

## Envoyer

With the new deploys made with Envoyer now the projects does not have git available, so you need to create a deployment hook

```
cd {{ release }}
echo {{ sha }} >> storage/version.txt
echo {{ time }} >> storage/version.txt
echo {{ branch }} >> storage/version.txt
```