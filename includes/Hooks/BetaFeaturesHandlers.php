<?php

namespace MediaWiki\Extension\Wikistories\Hooks;

use MediaWiki\Config\Config;
use MediaWiki\Extension\BetaFeatures\Hooks\GetBetaFeaturePreferencesHook;
use MediaWiki\Extension\Wikistories\Hooks;
use MediaWiki\MainConfigNames;
use MediaWiki\User\User;

class BetaFeaturesHandlers implements GetBetaFeaturePreferencesHook {

	public const WIKISTORIES_BETA_FEATURE = 'wikistories-storiesonarticles';

	public function __construct(
		private readonly Config $mainConfig,
	) {
	}

	/**
	 * Allows overwriting of beta feature preferences
	 *
	 * @param User $user User the preferences are for
	 * @param array &$betaFeatures
	 * @return bool|void True or no return value to continue or false to abort
	 */
	public function onGetBetaFeaturePreferences( User $user, array &$betaFeatures ) {
		if ( !Hooks::isBetaDiscoveryMode( $this->mainConfig ) ) {
			return;
		}
		$extensionAssetsPath = $this->mainConfig->get( MainConfigNames::ExtensionAssetsPath );
		$betaFeatures[ self::WIKISTORIES_BETA_FEATURE ] = [
			'label-message' => 'wikistories-beta-feature-message',
			'desc-message' => 'wikistories-beta-feature-description',
			'screenshot' => [
				'ltr' => "$extensionAssetsPath/Wikistories/resources/images/wikistories-betafeature-ltr.svg",
				'rtl' => "$extensionAssetsPath/Wikistories/resources/images/wikistories-betafeature-rtl.svg",
			],
			'info-link' => 'https://www.mediawiki.org/wiki/Wikistories',
			'discussion-link' => 'https://www.mediawiki.org/wiki/Talk:Wikistories',
		];
	}
}
