<?php

namespace MediaWiki\Extension\Wikistories;

use DeferredUpdates;
use ExtensionRegistry;
use MediaWiki\Extension\BetaFeatures\BetaFeatures;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use OutputPage;
use SpecialPage;
use Title;
use User;
use WikiPage;

class Hooks {

	private const WIKISTORIES_BETA_FEATURE = 'wikistories-storiesonarticles';

	/**
	 * Register a beta feature that lets users show stories on article pages
	 *
	 * @param User $user
	 * @param array &$betaPrefs
	 */
	public static function onGetBetaFeaturePreferences( User $user, array &$betaPrefs ) {
		$extensionAssetsPath = MediaWikiServices::getInstance()
			->getMainConfig()
			->get( 'ExtensionAssetsPath' );
		$betaPrefs[ self::WIKISTORIES_BETA_FEATURE ] = [
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

	/**
	 * @param User $user
	 * @param Title $title
	 * @return bool
	 */
	private static function shouldShowStories( User $user, Title $title ): bool {
		return $user->isRegistered()
			&& ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' )
			// @phan-suppress-next-line PhanUndeclaredClassMethod
			&& BetaFeatures::isFeatureEnabled( $user, self::WIKISTORIES_BETA_FEATURE )
			&& !$title->isMainPage()
			&& $title->inNamespace( NS_MAIN )
			&& $title->exists();
	}

	/**
	 * @param OutputPage $out
	 */
	public static function onBeforePageDisplayMobile( OutputPage $out ) {
		$title = $out->getTitle();
		if ( self::shouldShowStories( $out->getUser(), $title ) ) {
			$out->addJsConfigVars(
				'wgWikistoriesCreateUrl',
				SpecialPage::getTitleFor( 'StoryBuilder', $title )->getLinkURL()
			);
			$out->addModules( [ 'mw.ext.story.discover' ] );
			$out->addModuleStyles( 'mw.ext.story.discover.styles' );
		}
	}

	/**
	 * Register a message to make sure Special:StoryBuilder can redirect
	 * to the login page when the user is logged out.
	 *
	 * @param string[] &$messages List of messages valid on login screen
	 */
	public static function onLoginFormValidErrorMessages( &$messages ) {
		$messages[] = 'wikistories-specialstorybuilder-mustbeloggedin';
	}

	/**
	 * When editing a story with the form, it is possible to change the 'Related article'
	 * to change which article the story will be shown on. The link to the new article will
	 * be done automatically with the page links but it will still show on the previous
	 * article because of the long-live stories cache.
	 *
	 * This hook invalidates the stories cache for the old article.
	 *
	 * @param WikiPage $wikiPage
	 * @param UserIdentity $user
	 * @param string $summary
	 * @param int $flags
	 * @param RevisionRecord $revisionRecord
	 * @param EditResult $editResult
	 */
	public static function onPageSaveComplete(
		WikiPage $wikiPage,
		UserIdentity $user,
		string $summary,
		int $flags,
		RevisionRecord $revisionRecord,
		EditResult $editResult
	) {
		if ( $wikiPage->getNamespace() !== NS_STORY ) {
			return;
		}

		if ( $editResult->isNew() ) {
			return;
		}

		DeferredUpdates::addCallableUpdate( static function () use ( $revisionRecord ) {
			$services = MediaWikiServices::getInstance();
			$previousRevision = $services->getRevisionStore()->getRevisionById( $revisionRecord->getParentId() );
			/** @var StoryContent $previousStory */
			$previousStory = $previousRevision->getContent( 'main' );
			'@phan-var StoryContent $previousStory';
			/** @var StoryContent $newStory */
			$newStory = $revisionRecord->getContent( 'main' );
			'@phan-var StoryContent $newStory';

			if ( $newStory->getFromArticle() !== $previousStory->getFromArticle() ) {
				/** @var StoriesCache $cache */
				$cache = $services->get( 'Wikistories.Cache' );
				$cache->invalidateForArticle( $previousStory->getFromArticle() );
			}
		} );
	}

}
