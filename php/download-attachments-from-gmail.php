<?php
require 'vendor/autoload.php';

use Google\Client;
use Google\Service\Gmail;

define('CREDENTIALS_PATH', __DIR__ . '/credentials.json');
define('DOWNLOAD_PATH', __DIR__ . '/downloads-' . date('Y-m-d') . '/');

// Ensure the download directory exists
if (!file_exists(DOWNLOAD_PATH)) {
    mkdir(DOWNLOAD_PATH, 0777, true);
}

// Authenticate with Gmail API
function getClient() {
    $client = new Client();
    $client->setApplicationName('Gmail Attachment Downloader');
    $client->setScopes([Gmail::MAIL_GOOGLE_COM]);
    $client->setAuthConfig(CREDENTIALS_PATH);
    $client->setAccessType('offline');
    $client->setRedirectUri('http://localhost');

    $tokenPath = __DIR__ . '/token.json';
    if (!file_exists($tokenPath)) {
        $accessToken = $client->fetchAccessTokenWithAuthCode("put-access-code-here");
        if (isset($accessToken['error'])) {
            throw new Exception("Error fetching access token: " . json_encode($accessToken));
        }

        $client->setAccessToken($accessToken);

        // Save the token
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }

        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }

    if (file_exists($tokenPath)) {
        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $client->setAccessToken($accessToken);
    }

    // If there is no previous token or it's expired.
    if ($client->isAccessTokenExpired()) {
        // Refresh the token if possible, else fetch a new one.
        if ($client->getRefreshToken()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
        } else {
            // Request authorization from the user.
            $authUrl = $client->createAuthUrl();
            printf("Open the following link in your browser:\n%s\n", $authUrl);
            print 'Enter verification code: ';
            $authCode = trim(fgets(STDIN));

            // Exchange authorization code for an access token.
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $client->setAccessToken($accessToken);

            // Check to see if there was an error.
            if (array_key_exists('error', $accessToken)) {
                throw new Exception(join(', ', $accessToken));
            }
        }
        // Save the token to a file.
        if (!file_exists(dirname($tokenPath))) {
            mkdir(dirname($tokenPath), 0700, true);
        }
        file_put_contents($tokenPath, json_encode($client->getAccessToken()));
    }
    return $client;
}

// Fetch unread emails with attachments
function fetchUnreadEmails($service) {
    $messages = $service->users_messages->listUsersMessages('me', [
        'q' => 'is:unread has:attachment'
    ]);

    return $messages->getMessages() ?: [];
}

// Download attachments from email
function downloadAttachments($service, $messageId) {
    $message = $service->users_messages->get('me', $messageId);
    $parts = $message->getPayload()->getParts();

    if (!$parts) return;

    foreach ($parts as $part) {
        // Get the filename of the attachment
        $filename = $part->getFilename();

        // Check if the filename exists, the body has content, and the attachmentId is present
        if ($filename && $part->getBody() && $part->getBody()->getAttachmentId()) {
            // Only download PDF or DOCX files
            if (preg_match('/\.(pdf|docx)$/i', $filename)) {
                $attachmentId = $part->getBody()->getAttachmentId();
                $attachment = $service->users_messages_attachments->get('me', $messageId, $attachmentId);
                $data = base64_decode(strtr($attachment->getData(), '-_', '+/'));

                // Save the file to the specified path
                $filePath = DOWNLOAD_PATH . $filename;
                file_put_contents($filePath, $data);

                echo "✅ Downloaded: {$filename}\n";
            }
        }
    }
}

// Mark email as read
function markAsRead($service, $messageId) {
    $mods = new Google_Service_Gmail_ModifyMessageRequest();
    $mods->setRemoveLabelIds(['UNREAD']);
    $service->users_messages->modify('me', $messageId, $mods);
    echo "📩 Marked email as read\n";
}

$client = getClient();
$service = new Gmail($client);

$emails = fetchUnreadEmails($service);
$totalEmails = count($emails);
if ($totalEmails === 0) {
    echo "📭 No unread emails with attachments found.\n";
    exit;
}

echo "📥 Found $totalEmails unread emails with attachments.\n";

foreach ($emails as $index => $email) {
    $messageId = $email->getId();
    echo "📌 Processing email " . ($index + 1) . " of $totalEmails...\n";

    downloadAttachments($service, $messageId);
    markAsRead($service, $messageId);

    echo "-------------------------------\n";
}

echo "✅ All emails processed.\n";
