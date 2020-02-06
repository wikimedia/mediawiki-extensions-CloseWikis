<?php

use MediaWiki\MediaWikiServices;

class CloseWikis {
	static $cachedList = null;

	static function getReplicaDB() {
		global $wgCloseWikisDatabase;
		return wfGetDB( DB_REPLICA, 'closewikis', $wgCloseWikisDatabase );
	}

	static function getMasterDB() {
		global $wgCloseWikisDatabase;
		return wfGetDB( DB_MASTER, 'closewikis', $wgCloseWikisDatabase );
	}

	/** Returns list of all closed wikis in form of CloseWikisRow array. Not cached */
	static function getAll() {
		$list = [];
		$dbr = self::getReplicaDB();
		$result = $dbr->select( 'closedwikis', '*', false, __METHOD__ );
		foreach ( $result as $row ) {
			$list[] = new CloseWikisRow( $row );
		}
		$dbr->freeResult( $result );
		return $list;
	}

	/** Returns list of closed wikis in form of string array. Cached in CloseWikis::$cachedList */
	static function getList() {
		if ( self::$cachedList ) {
			return self::$cachedList;
		}
		$list = [];
		$dbr = self::getMasterDB();	// Used only on writes
		$result = $dbr->select( 'closedwikis', 'cw_wiki', false, __METHOD__ );
		foreach ( $result as $row ) {
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

	/**
	 * Returns a CloseWikisRow for specific wiki
	 *
	 * @param string $wikiId
	 * @return CloseWikisRow
	 */
	static function getClosedRow( $wikiId ) {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();

		$fname = __METHOD__;
		$map = $cache->getWithSetCallback(
			$cache->makeGlobalKey( 'closedwikis-state', $wikiId ),
			$cache::TTL_MONTH,
			function () use ( $wikiId, $fname ) {
				$dbr = self::getReplicaDB();
				$row = $dbr->selectRow( 'closedwikis', '*', [ 'cw_wiki' => $wikiId ], $fname );

				return $row ? (array)$row : [];
			}
		);

		return new CloseWikisRow( $map ? (object)$map : false );
	}

	/**
	 * Closes a wiki
	 *
	 * @param string $wikiId
	 * @param string $dispreason
	 * @param $by User
	 * @return bool
	 */
	static function close( $wikiId, $dispreason, $by ) {
		$dbw = self::getMasterDB();
		$dbw->startAtomic( __METHOD__ );
		$dbw->insert(
			'closedwikis',
			[
				'cw_wiki' => $wikiId,
				'cw_reason' => $dispreason,
				'cw_timestamp' => $dbw->timestamp( wfTimestampNow() ),
				'cw_by' => $by->getName(),
			],
			__METHOD__,
			[ 'IGNORE' ]	// Better error handling
		);
		$result = (bool)$dbw->affectedRows();
		$dbw->endAtomic( __METHOD__ );

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cache->delete( $cache->makeGlobalKey( 'closedwikis-state', $wikiId ) );

		self::$cachedList = null;
		return $result;
	}

	/**
	 * Reopens a wiki
	 *
	 * @param string $wikiId
	 * @return bool
	 */
	static function reopen( $wikiId ) {
		$dbw = self::getMasterDB();
		$dbw->startAtomic( __METHOD__ );
		$dbw->delete(
			'closedwikis',
			[ 'cw_wiki' => $wikiId ],
			__METHOD__
		);
		$result = (bool)$dbw->affectedRows();
		$dbw->endAtomic( __METHOD__ );

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cache->delete( $cache->makeGlobalKey( 'closedwikis-state', $wikiId ) );

		self::$cachedList = null;
		return $result;
	}
}
