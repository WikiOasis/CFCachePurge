<?php

namespace MediaWiki\Extension\CFCachePurge;

use SpecialPage;
use Html;
use HTMLForm;

class SpecialCFCachePurge extends SpecialPage {
    public function __construct() {
        parent::__construct( 'CFCachePurge', 'cfcachepurge' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $this->checkPermissions();
        $output = $this->getOutput();
        $output->setPageTitle( $this->msg( 'cfcachepurge-special-title' ) );

        $request = $this->getRequest();

        if ( $request->wasPosted() && $this->getUser()->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
            $this->getOutput()->addHTML(
                Html::successBox(
                    $this->msg( 'cfcachepurge-purge-success' )->escaped()
                )
            );
        }

        $output->addHtml( $this->msg( 'cfcachepurge-special-intro' )->parse() );

        $formDescriptor = [];

        $htmlForm = new HTMLForm( $formDescriptor, $this->getContext() );
        $htmlForm->setMethod( 'post' );
        $htmlForm->setAction( $this->getPageTitle()->getLocalURL() );
        $htmlForm->setSubmitDestructive();
        $htmlForm->setSubmitName( 'purge' );
        $htmlForm->setSubmitTextMsg( 'cfcachepurge-purge' );
        $htmlForm->show();

    }

    public function getGroupName() {
        return 'other';
    }
}

$wgSpecialPages['CFCachePurge'] = 'SpecialCFCachePurge';