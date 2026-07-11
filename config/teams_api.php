<?php
// config/teams_api.php - Microsoft Azure AD App Credentials
// Register an app in Azure Entra ID and grant Application permission: OnlineMeetings.ReadWrite.All

define('MS_TEAMS_TENANT_ID', 'YOUR_TENANT_ID');
define('MS_TEAMS_CLIENT_ID', 'YOUR_CLIENT_ID');
define('MS_TEAMS_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');

// The immutable User ID (Object ID) in Azure AD that will host the platform-generated meetings.
// It's recommended to use a centralized platform service worker account for this.
define('MS_TEAMS_HOST_USER_ID', 'YOUR_PLATFORM_SERVICE_USER_ID');
