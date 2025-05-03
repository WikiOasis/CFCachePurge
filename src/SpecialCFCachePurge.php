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

        $formDescriptor = [
            'purgebutton' => [
                'type' => 'submit',
                'default' => $this->msg( 'cfcachepurge-purge' )->text(),
                'flags' => [ 'progressive', 'primary' ],
            ],
        ];

        $htmlForm = \HTMLForm::create( $formDescriptor, $this->getContext() );
        $htmlForm->setMethod( 'post' );
        $htmlForm->setAction( $this->getPageTitle()->getLocalURL() );
        $htmlForm->setSubmitName( 'purge' );
        $htmlForm->setSubmitTextMsg( 'cfcachepurge-purge' );
        $htmlForm->setSubmitProgressive();
        $htmlForm->prepareForm();
        $htmlForm->displayForm( false );

    }

    public function getGroupName() {
        return 'other';
    }
}