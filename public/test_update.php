<?php

$data = [
    'cron' => '0 15 * * *',
    'timezone' => 'Asia/Baghdad',
    'max_storage_mb' => '50000',
    'include_files' => 'true',
    'multi_db' => 'false',
    'keep_daily_days' => '7',
    'keep_weekly_weeks' => '4',
    'keep_monthly_months' => '6',
    'keep_yearly_years' => '10',
    'disk' => 'local',
    'drive_folder' => 'Backups',
    'temp_link_expiry' => '60',
    'checksum_enabled' => 'true',
    'notify_enabled' => 'false',
    'notify_on' => 'both',
    'telegram_enabled' => 'true',
    'email_enabled' => 'true',
    'webhook_enabled' => 'true',
    'emails' => 'admin@example.com',
    'telegram_bot_token' => '7806251613:AAEcedPSxTCib13YFagNqhasr01l6nGrQq8',
    'stale_hours' => '48',
];

$options = [
    'http' => [
        'header'  => "Content-type: application/json\r\nAccept: application/json\r\n",
        'method'  => 'PUT',
        'content' => json_encode($data)
    ]
];

$context  = stream_context_create($options);
$result = file_get_contents('http://localhost/api/v1/backup/settings', false, $context);

echo "Response:\n";
echo $result;
echo "\n\n";

if ($result === FALSE) {
    echo "Error occurred!\n";
} else {
    echo "Success!\n";
    $response = json_decode($result, true);
    print_r($response);
}
