# The Give Hub PHP SDK Documentation

## Installation

### Requirements
- PHP 7.4 or higher
- Composer
- ext-curl
- ext-json

### Via Composer

```bash
composer require givehub/sdk
```

## Basic Usage

```php
use GiveHub\GiveHubSDK;

// Initialize the SDK
$givehub = new GiveHubSDK([
    'baseUrl' => 'https://api.thegivehub.com',
    'version' => 'v1',
    'apiKey' => 'your-api-key'
]);
```

## Authentication

### Login
```php
try {
    $result = $givehub->auth->login('user@example.com', 'password123');
    echo "Logged in as: " . $result['user']['username'];
} catch (GiveHubException $e) {
    echo "Login failed: " . $e->getMessage();
}
```

### Register New User
```php
try {
    $userData = [
        'email' => 'newuser@example.com',
        'password' => 'secure123',
        'firstName' => 'John',
        'lastName' => 'Doe'
    ];
    
    $result = $givehub->auth->register($userData);
    echo "Registration successful. User ID: " . $result['userId'];
} catch (GiveHubException $e) {
    echo "Registration failed: " . $e->getMessage();
}
```

### Email Verification
```php
try {
    $result = $givehub->auth->verifyEmail('user@example.com', '123456');
    echo "Email verified successfully";
} catch (GiveHubException $e) {
    echo "Verification failed: " . $e->getMessage();
}
```

## Campaign Management

### Create Campaign
```php
try {
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
    
    echo "Campaign created with ID: " . $campaign['id'];
} catch (GiveHubException $e) {
    echo "Failed to create campaign: " . $e->getMessage();
}
```

### Get Campaign Details
```php
try {
    $campaign = $givehub->campaigns->get('campaign-id');
    echo "Campaign title: " . $campaign['title'];
} catch (GiveHubException $e) {
    echo "Failed to get campaign: " . $e->getMessage();
}
```

### List Campaigns
```php
try {
    $campaigns = $givehub->campaigns->list([
        'category' => 'water',
        'status' => 'active',
        'page' => 1,
        'limit' => 10
    ]);
    
    foreach ($campaigns['data'] as $campaign) {
        echo $campaign['title'] . "\n";
    }
} catch (GiveHubException $e) {
    echo "Failed to list campaigns: " . $e->getMessage();
}
```

### Upload Campaign Media
```php
try {
    $result = $givehub->campaigns->uploadMedia(
        'campaign-id',
        '/path/to/image.jpg'
    );
    echo "Media uploaded successfully";
} catch (GiveHubException $e) {
    echo "Upload failed: " . $e->getMessage();
}
```

## Donations

### Process Donation
```php
try {
    $donation = $givehub->donations->create([
        'campaignId' => 'campaign-id',
        'amount' => [
            'value' => 100,
            'currency' => 'USD'
        ],
        'type' => 'one-time'
    ]);
    
    echo "Donation processed successfully";
} catch (GiveHubException $e) {
    echo "Donation failed: " . $e->getMessage();
}
```

### Create Recurring Donation
```php
try {
    $recurring = $givehub->donations->createRecurring([
        'campaignId' => 'campaign-id',
        'amount' => [
            'value' => 50,
            'currency' => 'USD'
        ],
        'frequency' => 'monthly'
    ]);
    
    echo "Recurring donation set up successfully";
} catch (GiveHubException $e) {
    echo "Failed to set up recurring donation: " . $e->getMessage();
}
```

## Impact Tracking

### Create Impact Metrics
```php
try {
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
    
    echo "Impact metrics created successfully";
} catch (GiveHubException $e) {
    echo "Failed to create metrics: " . $e->getMessage();
}
```

### Update Impact Metrics
```php
try {
    $result = $givehub->impact->updateMetrics('metric-id', [
        'value' => 600,
        'verificationMethod' => 'survey'
    ]);
    
    echo "Metrics updated successfully";
} catch (GiveHubException $e) {
    echo "Failed to update metrics: " . $e->getMessage();
}
```

## Campaign Updates

### Create Update
```php
try {
    $update = $givehub->updates->create([
        'campaignId' => 'campaign-id',
        'title' => 'Construction Progress',
        'content' => 'First phase completed successfully',
        'type' => 'milestone'
    ]);
    
    echo "Update posted successfully";
} catch (GiveHubException $e) {
    echo "Failed to post update: " . $e->getMessage();
}
```

## Advanced Usage Examples

### Campaign Manager Class
```php
class CampaignManager {
    private $givehub;
    
    public function __construct(GiveHubSDK $givehub) {
        $this->givehub = $givehub;
    }
    
    public function createCampaignWithMilestones(array $data, array $milestones) {
        try {
            // Create campaign
            $campaign = $this->givehub->campaigns->create([
                'title' => $data['title'],
                'description' => $data['description'],
                'targetAmount' => $data['targetAmount'],
                'category' => $data['category'],
                'milestones' => $milestones
            ]);
            
            // Upload media if provided
            if (isset($data['mediaPath'])) {
                $this->givehub->campaigns->uploadMedia(
                    $campaign['id'],
                    $data['mediaPath']
                );
            }
            
            return $campaign;
        } catch (GiveHubException $e) {
            error_log("Campaign creation failed: " . $e->getMessage());
            throw $e;
        }
    }
}
```

### Impact Tracker Class
```php
class ImpactTracker {
    private $givehub;
    
    public function __construct(GiveHubSDK $givehub) {
        $this->givehub = $givehub;
    }
    
    public function trackProgress(string $campaignId, array $metrics) {
        try {
            // Update metrics
            $result = $this->givehub->impact->updateMetrics(
                $campaignId,
                ['metrics' => $metrics]
            );
            
            // Create update
            $this->createImpactUpdate($campaignId, $metrics);
            
            return $result;
        } catch (GiveHubException $e) {
            error_log("Impact tracking failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createImpactUpdate($campaignId, $metrics) {
        $content = "Impact Update:\n\n";
        foreach ($metrics as $metric) {
            $content .= "- {$metric['name']}: {$metric['value']} {$metric['unit']}\n";
        }
        
        return $this->givehub->updates->create([
            'campaignId' => $campaignId,
            'title' => 'Impact Metrics Updated',
            'content' => $content,
            'type' => 'impact'
        ]);
    }
}
```

## Error Handling

The SDK throws `GiveHubException` for all errors. Example error handling:

```php
try {
    $result = $givehub->campaigns->create($campaignData);
} catch (GiveHubException $e) {
    switch ($e->getCode()) {
        case 401:
            // Handle authentication error
            break;
        case 400:
            // Handle validation error
            break;
        default:
            // Handle other errors
            break;
    }
    
    error_log("API Error: " . $e->getMessage());
}
```

## Configuration Options

```php
$config = [
    'baseUrl' => 'https://api.thegivehub.com',  // API base URL
    'version' => 'v1',                          // API version
    'apiKey' => 'your-api-key',                // Your API key
    'timeout' => 30,                           // Request timeout in seconds
    'verify_ssl' => true,                      // Verify SSL certificates
    'debug' => false                           // Enable debug mode
];
```

## Best Practices

1. **Error Handling**: Always wrap SDK calls in try-catch blocks
2. **Logging**: Implement proper error logging
3. **Configuration**: Store sensitive configuration in environment variables
4. **Validation**: Validate data before making API calls
5. **Resource Management**: Close connections and free resources properly

## Debugging

Enable debug mode to see detailed logs:

```php
$givehub = new GiveHubSDK([
    'debug' => true,
    // other config...
]);
```

## Support
- Documentation: https://docs.givehub.com
- GitHub Issues: https://github.com/givehub/sdk-php/issues
- Email: support@givehub.com
