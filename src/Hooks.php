<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

namespace MediaWiki\Extension\CFCachePurge;

use MediaWiki\Hook\FileUploadHook;

class Hooks implements FileUploadHook
{

    public function onFileUpload($file, $reupload, $hasDescription): void
    {
        wfDebugLog('CFCachePurge', __METHOD__ . ': FileUpload ' . $file->getFullUrl() . ' ' . $file->getSize());
        $this->purgeCache($file->getFullUrl());
    }

    /**
     * Purges the cache for the given URL using the Cloudflare API.
     *
     * @param string $url The URL to purge.
     * @return bool True on success, false on failure.
     */
    private function purgeCache(string $url): bool
    {
        global $wgCFCachePurgeZoneID, $wgCFCachePurgeAPIToken, $wgCFCachePurgeEmail, $wgCFCachePurgePurgeableImageHosts, $wgCFCachePurgeIgnoreImgAuth;

        if (empty($wgCFCachePurgeZoneID) || empty($wgCFCachePurgeAPIToken)) {
            throw new \RuntimeException('CloudflareCachePurge configuration error: Zone ID or API Token is missing.');
        }

        // $wgCFCachePurgeIgnoreImgAuth が true の場合は、img_auth.php が含まれていた場合パージしない
        if ($wgCFCachePurgeIgnoreImgAuth && strpos($url, 'img_auth.php') !== false) {
            wfDebugLog('CFCachePurge', __METHOD__ . ': Ignoring purge for URL with img_auth.php: ' . $url);
            return false;
        }

        // ホスト名を抽出
        $parsedUrl = parse_url($url);
        $host = $parsedUrl['host'] ?? null;

        // ホスト名が取得できない場合はパージしない
        if (!$host) {
            wfDebugLog('CFCachePurge', __METHOD__ . ': Could not parse host from URL: ' . $url);
            return false;
        }

        // $wgCFCachePurgePurgeableImageHosts が空でない配列の場合のみホスト名をチェックする
        if (isset($wgCFCachePurgePurgeableImageHosts) && is_array($wgCFCachePurgePurgeableImageHosts) && !empty($wgCFCachePurgePurgeableImageHosts)) {
            // ホスト名が許可されたリストにない場合はパージしない
            if (!in_array($host, $wgCFCachePurgePurgeableImageHosts, true)) {
                wfDebugLog('CFCachePurge', __METHOD__ . ': Hostname ' . $host . ' is not in the purgeable list for URL: ' . $url);
                return false;
            }
        }
        // $wgCFCachePurgePurgeableImageHosts が空、未設定、または配列でない場合は、どのホスト名も許可される

        $apiUrl = "https://api.cloudflare.com/client/v4/zones/{$wgCFCachePurgeZoneID}/purge_cache";
        $headers = [
            "X-Auth-Key: {$wgCFCachePurgeAPIToken}",
            "X-Auth-Email: {$wgCFCachePurgeEmail}",
            'Content-Type: application/json',
        ];
        $data = json_encode(['files' => [$url]]);

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
            wfDebugLog('CFCachePurge', __METHOD__ . ': cURL error purging ' . $url . ': ' . $error);
            return false;
        }

        $responseData = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($responseData['success']) && $responseData['success'] === true) {
            wfDebugLog('CFCachePurge', __METHOD__ . ': Successfully purged cache for ' . $url);
            return true;
        } else {
            $logMessage = __METHOD__ . ': Failed to purge cache for ' . $url
                . '. HTTP code: ' . $httpCode
                . '. Response: ' . $response;
            wfDebugLog('CFCachePurge', $logMessage);
            return false;
        }
    }
}
