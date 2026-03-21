<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Platform domain
    |--------------------------------------------------------------------------
    | The root domain used to extract the tenant slug from the subdomain.
    | e.g. "cms.inte.team" → {slug}.cms.inte.team
    */
    'domain' => env('CMS_DOMAIN', 'cms.inte.team'),

    /*
    |--------------------------------------------------------------------------
    | Media disk
    |--------------------------------------------------------------------------
    | The filesystem disk used for uploaded media files.
    | "local" in dev; "s3" or "gcs" in production.
    */
    'media_disk' => env('CMS_MEDIA_DISK', 'local'),

];
