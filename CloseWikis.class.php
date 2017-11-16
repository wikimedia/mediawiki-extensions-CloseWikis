<?php
class CloseWikis {
	static $cachedList = null;

	static function getSlaveDB() {
		global $wgCloseWikisDatabase;
		return wfGetDB( DB_REPLICA, 'closewikis', $wgCloseWikisDatabase );
	}

	static function getMasterDB() {
		global $wgCloseWikisDatabase;
		return wfGetDB( DB_MASTER, 'closewikis', $wgCloseWikisDatabase );
	}

	/** Returns list of all closed wikis in form of CloseWikisRow array. Not cached */
	static function getAll() {
		$list = array();
		$dbr = self::getSlaveDB();
		$result = $dbr->select( 'closedwikis', '*', false, __METHOD__ );
		foreach( $result as $row ) {
			$list[] = new CloseWikisRow( $row );
		}
		$dbr->freeResult( $result );
		return $list;
	}

	/** Returns list of closed wikis in form of string array. Cached in CloseWikis::$cachedList */
	static function getList() {
		if( self::$cachedList ) {
			return self::$cachedList;
		}
		$list = array();
		$dbr = self::getMasterDB();	// Used only on writes
		$result = $dbr->select( 'closedwikis', 'cw_wiki', false, __METHOD__ );
		foreach( $result as $row ) {
			$list[] = $row->cw_wiki;
		}
		$dbr->freeResult( $result );
		self::$cachedList = $list;
		return $list;
	}

	/** Returns list of unclosed wikis in form of string array. Based on getList() */
	static function getUnclosedList() {
		global $wgLocalDatabases;
		return array_diff( $wgLocalDatabases, self::getList() );
	}

	/** Returns a CloseWikisRow for specific wiki. Cached in $wgMemc */
	static function getClosedRow( $wiki ) {
		global $wgMemc;
		$memcKey = "closedwikis:{$wiki}";
		$cached = $wgMemc->get( $memcKey );
		if( is_object( $cached ) ) {
			return $cached;
		}
		$dbr = self::getSlaveDB();
		$result = new CloseWikisRow( $dbr->selectRow( 'closedwikis', '*', array( 'cw_wiki' => $wiki ), __METHOD__ ) );
		$wgMemc->set( $memcKey, $result );
		return $result;
	}

	/** Closes a wiki
	 *
	 * @param $by User
	 */
	static function close( $wiki, $dispreason, $by ) {
		global $wgMemc;
		$dbw = self::getMasterDB();
		$dbw->startAtomic( __METHOD__ );
		$dbw->insert(
			'closedwikis',
			array(
				'cw_wiki' => $wiki,
				'cw_reason' => $dispreason,
				'cw_timestamp' => $dbw->timestamp( wfTimestampNow() ),
				'cw_by' => $by->getName(),
			),
			__METHOD__,
			array( 'IGNORE' )	// Better error handling
		);
		$result = (bool)$dbw->affectedRows();
		$dbw->endAtomic( __METHOD__ );
		$wgMemc->delete( "closedwikis:{$wiki}" );
		self::$cachedList = null;
		return $result;
	}

	/** Reopens a wiki */
	static function reopen( $wiki ) {
		global $wgMemc;
		$dbw = self::getMasterDB();
		$dbw->startAtomic( __METHOD__ );
		$dbw->delete(
			'closedwikis',
			array( 'cw_wiki' => $wiki ),
			__METHOD__
		);
		$result = (bool)$dbw->affectedRows();
		$dbw->endAtomic( __METHOD__ );
		$wgMemc->delete( "closedwikis:{$wiki}" );
		self::$cachedList = null;
		return $result;
	}
}
