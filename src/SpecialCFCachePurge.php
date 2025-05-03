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

        $output->addHtml( $this->msg( 'cfcachepurge-special-intro' )->parse() );

        $formDescriptor = [];

        $htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
        $htmlForm->setMethod( 'post' );
        $htmlForm->setAction( $this->getPageTitle()->getLocalURL() );
        $htmlForm->setSubmitName( 'purge' );
        $htmlForm->setSubmitCallback( [ $this, 'processInput' ] ); 
        $htmlForm->setSubmitTextMsg( 'cfcachepurge-purge' );
        $htmlForm->show();

    }

    public function processInput( $input ) {
        $output = $this->getOutput();
        $output->addHTML(
            Html::successBox(
                $this->msg( 'cfcachepurge-purge-success' )->escaped()
            )
        );
        return true;
    }

    public function getGroupName() {
        return 'other';
    }
}

$wgSpecialPages['CFCachePurge'] = 'SpecialCFCachePurge';