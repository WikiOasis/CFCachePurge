<?php

namespace MediaWiki\Extension\CFCachePurge;

use SpecialPage;
use Html;
use HTMLForm;
use MediaWiki\MediaWikiServices;
use MediaWiki\Lock\LockManager;

class SpecialCFCachePurge extends SpecialPage
{
    private const CACHE_KEY_LAST_PURGE = 'cfcachepurge:last_purge_time';
    private const CACHE_KEY_LOCK = 'cfcachepurge:purge_lock';
    private const COOLDOWN_SECONDS = 60;
    private const LOCK_TIMEOUT = 5; // Wait up to 5 seconds for the lock


    public function __construct()
    {
        parent::__construct('CFCachePurge', 'cfcachepurge');
    }

    public function execute($subPage)
    {
        $this->setHeaders();
        $this->checkPermissions();
        $output = $this->getOutput();
        $output->setPageTitle($this->msg('cfcachepurge-special-title'));

        $output->addHtml($this->msg('cfcachepurge-special-intro')->parse());

        $formDescriptor = [];

        $htmlForm = HTMLForm::factory('ooui', $formDescriptor, $this->getContext());
        $htmlForm->setMethod('post');
        $htmlForm->setAction($this->getPageTitle()->getLocalURL());
        $htmlForm->setSubmitName('purge');
        $htmlForm->setSubmitCallback([$this, 'processInput']);
        $htmlForm->setSubmitTextMsg('cfcachepurge-purge');
        $htmlForm->show();

    }

    public function processInput($input)
    {
        global $wgDBname;
        $output = $this->getOutput();
        $services = MediaWikiServices::getInstance();
        $cache = $services->getMainWANObjectCache();
        $lockManager = $services->getLockManager();

        $lockKey = $cache->makeKey(self::CACHE_KEY_LOCK . '_' . $wgDBname);
        $acquiredLock = false;

        try {
            if (!$lockManager->lock($lockKey, LockManager::LOCK_UW, self::LOCK_TIMEOUT)) {
                // Could not acquire lock, likely another purge is in progress.
                $output->addHTML(
                    Html::warningBox(
                        $this->msg('cfcachepurge-purge-failure')->escaped()
                    )
                );
                return false;
            }
            $acquiredLock = true;

            $lastPurgeTime = $cache->get($cache->makeKey(self::CACHE_KEY_LAST_PURGE . '_' . $wgDBname));
            $currentTime = time();

            if ($lastPurgeTime !== false && ($currentTime - (int) $lastPurgeTime < self::COOLDOWN_SECONDS)) {
                $remainingTime = self::COOLDOWN_SECONDS - ($currentTime - (int) $lastPurgeTime);
                $output->addHTML(
                    Html::warningBox(
                        $this->msg('cfcachepurge-purge-throttled', $remainingTime)->escaped()
                    )
                );
                $lockManager->unlock($lockKey);
                $acquiredLock = false;
                return false;
            }

            try {
                $host = $_SERVER['HTTP_HOST'] ?? null;
                if (!$host) {
                    throw new \RuntimeException("Could not determine HTTP host.");
                }
                $result = CFHelper::purgePrefixes([$host . '/load.php']);
            } catch (\Exception $e) {
                wfDebugLog('CFCachePurge', 'Purge action failed: {exception}', ['exception' => $e]);

                $output->addHTML(
                    Html::errorBox(
                        $this->msg('cfcachepurge-purge-failure', $e->getMessage())->escaped()
                    )
                );
                $lockManager->unlock($lockKey);
                $acquiredLock = false;
                return false;
            }

            if ($result['success']) {
                $cache->set($cache->makeKey(self::CACHE_KEY_LAST_PURGE) . "_" . $wgDBname, $currentTime, $cache::TTL_INDEFINITE);

                $output->addHTML(
                    Html::successBox(
                        $this->msg('cfcachepurge-purge-success')->escaped()
                    )
                );
                $lockManager->unlock($lockKey);
                $acquiredLock = false;
                return true;
            } else {
                $errorInfo = $result['errors'][0]['message'] ?? 'Unknown error from Cloudflare API';
                wfDebugLog("CFCachePurge", 'Purge command reported failure: {error}', ['error' => $errorInfo]);

                $output->addHTML(
                    Html::errorBox(
                        $this->msg('cfcachepurge-purge-failure')->escaped()
                    )
                );
                $lockManager->unlock($lockKey);
                $acquiredLock = false;
                return false;
            }

        } finally {
            if ($acquiredLock) {
                $lockManager->unlock($lockKey);
            }
        }
    }

    public function getGroupName()
    {
        return 'other';
    }
}

$wgSpecialPages['CFCachePurge'] = 'SpecialCFCachePurge';