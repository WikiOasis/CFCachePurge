<?php

namespace MediaWiki\Extension\CFCachePurge;

class CFHelper
{

    /**
     * Purges the cache for the given URLs using the Cloudflare API.
     *
     * @param array $urls Array of URLs to purge.
     * @return array{success: bool, errors: array, messages: array} Result from Cloudflare API or error details.
     */
    public static function purgeUrls(array $urls): array
    {
        return self::sendPurgeRequest(['files' => $urls]);
    }

    /**
     * Purges the cache for the given prefixes using the Cloudflare API.
     *
     * @param array $prefixes Array of URL prefixes to purge.
     * @return array{success: bool, errors: array, messages: array} Result from Cloudflare API or error details.
     */
    public static function purgePrefixes(array $prefixes): array
    {
        return self::sendPurgeRequest(['prefixes' => $prefixes]);
    }

    /**
     * Sends a purge request to the Cloudflare API.
     *
     * @param array $data Purge data (e.g., ['files' => [...]] or ['prefixes' => [...]]).
     * @return array{success: bool, errors: array, messages: array} Result from Cloudflare API or error details.
     * @throws MWException If configuration is missing.
     */
    private static function sendPurgeRequest(array $data): array
    {
        global $wgCFCachePurgeZoneID, $wgCFCachePurgeAPIToken;

        if (empty($wgCFCachePurgeZoneID) || empty($wgCFCachePurgeAPIToken)) {
            $errorMsg = 'CloudflareCachePurge configuration error: Zone ID or API Token is missing.';
            // Log the error using the global wfDebugLog function
            wfDebugLog('CFCachePurge', __METHOD__ . ': ' . $errorMsg);
            throw new MWException($errorMsg);
        }

        $apiUrl = "https://api.cloudflare.com/client/v4/zones/{$wgCFCachePurgeZoneID}/purge_cache";
        $headers = [
            "Authorization: Bearer {$wgCFCachePurgeAPIToken}",
            'Content-Type: application/json',
        ];
        $jsonData = json_encode($data);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 seconds timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 seconds connection timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        $logContext = json_encode($data); // Log what was attempted to be purged

        if ($curlError) {
            // Log the error using the global wfDebugLog function
            wfDebugLog('CFCachePurge', __METHOD__ . ": cURL error purging {$logContext}: " . $curlError);
            return ['success' => false, 'errors' => [['code' => 0, 'message' => 'cURL error: ' . $curlError]], 'messages' => []];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['success']) && $responseData['success'] === true) {
            // Log success using the global wfDebugLog function
            wfDebugLog('CFCachePurge', __METHOD__ . ": Successfully requested cache purge for {$logContext}");
            return $responseData;
        } else {
            $logMessage = __METHOD__ . ": Failed to purge cache for {$logContext}"
                . '. HTTP code: ' . $httpCode
                . '. Response: ' . $response;
            // Log failure using the global wfDebugLog function
            wfDebugLog('CFCachePurge', $logMessage);
            $errors = (isset($responseData['errors']) && is_array($responseData['errors'])) ? $responseData['errors'] : [['code' => $httpCode, 'message' => 'API request failed. Response: ' . $response]];
            $messages = (isset($responseData['messages']) && is_array($responseData['messages'])) ? $responseData['messages'] : [];
            return ['success' => false, 'errors' => $errors, 'messages' => $messages];
        }
    }
}

