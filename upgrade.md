# Upgrade Guide to v3.2.0

This release includes optional support for posting comments in ClickUp using the `app-version:notify-clickup` command.

## ✅ Required Actions
Add the Clickup configuration to your app version configuration file:

   ```
   # config/app-version.php

   'clickup' => [
      'base_url' => env('CLICKUP_BASE_URL', 'https://api.clickup.com/api/v2'),
      'api_token' => env('CLICKUP_API_TOKEN'),
   ],

   'changelog_file_name' => env('CHANGELOG_FILE', 'CHANGELOG.md'),
   ```

To use this feature, add the following environment variables:

- `CLICKUP_API_TOKEN=YOUR-CLICKUP-API-TOKEN`
- `CHANGELOG_FILE=YOUR-CHANGELOG-FILE-NAME` (if needed)

If you don't need this functionality, no action is required.

# Upgrade Guide to v3.0.0

Released: 2025-03-25

## Overview

Version `3.0.0` introduces a breaking change: the SDK now integrates with the New Relic GraphQL API instead of the legacy REST API. While the public interface of the SDK remains the same, the required environment variables have changed.

---

## ⚠️ Breaking Changes

- **REMOVED:** The environment variable `APP_VERSION_NEWRELIC_APPLICATION_ID` has been removed.
- **REQUIRED:** A new variable `APP_VERSION_NEWRELIC_ENTITY_GUID` is now required.

---

## ✅ Required Actions

1. **Remove** the old variable from your environment:

   ```
   # .env
   APP_VERSION_NEWRELIC_APPLICATION_ID=YOUR_APPLICATION_ID
   ```

2. **Add** the new GraphQL API key:

   ```
   # .env
   APP_VERSION_NEWRELIC_ENTITY_GUID=YOUR_ENTITY_GUID
   ```

3. Change your app-version configuration file

   Remove the old newrelic application id from your configuration file and add the newrelic entity guid:
   ```
   # config/app-version.php
 
   'newrelic' => [
      'api_key' => env('APP_VERSION_NEWRELIC_API_KEY'),

      'application_id' => env('APP_VERSION_NEWRELIC_APPLICATION_ID'), // Remove this line
      'entity_guid' => env('APP_VERSION_NEWRELIC_ENTITY_GUID'), // Add this line
    ],
    ```

## 🔍 How to find your New Relic Entity GUID
The entityGuid value is the unique identifier assigned by New Relic to your system components during instrumentation and setup processes.

You may need to set this value manually in your environment or configuration.

To locate your New Relic entity GUID, follow this official guide: [What is an Entity in New Relic?](https://docs.newrelic.com/docs/new-relic-solutions/new-relic-one/core-concepts/what-entity-new-relic/)