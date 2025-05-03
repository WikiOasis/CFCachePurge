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
        global $wgCFCachePurgeZoneID, $wgCFCachePurgeAPIToken;

        if (empty($wgCFCachePurgeZoneID) || empty($wgCFCachePurgeAPIToken)) {
            // Log the error instead of throwing an exception to avoid breaking the hook flow
            wfDebugLog('CFCachePurge', __METHOD__ . ': CloudflareCachePurge configuration error: Zone ID or API Token is missing.');
            throw new \MWException('CloudflareCachePurge configuration error: Zone ID or API Token is missing.');
        }


        $apiUrl = "https://api.cloudflare.com/client/v4/zones/{$wgCFCachePurgeZoneID}/purge_cache";
        $headers = [
            "Authorization: Bearer {$wgCFCachePurgeAPIToken}",
            'Content-Type: application/json',
        ];
        $data = json_encode(['files' => $urls]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // It's good practice to set a timeout
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 seconds timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 seconds connection timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            wfDebugLog('CFCachePurge', __METHOD__ . ': cURL error purging ' . implode(', ', $urls) . ': ' . $error);
            return ['success' => false, 'errors' => [['code' => 0, 'message' => 'cURL error: ' . $error]], 'messages' => []];
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['success']) && $responseData['success'] === true) {
            wfDebugLog('CFCachePurge', __METHOD__ . ': Successfully requested cache purge for ' . implode(', ', $urls));
            return $responseData;
        } else {
            $logMessage = __METHOD__ . ': Failed to purge cache for ' . implode(', ', $urls)
                . '. HTTP code: ' . $httpCode
                . '. Response: ' . $response;
            wfDebugLog('CFCachePurge', $logMessage);
            if (is_array($responseData) && isset($responseData['errors'])) {
                return ['success' => false, 'errors' => $responseData['errors'], 'messages' => $responseData['messages'] ?? []];
            } else {
                return ['success' => false, 'errors' => [['code' => $httpCode, 'message' => 'API request failed. Response: ' . $response]], 'messages' => []];
            }
        }
    }
}

