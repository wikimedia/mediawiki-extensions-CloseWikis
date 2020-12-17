<?php
class CloseWikisRow {
	/**
	 * @var stdClass|false
	 */
	private $mRow;

	public function __construct( $row ) {
		$this->mRow = $row;
	}

	public function isClosed() {
		return (bool)$this->mRow;
	}

	public function getWiki() {
		return $this->mRow ? $this->mRow->cw_wiki : null;
	}

	public function getReason() {
		return $this->mRow ? $this->mRow->cw_reason : null;
	}

	public function getTimestamp() {
		return $this->mRow ? wfTimestamp( TS_MW, $this->mRow->cw_timestamp ) : null;
	}

	public function getBy() {
		return $this->mRow ? $this->mRow->cw_by : null;
	}
}
