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

class SpecialListClosedWikis extends SpecialPage {
	public function __construct() {
		parent::__construct( 'ListClosedWikis' );
	}

	public function getDescription() {
		return $this->msg( 'closewikis-list' );
	}

	public function execute( $par ) {
		$output = $this->getOutput();

		$this->setHeaders();
		$output->addWikiMsg( 'closewikis-list-intro' );
		$output->addHTML( '<table class="mw-datatable TablePager" style="width: 100%"><tr>' );
		foreach ( [ 'wiki', 'by', 'timestamp', 'dispreason' ] as $column ) {
			$output->addHTML( '<th>' . $this->msg( "closewikis-list-header-{$column}" )->parse() . '</th>' );
		}
		$output->addHTML( '</tr>' );
		$list = CloseWikis::getAll();
		foreach ( $list as $entry ) {
			$output->addHTML( '<tr>' );
			$output->addHTML( '<td>' . $entry->getWiki() . '</td>' );
			$output->addHTML( '<td>' . $entry->getBy() . '</td>' );
			$output->addHTML( '<td>' . $this->getLanguage()->timeanddate( $entry->getTimestamp() ) . '</td>' );
			$output->addHTML( '<td>' );
			$output->addWikiTextAsInterface( $entry->getReason() );
			$output->addHTML( '</td>' );
			$output->addHTML( '</tr>' );
		}
		$output->addHTML( '</table>' );
	}
}
