<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Drive Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Drive integration including credentials,
    | refresh tokens, and folder settings.
    |
    */

    'client_id' => env('GOOGLE_DRIVE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_DRIVE_CLIENT_SECRET'),
    'refresh_token' => env('GOOGLE_DRIVE_REFRESH_TOKEN'),
    'folder_id' => env('GOOGLE_DRIVE_FOLDER_ID'),

    /*
    |--------------------------------------------------------------------------
    | Google Drive API Settings
    |--------------------------------------------------------------------------
    */

    'team_drive_id' => env('GOOGLE_DRIVE_TEAM_DRIVE_ID'),
    
    'scopes' => [
        'https://www.googleapis.com/auth/drive',
        'https://www.googleapis.com/auth/drive.file',
    ],

];
