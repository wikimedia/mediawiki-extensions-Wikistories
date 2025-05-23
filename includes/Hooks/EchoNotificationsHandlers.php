<?php

namespace MediaWiki\Extension\Wikistories\Hooks;

use MediaWiki\Extension\Notifications\Model\Event;

class EchoNotificationsHandlers {

	public const NOTIFICATION_TYPE = 'wikistories-articlechanged';

	/**
	 * @param Event $event
	 * @param string &$bundleString
	 */
	public function onEchoGetBundleRules( $event, &$bundleString ) {
		if ( $event->getType() === self::NOTIFICATION_TYPE ) {
			$bundleString = $event->getTitle()->getPrefixedDBkey();
		}
	}

}
