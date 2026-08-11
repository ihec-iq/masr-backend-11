<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use Google_Client;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;
use Illuminate\Http\Request;

class GoogleDriveController extends Controller
{
    public function uploadToGoogleDrive($file)
    {
        // Instantiate the Google Drive client
        $client = new Google_Client();
        $client->setAuthConfig(public_path('storage/client_secret.json'));
        $client->setScopes([
            Google_Service_Drive::DRIVE_FILE,
            Google_Service_Drive::DRIVE,
        ]);
        $client->setClientId(config('google.client_id'));
        $client->setClientSecret(config('google.client_secret'));
        $client->refreshToken(config('google.refresh_token'));
        // Create the Google Drive service
        $drive = new Google_Service_Drive($client);

        // Create a new file on Google Drive
        $driveFile = new Google_Service_Drive_DriveFile([
            'name' => $file->getClientOriginalName(),
            'parents' => ['1BHyI2-DdswjXU_f46krypxtG6iCOvhDF'],

        ]);

        // Set the file content
        $response = $drive->files->create(
            $driveFile,
            [
                'data' => file_get_contents($file->getPathname()),
                'mimeType' => $file->getClientMimeType(),
                'uploadType' => 'multipart',
            ]
        );

        // Get the URL of the file
        $fileId = $response->getId();
        $fileUp = $drive->files->get($fileId, ['fields' => 'webContentLink']);
        // print_r($drive->files->get($fileId,['fields' => 'id,name,webContentLink']));
        $fileUrl = $fileUp->getWebContentLink();
        $extension = $file->getClientOriginalExtension();
        if ($extension == 'xls' || $extension == 'xlsx') {
            $fileUrlOnline = "https://docs.google.com/spreadsheets/d/{$fileId}/edit";
        } elseif ($extension == 'doc' || $extension == 'docx') {
            $fileUrlOnline = "https://docs.google.com/document/d/{$fileId}/edit";
        } else {
            $fileUrlOnline = '...';
        }
        // Return the file URL or any other response as needed
        return response()->json([
            'file_url' => str_replace('&export=download', '', $fileUrl),
            'fileUrlOnline' => $fileUrlOnline,
            'file_id' => $fileId,
            'extension' => $extension,
        ]);
    }

    public function handleUpload(Request $request)
    {
        $file = $request->file('excel_file');

        // Call the method to upload the file to Google Drive
        return $this->uploadToGoogleDrive($file);

        // Do something with the file ID or handle the response as needed
    }
}
