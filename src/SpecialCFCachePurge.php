<?php

namespace MediaWiki\Extension\CFCachePurge;

class SpecialCFCachePurge extends \SpecialPage {
    public function __construct() {
        parent::__construct( 'CFCachePurge', 'cfcachepurge' );
    }

    public function execute( $subPage ) {
        $this->setHeaders();
        $this->checkPermissions();
        $this->getOutput()->setPageTitle( $this->msg( 'cfcachepurge-special-title' ) );
        $this->getOutput()->addHtml( $this->msg( 'cfcachepurge-special-intro' )->parse() );
        // button
        $this->getOutput()->addHtml( '<form method="post" action="' . $this->getPageTitle()->getLocalURL() . '">' );
    }

    public function getGroupName() {
        return 'other';
    }
}