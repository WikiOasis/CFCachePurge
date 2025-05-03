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
use MediaWiki\Hook\FileDeleteCompleteHook;
// Add the use statement for CFHelper
use MediaWiki\Extension\CFCachePurge\CFHelper;

class Hooks implements FileUploadHook, FileDeleteCompleteHook
{

    public function onFileUpload($file, $reupload, $hasDescription): void
    {
        wfDebugLog('CFCachePurge', __METHOD__ . ': FileUpload ' . $file->getFullUrl() . ' ' . $file->getSize());
        $this->purgeCache($file->getFullUrl());
    }

    public function onFileDeleteComplete($file, $oldimage, $article, $user, $reason): void
    {
        wfDebugLog('CFCachePurge', __METHOD__ . ': FileDeleteComplete ' . $file->getFullUrl() . ' ' . $file->getSize());
        $this->purgeCache($file->getFullUrl());
    }

    /**
     * Purges the cache for the given URL using the Cloudflare API via CFHelper.
     *
     * @param string $url The URL to purge.
     * @return bool True on success, false on failure.
     */
    private function purgeCache(string $url): bool
    {
        global $wgCFCachePurgePurgeableImageHosts, $wgCFCachePurgeIgnoreImgAuth;

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

        // Call the static helper method
        $result = CFHelper::purgeUrls([$url]);

        // Check the result from the helper method
        if ($result['success']) {
            wfDebugLog('CFCachePurge', __METHOD__ . ': Successfully requested cache purge for ' . $url);
            return true;
        } else {
            // Log errors if any
            $errorMessages = [];
            foreach ($result['errors'] as $error) {
                $errorMessages[] = "Code: {$error['code']}, Message: {$error['message']}";
            }
            wfDebugLog('CFCachePurge', __METHOD__ . ': Failed to purge cache for ' . $url . '. Errors: ' . implode('; ', $errorMessages));
            return false;
        }
    }
}
