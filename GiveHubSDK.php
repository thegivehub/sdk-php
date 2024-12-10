<?php
namespace GiveHub;

/**
 * GiveHub PHP SDK
 * @version 1.0.0
 */

class GiveHubSDK {
    private $baseUrl;
    private $version;
    private $apiKey;
    private $accessToken;
    private $refreshToken;

    /** @var Auth */
    public $auth;
    
    /** @var Campaigns */
    public $campaigns;
    
    /** @var Donations */
    public $donations;
    
    /** @var Impact */
    public $impact;
    
    /** @var Updates */
    public $updates;
    
    /** @var Notifications */
    public $notifications;

    public function __construct(array $config = []) {
        $this->baseUrl = $config['baseUrl'] ?? 'https://api.thegivehub.com';
        $this->version = $config['version'] ?? 'v1';
        $this->apiKey = $config['apiKey'] ?? null;
        $this->accessToken = $config['accessToken'] ?? null;
        $this->refreshToken = $config['refreshToken'] ?? null;

        // Initialize modules
        $this->auth = new Auth($this);
        $this->campaigns = new Campaigns($this);
        $this->donations = new Donations($this);
        $this->impact = new Impact($this);
        $this->updates = new Updates($this);
        $this->notifications = new Notifications($this);
    }

    /**
     * Make an authenticated API request
     * @throws GiveHubException
     */
    public function request(string $endpoint, array $options = []): array {
        $url = "{$this->baseUrl}/{$this->version}{$endpoint}";
        
        $headers = [
            'Content-Type: application/json',
            'X-API-Key: ' . $this->apiKey
        ];

        if ($this->accessToken) {
            $headers[] = 'Authorization: Bearer ' . $this->accessToken;
        }

        $ch = curl_init();
        
        $defaultOptions = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers
        ];

        // Merge with custom options
        $curlOptions = $options['curl'] ?? [];
        curl_setopt_array($ch, $defaultOptions + $curlOptions);

        if (isset($options['method'])) {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($options['method']));
        }

        if (isset($options['body'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['body']));
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new GiveHubException('Request failed: ' . curl_error($ch));
        }
        
        curl_close($ch);

        $data = json_decode($response, true);

        if ($statusCode === 401 && $this->refreshToken) {
            // Token expired, try to refresh
            $this->auth->refreshAccessToken();
            // Retry request with new token
            return $this->request($endpoint, $options);
        }

        if ($statusCode >= 400) {
            throw new GiveHubException($data['error'] ?? 'API Error', $statusCode);
        }

        return $data;
    }

    public function setAccessToken(string $token): void {
        $this->accessToken = $token;
    }

    public function setRefreshToken(string $token): void {
        $this->refreshToken = $token;
    }
}

class Auth {
    private $sdk;

    public function __construct(GiveHubSDK $sdk) {
        $this->sdk = $sdk;
    }

    public function login(string $email, string $password): array {
        $response = $this->sdk->request('/auth/login', [
            'method' => 'POST',
            'body' => [
                'email' => $email,
                'password' => $password
            ]
        ]);

        if ($response['success']) {
            $this->sdk->setAccessToken($response['tokens']['accessToken']);
            $this->sdk->setRefreshToken($response['tokens']['refreshToken']);
        }

        return $response;
    }

    public function register(array $userData): array {
        return $this->sdk->request('/auth/register', [
            'method' => 'POST',
            'body' => $userData
        ]);
    }

    public function verifyEmail(string $email, string $code): array {
        return $this->sdk->request('/auth/verify', [
            'method' => 'POST',
            'body' => [
                'email' => $email,
                'code' => $code
            ]
        ]);
    }

    public function refreshAccessToken(): array {
        $response = $this->sdk->request('/auth/refresh', [
            'method' => 'POST',
            'body' => [
                'refreshToken' => $this->sdk->refreshToken
            ]
        ]);

        if ($response['success']) {
            $this->sdk->setAccessToken($response['accessToken']);
        }

        return $response;
    }
}

class Campaigns {
    private $sdk;

    public function __construct(GiveHubSDK $sdk) {
        $this->sdk = $sdk;
    }

    public function create(array $campaignData): array {
        return $this->sdk->request('/campaigns', [
            'method' => 'POST',
            'body' => $campaignData
        ]);
    }

    public function get(string $campaignId): array {
        return $this->sdk->request("/campaigns/{$campaignId}");
    }

    public function list(array $params = []): array {
        $query = http_build_query($params);
        return $this->sdk->request("/campaigns?{$query}");
    }

    public function update(string $campaignId, array $updateData): array {
        return $this->sdk->request("/campaigns/{$campaignId}", [
            'method' => 'PUT',
            'body' => $updateData
        ]);
    }

    public function uploadMedia(string $campaignId, string $filePath): array {
        $file = new \CURLFile($filePath);
        
        return $this->sdk->request("/campaigns/{$campaignId}/media", [
            'method' => 'POST',
            'curl' => [
                CURLOPT_POSTFIELDS => ['media' => $file]
            ]
        ]);
    }
}

class Donations {
    private $sdk;

    public function __construct(GiveHubSDK $sdk) {
        $this->sdk = $sdk;
    }

    public function create(array $donationData): array {
        return $this->sdk->request('/donations', [
            'method' => 'POST',
            'body' => $donationData
        ]);
    }

    public function getDonations(array $params = []): array {
        $query = http_build_query($params);
        return $this->sdk->request("/donations?{$query}");
    }

    public function createRecurring(array $donationData): array {
        return $this->sdk->request('/donations/recurring', [
            'method' => 'POST',
            'body' => $donationData
        ]);
    }

    public function cancelRecurring(string $subscriptionId): array {
        return $this->sdk->request("/donations/recurring/{$subscriptionId}", [
            'method' => 'DELETE'
        ]);
    }
}

class Impact {
    private $sdk;

    public function __construct(GiveHubSDK $sdk) {
        $this->sdk = $sdk;
    }

    public function createMetrics(string $campaignId, array $metricsData): array {
        return $this->sdk->request('/impact/metrics', [
            'method' => 'POST',
            'body' => array_merge(['campaignId' => $campaignId], $metricsData)
        ]);
    }

    public function updateMetrics(string $metricId, array $updateData): array {
        return $this->sdk->request("/impact/metrics/{$metricId}", [
            'method' => 'PUT',
            'body' => $updateData
        ]);
    }

    public function getMetrics(string $campaignId, array $params = []): array {
        $query = http_build_query($params);
        return $this->sdk->request("/impact/metrics/{$campaignId}?{$query}");
    }
}

class Updates {
    private $sdk;

    public function __construct(GiveHubSDK $sdk) {
        $this->sdk = $sdk;
    }

    public function create(array $updateData): array {
        return $this->sdk->request('/updates', [
            'method' => 'POST',
            'body' => $updateData
        ]);
    }

    public function getUpdates(array $params = []): array {
        $query = http_build_query($params);
        return $this->sdk->request("/updates?{$query}");
    }

    public function uploadMedia(string $updateId, string $filePath): array {
        $file = new \CURLFile($filePath);
        
        return $this->sdk->request("/updates/{$updateId}/media", [
            'method' => 'POST',
            'curl' => [
                CURLOPT_POSTFIELDS => ['media' => $file]
            ]
        ]);
    }
}

class Notifications {
    private $sdk;
    private $socket;
    private $listeners = [];

    public function __construct(GiveHubSDK $sdk) {
        $this->sdk = $sdk;
    }

    public function getNotifications(array $params = []): array {
        $query = http_build_query($params);
        return $this->sdk->request("/notifications?{$query}");
    }

    public function markAsRead(string $notificationId): array {
        return $this->sdk->request("/notifications/{$notificationId}/read", [
            'method' => 'PUT'
        ]);
    }
}

class GiveHubException extends \Exception {
    public function __construct(string $message = "", int $code = 0) {
        parent::__construct($message, $code);
    }
}

