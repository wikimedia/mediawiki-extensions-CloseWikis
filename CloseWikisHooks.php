<?php
class CloseWikisHooks {

	public static function onLoadExtensionSchemaUpdates( DatabaseUpdater $updater ) {
		global $wgWikimediaJenkinsCI;
		if ( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI === true ) {
			$updater->addExtensionTable( 'closedwikis', __DIR__ . '/closewikis.sql' );
		}
		return true;
	}

	public static function onRegistration() {
		global $wgWikimediaJenkinsCI, $wgCloseWikisDatabase, $wgDBname;

		if ( isset( $wgWikimediaJenkinsCI ) && $wgWikimediaJenkinsCI === true ) {
			$wgCloseWikisDatabase = $wgDBname;
		}
	}

	/**
	 * @static
	 * @param $title
	 * @param $user User
	 * @param $action
	 * @param $result
	 * @return bool
	 */
	static function userCan( &$title, &$user, $action, &$result ) {
		static $closed = null;
		global $wgLang;
		if( $action == 'read' ) {
			return true;
		}

		if( is_null( $closed ) ) {
			$closed = CloseWikis::getClosedRow( wfWikiID() );
		}

		if( $closed->isClosed() && !$user->isAllowed( 'editclosedwikis' ) ) {
			$reason = $closed->getReason();
			$ts = $closed->getTimestamp();
			$by = $closed->getBy();
			$result[] =	array( 'closewikis-closed', $reason, $by,
				$wgLang->timeanddate( $ts ), $wgLang->time( $ts ), $wgLang->date( $ts ) );
			return false;
		}
		return true;
	}
}
