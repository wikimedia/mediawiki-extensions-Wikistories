<?php

namespace MediaWiki\Extension\Wikistories\Hooks;

use ExtensionRegistry;
use IContextSource;
use MediaWiki\Config\Config;
use MediaWiki\Extension\BetaFeatures\BetaFeatures;
use MediaWiki\Extension\Wikistories\Hooks;
use MediaWiki\MediaWikiServices;
use MediaWiki\Output\OutputPage;
use MediaWiki\Title\Title;
use MediaWiki\User\Options\UserOptionsLookup;
use MediaWiki\User\User;
use MobileFrontend\Hooks\BeforePageDisplayMobileHook;
use Skin;

class MobileFrontendHandlers implements BeforePageDisplayMobileHook {

	/**
	 * @param Skin $skin
	 * @return bool
	 */
	private static function shouldShowStoriesOnSkin( Skin $skin ) {
		return $skin->getSkinName() === 'minerva';
	}

	/**
	 * @param IContextSource $context
	 * @return bool
	 */
	private static function shouldShowStoriesForAction( IContextSource $context ) {
		return $context->getActionName() === 'view' &&
			$context->getRequest()->getText( 'diff' ) === '';
	}

	/**
	 * @param User $user
	 * @param Config $config
	 * @return bool
	 */
	private static function shouldShowStoriesForUser( User $user, Config $config ): bool {
		if ( Hooks::isBetaDiscoveryMode( $config ) ) {
			return $user->isNamed()
				&& ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' )
				&& BetaFeatures::isFeatureEnabled( $user, BetaFeaturesHandlers::WIKISTORIES_BETA_FEATURE );
		} elseif ( Hooks::isPublicDiscoveryMode( $config ) ) {
			/** @var UserOptionsLookup $userOptionsLookup */
			$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
			return ( $user->isAnon() || $user->isTemp() )
				|| (bool)$userOptionsLookup->getOption( $user, Hooks::WIKISTORIES_PREF_SHOW_DISCOVERY, true );
		} else {
			// unknown discovery mode
			return false;
		}
	}

	/**
	 * @param Title $title
	 * @return bool
	 */
	private static function shouldShowStoriesOnPage( Title $title ): bool {
		return !$title->isMainPage()
			&& $title->inNamespace( NS_MAIN )
			&& $title->exists();
	}

	/**
	 * @param User $user
	 * @param Title $title
	 * @param Skin $skin
	 * @param IContextSource $context
	 * @return bool
	 */
	private static function shouldShowStories( User $user, Title $title, Skin $skin, IContextSource $context ): bool {
		return self::shouldShowStoriesForUser( $user, $context->getConfig() )
			&& self::shouldShowStoriesOnSkin( $skin )
			&& self::shouldShowStoriesOnPage( $title )
			&& self::shouldShowStoriesForAction( $context );
	}

	/**
	 * @param OutputPage &$out
	 * @param Skin &$skin
	 */
	public function onBeforePageDisplayMobile( OutputPage &$out, Skin &$skin ) {
		$title = $out->getTitle();
		if ( self::shouldShowStories( $out->getUser(), $title, $out->getSkin(), $out->getContext() ) ) {
			$out->addModules( [ 'ext.wikistories.discover' ] );
			$out->addModuleStyles( 'ext.wikistories.discover.styles' );
		}
	}
}
