<?php

namespace MediaWiki\Extension\Wikistories;

use DeferredUpdates;
use ExtensionRegistry;
use IContextSource;
use ManualLogEntry;
use MediaWiki\Extension\BetaFeatures\BetaFeatures;
use MediaWiki\Extension\Wikistories\jobs\ArticleChangedJob;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Permissions\Authority;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserOptionsLookup;
use OutputPage;
use ParserCache;
use ParserOptions;
use ParserOutput;
use RequestContext;
use Skin;
use SpecialPage;
use Title;
use User;
use WikiPage;

class Hooks {

	private const WIKISTORIES_BETA_FEATURE = 'wikistories-storiesonarticles';

	private const WIKISTORIES_PREF_SHOW_DISCOVERY = 'wikistories-pref-showdiscovery';

	private const WIKISTORIES_MODE_BETA = 'beta';

	private const WIKISTORIES_MODE_PUBLIC = 'public';

	private const WIKISTORIES_PREF_VIEWER_TEXTSIZE = 'wikistories-pref-viewertextsize';

	/**
	 * Register a beta feature that lets users show stories on article pages
	 *
	 * @param User $user
	 * @param array &$betaPrefs
	 */
	public static function onGetBetaFeaturePreferences( User $user, array &$betaPrefs ) {
		if ( !self::isBetaDiscoveryMode( RequestContext::getMain() ) ) {
			return;
		}
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
	 * @param array &$preferences
	 */
	public static function onGetPreferences( User $user, array &$preferences ) {
		if ( self::isPublicDiscoveryMode( RequestContext::getMain() ) ) {
			$preferences[ self::WIKISTORIES_PREF_SHOW_DISCOVERY ] = [
				'section' => 'rendering/wikistories',
				'label-message' => 'wikistories-pref-showdiscovery-message',
				'help-message' => 'wikistories-pref-showdiscovery-help-message',
				'type' => 'toggle',
			];
		}
		$preferences[ self::WIKISTORIES_PREF_VIEWER_TEXTSIZE ] = [
			'type' => 'api',
		];
	}

	/**
	 * @param User $user
	 * @param Title $title
	 * @param Skin $skin
	 * @param IContextSource $context
	 * @return bool
	 */
	private static function shouldShowStories( User $user, Title $title, Skin $skin, IContextSource $context ): bool {
		return self::shouldShowStoriesForUser( $user, $context )
			&& self::shouldShowStoriesOnSkin( $skin )
			&& self::shouldShowStoriesOnPage( $title )
			&& self::shouldShowStoriesForAction( $context->getActionName() );
	}

	/**
	 * @param IContextSource $context
	 * @return mixed
	 */
	private static function getDiscoveryMode( IContextSource $context ) {
		return $context->getConfig()->get( 'WikistoriesDiscoveryMode' );
	}

	/**
	 * @param IContextSource $context
	 * @return bool
	 */
	private static function isBetaDiscoveryMode( IContextSource $context ): bool {
		return self::getDiscoveryMode( $context ) === self::WIKISTORIES_MODE_BETA;
	}

	/**
	 * @param IContextSource $context
	 * @return bool
	 */
	private static function isPublicDiscoveryMode( IContextSource $context ): bool {
		return self::getDiscoveryMode( $context ) === self::WIKISTORIES_MODE_PUBLIC;
	}

	/**
	 * @param User $user
	 * @param IContextSource $context
	 * @return bool
	 */
	private static function shouldShowStoriesForUser( User $user, IContextSource $context ): bool {
		if ( self::isBetaDiscoveryMode( $context ) ) {
			return $user->isRegistered()
				&& ExtensionRegistry::getInstance()->isLoaded( 'BetaFeatures' )
				// @phan-suppress-next-line PhanUndeclaredClassMethod
				&& BetaFeatures::isFeatureEnabled( $user, self::WIKISTORIES_BETA_FEATURE );
		} elseif ( self::isPublicDiscoveryMode( $context ) ) {
			/** @var UserOptionsLookup $userOptionsLookup */
			$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
			return $user->isAnon()
				|| (bool)$userOptionsLookup->getOption( $user, self::WIKISTORIES_PREF_SHOW_DISCOVERY, true );
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
	 * @param ProperPageIdentity $page
	 */
	private static function purgeStories( ProperPageIdentity $page ) {
		$services = MediaWikiServices::getInstance();
		/** @var PageLinksSearch $pageLinksSearch */
		$pageLinksSearch = $services->get( 'Wikistories.PageLinksSearch' );
		$wikiPageFactory = $services->getWikiPageFactory();
		$storiesId = $pageLinksSearch->getPageLinks( $page->getDBkey(), 99 );
		foreach ( $storiesId as $storyId ) {
			$page = $wikiPageFactory->newFromID( $storyId );
			$page->doPurge();
		}
	}

	/**
	 * @param Skin $skin
	 * @return bool
	 */
	private static function shouldShowStoriesOnSkin( Skin $skin ) {
		return $skin->getSkinName() === 'minerva';
	}

	/**
	 * @param string $action
	 * @return bool
	 */
	private static function shouldShowStoriesForAction( string $action ) {
		return $action === 'view';
	}

	/**
	 * @param OutputPage $out
	 */
	public static function onBeforePageDisplayMobile( OutputPage $out ) {
		$title = $out->getTitle();
		if ( self::shouldShowStories( $out->getUser(), $title, $out->getSkin(), $out->getContext() ) ) {
			$out->addModules( [ 'ext.wikistories.discover' ] );
			$out->addModuleStyles( 'ext.wikistories.discover.styles' );
		}
	}

	/**
	 * @return array Data used by the 'discover' module
	 */
	public static function getDiscoverBundleData(): array {
		return [ 'storyBuilder' => SpecialPage::getTitleValueFor( 'StoryBuilder' )->getText() ];
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

		if ( $wikiPage->getContentModel() !== 'story' ) {
			return;
		}

		DeferredUpdates::addCallableUpdate( static function () use ( $wikiPage, $revisionRecord ) {
			$services = MediaWikiServices::getInstance();
			/** @var StoriesCache $cache */
			$cache = $services->get( 'Wikistories.Cache' );
			/** @var StoryContent $story */
			$story = $revisionRecord->getContent( 'main' );
			'@phan-var StoryContent $story';
			$articleTitle = $story->getArticleTitle( $services->getPageStore(), $services->getRedirectLookup() );
			if ( $articleTitle ) {
				$cache->invalidateForArticle( $articleTitle->getId() );
			}
			$cache->invalidateStory( $wikiPage->getId() );
		} );
	}

	/**
	 * Do purge stories when article is deleted
	 * Invalidate stories cache for the related article
	 *
	 * @param ProperPageIdentity $page
	 * @param Authority $deleter
	 * @param string $reason
	 * @param int $pageID
	 * @param RevisionRecord $deletedRev
	 * @param ManualLogEntry $logEntry
	 * @param int $archivedRevisionCount
	 */
	public static function onPageDeleteComplete(
		ProperPageIdentity $page,
		Authority $deleter,
		string $reason,
		int $pageID,
		RevisionRecord $deletedRev,
		ManualLogEntry $logEntry,
		int $archivedRevisionCount
	) {
		// NS_MAIN deletion
		if ( $page->getNamespace() === NS_MAIN ) {
			DeferredUpdates::addCallableUpdate( static function () use ( $page ) {
				self::purgeStories( $page );
			} );
			return;
		}

		// NS_STORY deletion
		if ( $page->getNamespace() !== NS_STORY ) {
			return;
		}

		$story = $deletedRev->getContent( 'main' );
		if ( !( $story instanceof StoryContent ) ) {
			return;
		}

		DeferredUpdates::addCallableUpdate( static function () use ( $pageID ) {
			$services = MediaWikiServices::getInstance();
			/** @var StoriesCache $cache */
			$cache = $services->get( 'Wikistories.Cache' );
			$cache->invalidateForArticle( $pageID );
		} );
	}

	/**
	 * Do purge stories when article is undeleted
	 *
	 * @param ProperPageIdentity $page
	 * @param Authority $restorer
	 * @param string $reason
	 * @param RevisionRecord $restoredRev
	 * @param ManualLogEntry $logEntry
	 * @param int $restoredRevisionCount
	 * @param bool $created
	 * @param array $restoredPageIds
	 */
	public static function onPageUndeleteComplete(
		ProperPageIdentity $page,
		Authority $restorer,
		string $reason,
		RevisionRecord $restoredRev,
		ManualLogEntry $logEntry,
		int $restoredRevisionCount,
		bool $created,
		array $restoredPageIds
	) {
		// NS_MAIN deletion
		if ( $page->getNamespace() === NS_MAIN ) {
			DeferredUpdates::addCallableUpdate( static function () use ( $page ) {
				self::purgeStories( $page );
			} );
			return;
		}
	}

	/**
	 * @param ParserCache $parserCache
	 * @param ParserOutput $parserOutput
	 * @param Title $title
	 * @param ParserOptions $parserOptions
	 * @param int $revId
	 */
	public static function onParserCacheSaveComplete(
		ParserCache $parserCache,
		ParserOutput $parserOutput,
		Title $title,
		ParserOptions $parserOptions,
		int $revId
	) {
		if ( $title->getNamespace() !== NS_MAIN ) {
			return;
		}

		if ( $parserOptions->getRenderReason() !== 'edit-page' ) {
			// Don't want to trigger story outdated verification for any other reason
			return;
		}

		DeferredUpdates::addCallableUpdate( static function () use ( $title ) {
			/** @var PageLinksSearch $pageLinkSearch */
			$pageLinkSearch = MediaWikiServices::getInstance()->get( 'Wikistories.PageLinksSearch' );
			$links = $pageLinkSearch->getPageLinks( $title->getDBkey(), 1 );
			if ( count( $links ) === 0 ) {
				return;
			}

			$job = ArticleChangedJob::newSpec( $title->getId() );
			MediaWikiServices::getInstance()->getJobQueueGroup()->push( $job );
		} );
	}

}
