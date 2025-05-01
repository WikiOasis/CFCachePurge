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

use MediaWiki\Hook\UploadCompleteHook;
use MediaWiki\Hook\FileUploadHook;

class Hooks implements UploadCompleteHook, FileUploadHook {
    public function onUploadComplete( $image ): bool {
        wfDebugLog( 'CFCachePurge', __METHOD__ . ': UploadComplete ' . $image->getLocalFile()->url . ' ' . $image->getLocalFile()->size );
        return true;
    }

    public function onFileUpload( $file, $reupload, $hasDescription ): void {
        wfDebugLog( 'CFCachePurge', __METHOD__ . ': FileUpload ' . $file->getLocalFile()->url . ' ' . $file->getLocalFile()->size );
    }
}
