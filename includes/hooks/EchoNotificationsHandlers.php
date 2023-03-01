<?php

namespace MediaWiki\Extension\Wikistories\hooks;

use EchoAttributeManager;
use EchoEvent;
use EchoUserLocator;
use MediaWiki\Extension\Wikistories\notifications\ArticleChangedPresentationModel;

class EchoNotificationsHandlers {

	public const NOTIFICATION_TYPE = 'wikistories-articlechanged';

	/**
	 * Handler for BeforeCreateEchoEvent hook
	 * @see https://www.mediawiki.org/wiki/Extension:Echo/BeforeCreateEchoEvent
	 * @see https://www.mediawiki.org/wiki/Notifications/Developer_guide
	 *
	 * @param array[] &$notifications
	 * @param array[] &$notificationCategories
	 * @param array[] &$icons
	 */
	public function onBeforeCreateEchoEvent(
		array &$notifications,
		array &$notificationCategories,
		array &$icons
	) {
		$notificationCategories[ 'wikistories-action' ] = [
			'priority' => 5,
			'tooltip' => 'echo-pref-tooltip-wikistories-action',
		];

		$notifications[ self::NOTIFICATION_TYPE ] = [
			EchoAttributeManager::ATTR_LOCATORS => [
				EchoUserLocator::class . '::locateUsersWatchingTitle',
			],
			'category' => 'wikistories-action',
			'group' => 'neutral',
			'section' => 'alert',
			'presentation-model' => ArticleChangedPresentationModel::class,
			'bundle' => [ 'web' => true, 'email' => true, 'expandable' => false ],
		];

		$icons[ self::NOTIFICATION_TYPE ] = [
			'path' => 'Wikistories/resources/images/edit.svg',
		];
	}

	/**
	 * @param EchoEvent $event
	 * @param string &$bundleString
	 */
	public function onEchoGetBundleRules( $event, &$bundleString ) {
		if ( $event->getType() === self::NOTIFICATION_TYPE ) {
			$bundleString = $event->getTitle()->getPrefixedDBkey();
		}
	}

}
