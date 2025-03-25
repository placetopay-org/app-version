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
   - APP_VERSION_NEWRELIC_APPLICATION_ID=YOUR_APPLICATION_ID
   ```

2. **Add** the new GraphQL API key:

   ```
   # .env
   - APP_VERSION_NEWRELIC_ENTITY_GUID=YOUR_ENTITY_GUID
   ```

3. Change your newrelic configuration file

   Remove the old newrelic application id from your configuration file and add the newrelic entity guid:
 ```
 
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