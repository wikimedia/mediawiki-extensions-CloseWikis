<?php

use MediaWiki\WikiMap\WikiMap;

class CloseWikisHooks {

	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		if ( defined( 'MW_QUIBBLE_CI' ) ) {
			$updater->addExtensionTable( 'closedwikis', __DIR__ . '/../sql/closewikis.sql' );
		}

		$updater->dropExtensionIndex(
			'closedwikis', // table
			'cw_wiki', // index
			__DIR__ . '/../sql/closedwikis-patch-pk.sql' // file
		);

		return true;
	}

	public static function onRegistration() {
		global $wgCloseWikisDatabase, $wgDBname;

		if ( defined( 'MW_QUIBBLE_CI' ) ) {
			$wgCloseWikisDatabase = $wgDBname;
		}
	}

	/**
	 * @param Title &$title
	 * @param User &$user
	 * @param string $action
	 * @param array &$result
	 * @return bool
	 */
	public static function userCan( &$title, &$user, $action, &$result ) {
		static $closed = null;
		global $wgLang;
		if ( $action == 'read' ) {
			return true;
		}

		if ( $closed === null ) {
			$closed = CloseWikis::getClosedRow( WikiMap::getCurrentWikiId() );
		}

		if ( $closed->isClosed() && !$user->isAllowed( 'editclosedwikis' ) ) {
			$reason = $closed->getReason();
			$ts = $closed->getTimestamp();
			$by = $closed->getBy();
			$result[] =	[ 'closewikis-closed', $reason, $by,
				$wgLang->timeanddate( $ts ), $wgLang->time( $ts ), $wgLang->date( $ts ) ];
			return false;
		}
		return true;
	}
}
