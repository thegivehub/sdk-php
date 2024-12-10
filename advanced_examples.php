<?php
// Advanced Usage Examples

/**
 * Campaign Management Class Example
 */
class CampaignManager {
    private $givehub;
    
    public function __construct(GiveHubSDK $givehub) {
        $this->givehub = $givehub;
    }
    
    public function createCampaignWithMilestones(array $campaignData, array $milestones) {
        try {
            // Create the base campaign
            $campaign = $this->givehub->campaigns->create([
                'title' => $campaignData['title'],
                'description' => $campaignData['description'],
                'targetAmount' => $campaignData['targetAmount'],
                'category' => $campaignData['category'],
                'milestones' => $milestones
            ]);
            
            // Upload campaign media if provided
            if (isset($campaignData['mediaPath'])) {
                $this->givehub->campaigns->uploadMedia(
                    $campaign['id'],
                    $campaignData['mediaPath']
                );
            }
            
            // Create initial impact metrics
            if (isset($campaignData['metrics'])) {
                $this->givehub->impact->createMetrics(
                    $campaign['id'],
                    ['metrics' => $campaignData['metrics']]
                );
            }
            
            return $campaign;
        } catch (GiveHubException $e) {
            error_log("Failed to create campaign: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function updateCampaignProgress(string $campaignId, array $progressData) {
        try {
            // Update campaign metrics
            if (isset($progressData['metrics'])) {
                $this->givehub->impact->updateMetrics($campaignId, [
                    'metrics' => $progressData['metrics']
                ]);
            }
            
            // Create progress update
            if (isset($progressData['update'])) {
                $update = $this->givehub->updates->create([
                    'campaignId' => $campaignId,
                    'title' => $progressData['update']['title'],
                    'content' => $progressData['update']['content'],
                    'type' => $progressData['update']['type'] ?? 'progress'
                ]);
                
                // Handle update media
                if (isset($progressData['update']['media'])) {
                    foreach ($progressData['update']['media'] as $mediaPath) {
                        $this->givehub->updates->uploadMedia($update['id'], $mediaPath);
                    }
                }
            }
            
            return true;
        } catch (GiveHubException $e) {
            error_log("Failed to update campaign progress: " . $e->getMessage());
            throw $e;
        }
    }
}

/**
 * Donation Processing Class Example
 */
class DonationProcessor {
    private $givehub;
    
    public function __construct(GiveHubSDK $givehub) {
        $this->givehub = $givehub;
    }
    
    public function processDonation(array $donationData) {
        try {
            // Validate donation data
            $this->validateDonationData($donationData);
            
            // Create the donation
            $donation = $this->givehub->donations->create([
                'campaignId' => $donationData['campaignId'],
                'amount' => [
                    'value' => $donationData['amount'],
                    'currency' => $donationData['currency']
                ],
                'type' => $donationData['type'] ?? 'one-time',
                'metadata' => $donationData['metadata'] ?? []
            ]);
            
            // Handle recurring donations
            if (($donationData['type'] ?? '') === 'recurring') {
                $this->setupRecurringDonation($donation['id'], $donationData);
            }
            
            return $donation;
        } catch (GiveHubException $e) {
            error_log("Donation processing failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function validateDonationData(array $data) {
        $required = ['campaignId', 'amount', 'currency'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new GiveHubException("Missing required field: {$field}");
            }
        }
        
        if ($data['amount'] <= 0) {
            throw new GiveHubException("Invalid donation amount");
        }
    }
    
    private function setupRecurringDonation(string $donationId, array $data) {
        return $this->givehub->donations->createRecurring([
            'donationId' => $donationId,
            'frequency' => $data['frequency'] ?? 'monthly',
            'duration' => $data['duration'] ?? null,
            'metadata' => $data['metadata'] ?? []
        ]);
    }
}

/**
 * Impact Tracking Class Example
 */
class ImpactTracker {
    private $givehub;
    
    public function __construct(GiveHubSDK $givehub) {
        $this->givehub = $givehub;
    }
    
    public function trackCampaignImpact(string $campaignId, array $metrics) {
        try {
            // Get existing metrics
            $existing = $this->givehub->impact->getMetrics($campaignId);
            
            // Update or create metrics
            $updatedMetrics = $this->mergeMetrics($existing['metrics'] ?? [], $metrics);
            
            // Submit updated metrics
            $result = $this->givehub->impact->updateMetrics($campaignId, [
                'metrics' => $updatedMetrics
            ]);
            
            // Create impact update
            $this->createImpactUpdate($campaignId, $metrics);
            
            return $result;
        } catch (GiveHubException $e) {
            error_log("Impact tracking failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    private function mergeMetrics(array $existing, array $new) {
        $merged = [];
        foreach ($new as $metric) {
            $existingMetric = $this->findExistingMetric($existing, $metric['name']);
            if ($existingMetric) {
                $merged[] = array_merge($existingMetric, [
                    'value' => $metric['value'],
                    'updated' => date('Y-m-d H:i:s')
                ]);
            } else {
                $merged[] = array_merge($metric, [
                    'created' => date('Y-m-d H:i:s')
                ]);
            }
        }
        return $merged;
    }
    
    private function findExistingMetric(array $metrics, string $name) {
        foreach ($metrics as $metric) {
            if ($metric['name'] === $name) {
                return $metric;
            }
        }
        return null;
    }
    
    private function createImpactUpdate(string $campaignId, array $metrics) {
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

// Usage Examples

// Initialize SDK
$givehub = new GiveHubSDK([
    'baseUrl' => 'https://api.thegivehub.com',
    'version' => 'v1',
    'apiKey' => 'your-api-key'
]);

// Campaign Management Example
$campaignManager = new CampaignManager($givehub);

$campaignData = [
    'title' => 'Clean Water Initiative',
    'description' => 'Bringing clean water to rural communities',
    'targetAmount' => 100000,
    'category' => 'water',
    'mediaPath' => '/path/to/campaign-image.jpg',
    'metrics' => [
        [
            'name' => 'Wells Built',
            'value' => 0,
            'target' => 10,
            'unit' => 'wells'
        ],
        [
            'name' => 'People Served',
            'value' => 0,
            'target' => 5000,
            'unit' => 'individuals'
        ]
    ]
];

$milestones = [
    [
        'description' => 'Initial Survey',
        'amount' => 10000
    ],
    [
        'description' => 'First Well Construction',
        'amount' => 25000
    ],
    [
        'description' => 'Distribution Network',
        'amount' => 65000
    ]
];

try {
    $campaign = $campaignManager->createCampaignWithMilestones($campaignData, $milestones);
    echo "Campaign created successfully: " . $campaign['id'] . "\n";
    
    // Update progress
    $progressData = [
        'metrics' => [
            [
                'name' => 'Wells Built',
                'value' => 1
            ],
            [
                'name' => 'People Served',
                'value' => 500
            ]
        ],
        'update' => [
            'title' => 'First Well Completed',
            'content' => 'We have successfully completed construction of the first well...',
            'type' => 'milestone',
            'media' => ['/path/to/well-photo.jpg']
        ]
    ];
    
    $campaignManager->updateCampaignProgress($campaign['id'], $progressData);
} catch (GiveHubException $e) {
    echo "Campaign management failed: " . $e->getMessage() . "\n";
}

// Donation Processing Example
$donationProcessor = new DonationProcessor($givehub);

try {
    $donationData = [
        'campaignId' => $campaign['id'],
        'amount' => 1000,
        'currency' => 'USD',
        'type' => 'recurring',
        'frequency' => 'monthly',
        'duration' => 12,
        'metadata' => [
            'donor_message' => 'Keep up the great work!',
            'anonymous' => false
        ]
    ];
    
    $donation = $donationProcessor->processDonation($donationData);
    echo "Donation processed successfully: " . $donation['id'] . "\n";
} catch (GiveHubException $e) {
    echo "Donation processing failed: " . $e->getMessage() . "\n";
}

// Impact Tracking Example
$impactTracker = new ImpactTracker($givehub);

try {
    $metrics = [
        [
            'name' => 'Wells Built',
            'value' => 2,
            'unit' => 'wells'
        ],
        [
            'name' => 'People Served',
            'value' => 1000,
            'unit' => 'individuals'
        ],
        [
            'name' => 'Water Quality',
            'value' => 95,
            'unit' => 'percent'
        ]
    ];
    
    $impact = $impactTracker->trackCampaignImpact($campaign['id'], $metrics);
    echo "Impact metrics updated successfully\n";
} catch (GiveHubException $e) {
    echo "Impact tracking failed: " . $e->getMessage() . "\n";
}

