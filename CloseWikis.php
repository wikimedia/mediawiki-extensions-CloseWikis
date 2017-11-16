<?php
/**
 * Copyright (C) 2008 Victor Vasiliev <vasilvv@gmail.com>
 * Copyright (C) 2014 Hydriz Scholz
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
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'CloseWikis' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['CloseWikis'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['CloseWikisAlias'] = __DIR__ . '/CloseWikis.alias.php';
	wfWarn(
		'Deprecated PHP entry point used for the CloseWikis extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the CloseWikis extension requires MediaWiki 1.29+' );
}
