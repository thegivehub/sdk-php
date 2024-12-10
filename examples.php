<?php
require_once 'vendor/autoload.php';

use GiveHub\GiveHubSDK;

// Initialize the SDK
$givehub = new GiveHubSDK([
    'baseUrl' => 'https://api.thegivehub.com',
    'version' => 'v1',
    'apiKey' => 'your-api-key'
]);

// Authentication Examples
try {
    // Login
    $loginResult = $givehub->auth->login('user@example.com', 'password123');
    echo "Logged in as: " . $loginResult['user']['username'] . "\n";

    // Register new user
    $userData = [
        'email' => 'newuser@example.com',
        'password' => 'securepass123',
        'firstName' => 'John',
        'lastName' => 'Doe'
    ];
    $givehub->auth->register($userData);
} catch (GiveHubException $e) {
    echo "Auth error: " . $e->getMessage() . "\n";
}

// Campaign Examples
try {
    // Create campaign
    $campaign = $givehub->campaigns->create([
        'title' => 'Clean Water Project',
        'description' => 'Providing clean water access to remote communities',
        'targetAmount' => 50000,
        'category' => 'water',
        'milestones' => [
            [
                'description' => 'Phase 1: Survey',
                'amount' => 10000
            ]
        ]
    ]);

    // Upload campaign media
    $mediaResult = $givehub->campaigns->uploadMedia(
        $campaign['id'],
        '/path/to/campaign-photo.jpg'
    );

    // Get campaign list
    $campaigns = $givehub->campaigns->list([
        'category' => 'water',
        'status' => 'active',
        'page' => 1,
        'limit' => 10
    ]);
} catch (GiveHubException $e) {
    echo "Campaign error: " . $e->getMessage() . "\n";
}

// Donation Examples
try {
    // Create one-time donation
    $donation = $givehub->donations->create([
        'campaignId' => 'campaign-id',
        'amount' => [
            'value' => 100,
            'currency' => 'USD'
        ],
        'type' => 'one-time'
    ]);

    // Create recurring donation
    $recurring = $givehub->donations->createRecurring([
        'campaignId' => 'campaign-id',
        'amount' => [
            'value' => 50,
            'currency' => 'USD'
        ],
        'frequency' => 'monthly'
    ]);

    // Get donation history
    $donations = $givehub->donations->getDonations([
        'campaignId' => 'campaign-id',
        'status' => 'completed'
    ]);
} catch (GiveHubException $e) {
    echo "Donation error: " . $e->getMessage() . "\n";
}

// Impact Tracking Examples
try {
    // Create impact metrics
    $metrics = $givehub->impact->createMetrics('campaign-id', [
        'metrics' => [
            [
                'name' => 'People Helped',
                'value' => 500,
                'unit' => 'individuals'
            ],
            [
                'name' => 'Water Access',
                'value' => 1000,
                'unit' => 'liters/day'
            ]
        ]
    ]);

    // Get impact metrics
    $impact = $givehub->impact->getMetrics('campaign-id', [
        'from' => '2024-01-01',
        'to' => '2024-12-31'
    ]);
} catch (GiveHubException $e) {
    echo "Impact error: " . $e->getMessage() . "\n";
}

// Update Examples
try {
    // Create campaign update
    $update = $givehub->updates->create([
        'campaignId' => 'campaign-id',
        'title' => 'Construction Progress',
        'content' => 'We have completed the first phase of well construction.',
        'type' => 'milestone'
    ]);

    // Upload update media
    $mediaResult = $givehub->updates->uploadMedia(
        $update['id'],
        '/path/to/progress-photo.jpg'
    );

    // Get updates
    $updates = $givehub->updates->getUpdates([
        'campaignId' => 'campaign-id',
        'type' => 'milestone'
    ]);
} catch (GiveHubException $e) {
    echo "Update error: " . $e->getMessage() . "\n";
}

// Notification Examples
try {
    // Get notifications
    $notifications = $givehub->notifications->getNotifications([
        'status' => 'unread'
    ]);

    // Mark notification as read
    $givehub->notifications->markAsRead('notification-id');
} catch (GiveHubException $e) {
    echo "Update error: " . $e->getMessage() . "\n";
}


