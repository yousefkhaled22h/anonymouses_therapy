<?php
// api/teams_create.php
require_once __DIR__ . '/../config/teams_api.php';

/**
 * Creates a Microsoft Teams online meeting via Microsoft Graph API.
 * Uses App-Only (Daemon) OAuth2 flow.
 * 
 * @param string $startDateTime ISO8601 format (e.g. 2026-04-19T14:30:00Z)
 * @param string $endDateTime ISO8601 format (e.g. 2026-04-19T15:30:00Z)
 * @param string $subject The name of the meeting
 * @return array { 'success' => bool, 'joinUrl' => string, 'meetingId' => string, 'error' => string }
 */
function createTeamsMeeting($startDateTime, $endDateTime, $subject = "Safe Haven Therapy Session") {
    // 1. Check if configured
    if (MS_TEAMS_TENANT_ID === 'YOUR_TENANT_ID' || empty(MS_TEAMS_TENANT_ID)) {
        // Fallback or mock behavior when not configured
        return [
            'success' => true, 
            'joinUrl' => 'https://teams.microsoft.com/mock-meeting-' . uniqid(), 
            'meetingId' => 'MOCK-' . uniqid()
        ];
    }

    // 2. Fetch Access Token via Client Credentials
    $tokenUrl = "https://login.microsoftonline.com/" . MS_TEAMS_TENANT_ID . "/oauth2/v2.0/token";
    $tokenData = [
        'client_id' => MS_TEAMS_CLIENT_ID,
        'client_secret' => MS_TEAMS_CLIENT_SECRET,
        'scope' => 'https://graph.microsoft.com/.default',
        'grant_type' => 'client_credentials'
    ];

    $ch = curl_init($tokenUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenData));
    $tokenResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$tokenResponse) {
        return ['success' => false, 'error' => 'Failed to obtain MS Graph access token'];
    }

    $tokenJson = json_decode($tokenResponse, true);
    $accessToken = $tokenJson['access_token'] ?? '';

    if (empty($accessToken)) {
        return ['success' => false, 'error' => 'Invalid token response from Azure AD'];
    }

    // 3. Create Online Meeting via Graph
    $apiUrl = "https://graph.microsoft.com/v1.0/users/" . MS_TEAMS_HOST_USER_ID . "/onlineMeetings";
    
    $meetingPayload = [
        'startDateTime' => $startDateTime,
        'endDateTime' => $endDateTime,
        'subject' => $subject,
        'lobbyBypassSettings' => [
            'scope' => 'everyone',
            'isDialInBypassEnabled' => true
        ]
    ];

    $ch2 = curl_init($apiUrl);
    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch2, CURLOPT_POST, true);
    curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($meetingPayload));
    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $accessToken,
        'Content-Type: application/json'
    ]);
    
    $meetingResponse = curl_exec($ch2);
    $meetCode = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
    curl_close($ch2);

    if ($meetCode >= 200 && $meetCode < 300) {
        $meetingJson = json_decode($meetingResponse, true);
        return [
            'success' => true,
            'joinUrl' => $meetingJson['joinWebUrl'] ?? '',
            'meetingId' => $meetingJson['id'] ?? ''
        ];
    }

    return ['success' => false, 'error' => 'Graph API returned code ' . $meetCode . ': ' . $meetingResponse];
}
