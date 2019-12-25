<?php
/**
 * Copyright (C) 2008 Victor Vasiliev <vasilvv@gmail.com>
 *
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
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 */

class SpecialCloseWiki extends SpecialPage {
	public function __construct() {
		parent::__construct( 'CloseWiki', 'closewikis' );
	}

	public function doesWrites() {
		return true;
	}

	public function getDescription() {
		return wfMessage( 'closewikis-page' )->text();
	}

	public function execute( $par ) {
		$this->setHeaders();
		if ( !$this->userCanExecute( $this->getUser() ) ) {
			$this->displayRestrictionError();
			return;
		}

		$this->closeForm();
		if ( CloseWikis::getList() ) {
			$this->reopenForm();
		}
	}

	protected function buildSelect( $list, $name, $default = '' ) {
		sort( $list );
		$select = new XmlSelect( $name );
		$select->setDefault( $default );
		foreach ( $list as $wiki ) {
			$select->addOption( $wiki );
		}
		return $select->getHTML();
	}

	protected function closeForm() {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$user = $this->getUser();
		$status = '';
		$statusOK = false;
		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpcEdittoken' ) ) ) {
			global $wgLocalDatabases;
			$wiki = $request->getVal( 'wpcWiki' );
			$dreason = $request->getVal( 'wpcDisplayReason' );
			$lreason = $request->getVal( 'wpcReason' );
			if ( !in_array( $wiki, $wgLocalDatabases ) ) {
				$status = wfMessage( 'closewikis-page-err-nowiki' )->parse();
			} else {
				$statusOK = CloseWikis::close( $wiki, $dreason, $user );
				if ( $statusOK ) {
					$status = wfMessage( 'closewikis-page-close-success' )->parse();
					$logpage = new LogPage( 'closewiki' );
					$logpage->addEntry(
						'close',
						$this->getPageTitle(), /* dummy */
						$lreason,
						[ $wiki ],
						$user
					);
				} else {
					$status = wfMessage( 'closewikis-page-err-closed' )->parse();
				}
			}
		}

		$legend = wfMessage( 'closewikis-page-close' )->escaped();

		// If operation was successful, empty all fields
		$defaultWiki = $statusOK ? '' : $request->getVal( 'wpcWiki' );
		$defaultDisplayReason = $statusOK ? '' : $request->getVal( 'wpcDisplayReason' );
		$defaultReason = $statusOK ? '' : $request->getVal( 'wpcReason' );
		// For some reason Xml::textarea( 'blabla', null ) produces an unclosed tag
		if ( !$defaultDisplayReason ) {
			$defaultDisplayReason = '';
		}

		$output->addHTML( "<fieldset><legend>{$legend}</legend>" );
		if ( $status ) {
			$statusStyle = $statusOK ? 'success' : 'error';
			$output->addHTML( "<p><strong class=\"{$statusStyle}\">{$status}</strong></p>" );
		}
		$output->addHTML( '<form method="post" action="' . htmlspecialchars( $this->getPageTitle()->getLocalURL() ) . '">' );
		$form = [];
		$form['closewikis-page-close-wiki'] = $this->buildSelect( CloseWikis::getUnclosedList(), 'wpcWiki', $defaultWiki );
		$form['closewikis-page-close-dreason'] = Xml::textarea( 'wpcDisplayReason', $defaultDisplayReason );
		$form['closewikis-page-close-reason'] = Xml::input( 'wpcReason', false, $defaultReason );
		$output->addHTML( Xml::buildForm( $form, 'closewikis-page-close-submit' ) );
		$output->addHTML( Html::hidden( 'wpcEdittoken', $user->getEditToken() ) );
		$output->addHTML( "</form></fieldset>" );
	}

	protected function reopenForm() {
		$request = $this->getRequest();
		$output = $this->getOutput();
		$user = $this->getUser();
		$status = '';
		$statusOK = false;
		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wprEdittoken' ) ) ) {
			global $wgLocalDatabases;
			$wiki = $request->getVal( 'wprWiki' );
			$lreason = $request->getVal( 'wprReason' );
			if ( !in_array( $wiki, $wgLocalDatabases ) ) {
				$status = wfMessage( 'closewikis-page-err-nowiki' )->parse();
			} else {
				$statusOK = CloseWikis::reopen( $wiki );
				if ( $statusOK ) {
					$status = wfMessage( 'closewikis-page-reopen-success' )->parse();
					$logpage = new LogPage( 'closewiki' );
					$logpage->addEntry(
						'reopen',
						$this->getPageTitle(), /* dummy */
						$lreason,
						[ $wiki ],
						$user
					);
				} else {
					$status = wfMessage( 'closewikis-page-err-opened' )->parse();
				}
			}
		}

		$legend = wfMessage( 'closewikis-page-reopen' )->escaped();

		// If operation was successful, empty all fields
		$defaultWiki = $statusOK ? '' : $request->getVal( 'wprWiki' );
		$defaultReason = $statusOK ? '' : $request->getVal( 'wprReason' );

		$output->addHTML( "<fieldset><legend>{$legend}</legend>" );
		if ( $status ) {
			$statusStyle = $statusOK ? 'success' : 'error';
			$output->addHTML( "<p><strong class=\"{$statusStyle}\">{$status}</strong></p>" );
		}
		$output->addHTML( '<form method="post" action="' . htmlspecialchars( $this->getPageTitle()->getLocalURL() ) . '">' );
		$form = [];
		$form['closewikis-page-reopen-wiki'] = $this->buildSelect( CloseWikis::getList(), 'wprWiki', $defaultWiki );
		$form['closewikis-page-reopen-reason'] = Xml::input( 'wprReason', false, $defaultReason );
		$output->addHTML( Xml::buildForm( $form, 'closewikis-page-reopen-submit' ) );
		$output->addHTML( Html::hidden( 'wprEdittoken', $user->getEditToken() ) );
		$output->addHTML( "</form></fieldset>" );
	}
}
