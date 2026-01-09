<?php declare(strict_types=1);

namespace GotoWebinarGoogleSheetsExport\Service;

use Google\Client as GoogleClient;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Service for Google Sheets API integration
 * Handles OAuth2 authentication and data export to Google Sheets
 */
class GoogleSheetsService
{
    private const CONFIG_PREFIX = 'GotoWebinarGoogleSheetsExport.config.';
    private const OAUTH_SCOPES = [
        Sheets::SPREADSHEETS,
    ];

    private ?GoogleClient $client = null;

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Get a sanitized config value (trimmed of whitespace)
     * This prevents issues with copy-paste errors adding trailing spaces
     */
    private function getSanitizedConfig(string $key): ?string
    {
        $value = $this->systemConfigService->get(self::CONFIG_PREFIX . $key);
        
        if ($value === null) {
            return null;
        }
        
        return trim((string) $value);
    }

    /**
     * Get Google Client instance with authentication
     */
    private function getClient(): GoogleClient
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $clientId = $this->getSanitizedConfig('googleClientId');
        $clientSecret = $this->getSanitizedConfig('googleClientSecret');
        $refreshToken = $this->getSanitizedConfig('googleRefreshToken');

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException('Google API credentials not configured');
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setScopes(self::OAUTH_SCOPES);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        if ($refreshToken) {
            $client->setAccessToken([
                'refresh_token' => $refreshToken,
            ]);

            // Refresh the access token if needed
            if ($client->isAccessTokenExpired()) {
                try {
                    $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
                    
                    if (isset($newToken['error'])) {
                        throw new \RuntimeException('Failed to refresh token: ' . $newToken['error']);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Failed to refresh Google access token', [
                        'error' => $e->getMessage(),
                    ]);
                    throw new \RuntimeException('Failed to refresh access token: ' . $e->getMessage());
                }
            }
        }

        $this->client = $client;
        return $client;
    }

    /**
     * Generate OAuth2 authorization URL
     */
    public function getAuthorizationUrl(string $redirectUri): string
    {
        $clientId = $this->getSanitizedConfig('googleClientId');
        $clientSecret = $this->getSanitizedConfig('googleClientSecret');

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException('Google API credentials not configured');
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes(self::OAUTH_SCOPES);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client->createAuthUrl();
    }

    /**
     * Exchange authorization code for tokens
     */
    public function authenticate(string $authCode, string $redirectUri): array
    {
        $clientId = $this->getSanitizedConfig('googleClientId');
        $clientSecret = $this->getSanitizedConfig('googleClientSecret');

        if (!$clientId || !$clientSecret) {
            throw new \RuntimeException('Google API credentials not configured');
        }

        $client = new GoogleClient();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);
        $client->setScopes(self::OAUTH_SCOPES);
        $client->setAccessType('offline');

        try {
            $token = $client->fetchAccessTokenWithAuthCode($authCode);

            if (isset($token['error'])) {
                throw new \RuntimeException('OAuth error: ' . $token['error']);
            }

            if (!isset($token['refresh_token'])) {
                throw new \RuntimeException('No refresh token received. Please revoke app access and try again.');
            }

            // Store refresh token
            $this->systemConfigService->set(
                self::CONFIG_PREFIX . 'googleRefreshToken',
                $token['refresh_token']
            );

            return $token;
        } catch (\Exception $e) {
            $this->logger->error('Google OAuth authentication failed', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Append rows to Google Sheet
     * 
     * @param string $sheetId Google Sheet ID
     * @param string $worksheetName Name of the worksheet/tab
     * @param array $rows Array of rows, each row is an array of values
     */
    public function appendRows(string $sheetId, string $worksheetName, array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        try {
            $client = $this->getClient();
            $service = new Sheets($client);

            $range = $worksheetName . '!A:Z';
            $body = new ValueRange([
                'values' => $rows
            ]);

            $params = [
                'valueInputOption' => 'RAW'
            ];

            $result = $service->spreadsheets_values->append(
                $sheetId,
                $range,
                $body,
                $params
            );

            $this->logger->info('Successfully appended rows to Google Sheet', [
                'sheetId' => $sheetId,
                'rowCount' => count($rows),
                'updatedCells' => $result->getUpdates()->getUpdatedCells()
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to append rows to Google Sheet', [
                'sheetId' => $sheetId,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException('Failed to export to Google Sheets: ' . $e->getMessage());
        }
    }

    /**
     * Validate access to a Google Sheet
     */
    public function validateSheetAccess(string $sheetId): bool
    {
        try {
            $client = $this->getClient();
            $service = new Sheets($client);

            // Try to read sheet metadata
            $service->spreadsheets->get($sheetId);

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Failed to validate Google Sheet access', [
                'sheetId' => $sheetId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Refresh access token manually
     */
    public function refreshAccessToken(): string
    {
        $client = $this->getClient();
        
        if (!$client->isAccessTokenExpired()) {
            $token = $client->getAccessToken();
            return $token['access_token'];
        }

        $refreshToken = $this->getSanitizedConfig('googleRefreshToken');
        
        if (!$refreshToken) {
            throw new \RuntimeException('No refresh token available');
        }

        try {
            $newToken = $client->fetchAccessTokenWithRefreshToken($refreshToken);
            
            if (isset($newToken['error'])) {
                throw new \RuntimeException('Failed to refresh token: ' . $newToken['error']);
            }

            return $newToken['access_token'];
        } catch (\Exception $e) {
            $this->logger->error('Failed to refresh access token', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if Google Sheets integration is configured
     */
    public function isConfigured(): bool
    {
        $clientId = $this->getSanitizedConfig('googleClientId');
        $clientSecret = $this->getSanitizedConfig('googleClientSecret');
        $refreshToken = $this->getSanitizedConfig('googleRefreshToken');

        return !empty($clientId) && !empty($clientSecret) && !empty($refreshToken);
    }
}
