# CFCachePurge MediaWiki Extension

This MediaWiki extension automatically purges the Cloudflare cache for files when they are uploaded or deleted.

## Features

* Purges Cloudflare cache upon file upload (`FileUpload` hook).
* Purges Cloudflare cache upon file deletion (`FileDeleteComplete` hook).
* Configurable Cloudflare Zone ID and API Token.
* Option to ignore purge requests for URLs containing `img_auth.php`.
* Option to specify a list of hostnames for which cache purging is allowed.

## Installation

1. Download the extension files and place them in the `extensions/CFCachePurge` directory of your MediaWiki installation.
2. Add the following code at the bottom of your `LocalSettings.php` file:

    ```php
    wfLoadExtension( 'CFCachePurge' );
    ```

3. Configure the required settings in your `LocalSettings.php`:
    * `$wgCFCachePurgeZoneID`: Your Cloudflare Zone ID.
    * `$wgCFCachePurgeAPIToken`: Your Cloudflare API Token with cache purge permissions.
    * `$wgCFCachePurgeIgnoreImgAuth` (optional, default: `false`): Set to `true` to prevent purging for files served via `img_auth.php`.
    * `$wgCFCachePurgePurgeableImageHosts` (optional, default: `[]`): An array of hostnames. If not empty, only files hosted on these domains will be purged. If empty, files on any hostname will be purged.
    Example:

    ```php
    wfLoadExtension( 'CFCachePurge' );
    $wgCFCachePurgeZoneID = 'YOUR_CLOUDFLARE_ZONE_ID';
    $wgCFCachePurgeAPIToken = 'YOUR_CLOUDFLARE_API_TOKEN';
    // Optional settings:
    // $wgCFCachePurgeIgnoreImgAuth = true;
    // $wgCFCachePurgePurgeableImageHosts = [ 'images.example.com', 'static.example.org' ];
    ```

4. Verify the extension is installed on the `Special:Version` page of your wiki.

## License

This extension is licensed under the GNU General Public License, version 2 or later (GPL-2.0-or-later). See the `COPYING` file for details.
