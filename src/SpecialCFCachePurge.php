<?php

namespace MediaWiki\Extension\CFCachePurge;

use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;

class SpecialCFCachePurge extends SpecialPage
{
    public function __construct()
    {
        parent::__construct('CFCachePurge', 'cfcachepurge');
    }

    public function execute($subPage)
    {
        $this->setHeaders();
        $this->checkPermissions();
        $output = $this->getOutput();
        $output->setPageTitle($this->msg('cfcachepurge-special-title')->text());

        $output->addHtml($this->msg('cfcachepurge-special-intro')->escaped());

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
        $output = $this->getOutput();
        if ( $this->getUser()->pingLimiter( 'cfcachepurge' ) ) {
			$output->addHTML(
                Html::errorBox(
                    $this->msg('cfcachepurge-purge-throttled')->escaped()
                )
            );
            return false;
		}
        $result = CFHelper::purgePrefixes([$_SERVER['HTTP_HOST'] . '/load.php']);
        if ($result['success']) {
            $output->addHTML(
                Html::successBox(
                    $this->msg('cfcachepurge-purge-success')->escaped()
                )
            );
            return true;
        } else {
            $output->addHTML(
                Html::errorBox(
                    $this->msg('cfcachepurge-purge-failure')->escaped()
                )
            );
            return false;
        }
    }

    public function getGroupName()
    {
        return 'other';
    }
}

$wgSpecialPages['CFCachePurge'] = 'SpecialCFCachePurge';
