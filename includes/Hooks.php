<?php

namespace MediaWiki\Extension\Wikistories;

use Article;
use ManualLogEntry;
use MediaWiki\Config\Config;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\Wikistories\Jobs\ArticleChangedJob;
use MediaWiki\Hook\ActionModifyFormFieldsHook;
use MediaWiki\Hook\LoginFormValidErrorMessagesHook;
use MediaWiki\Hook\ParserCacheSaveCompleteHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\Hook\ArticlePurgeHook;
use MediaWiki\Page\Hook\PageDeleteCompleteHook;
use MediaWiki\Page\Hook\PageUndeleteCompleteHook;
use MediaWiki\Page\ProperPageIdentity;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Permissions\Authority;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Storage\EditResult;
use MediaWiki\Storage\Hook\PageSaveCompleteHook;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use MediaWiki\User\UserIdentity;
use ParserCache;
use ParserOptions;
use RequestContext;
use WikiPage;

class Hooks implements
	LoginFormValidErrorMessagesHook,
	PageSaveCompleteHook,
	PageDeleteCompleteHook,
	PageUndeleteCompleteHook,
	GetPreferencesHook,
	ParserCacheSaveCompleteHook,
	ArticlePurgeHook,
	ActionModifyFormFieldsHook
{

	public const WIKISTORIES_PREF_SHOW_DISCOVERY = 'wikistories-pref-showdiscovery';

	private const WIKISTORIES_MODE_BETA = 'beta';

	private const WIKISTORIES_MODE_PUBLIC = 'public';

	private const WIKISTORIES_PREF_VIEWER_TEXTSIZE = 'wikistories-pref-viewertextsize';

	/** @var Config */
	private $mainConfig;

	/**
	 * @param Config $mainConfig
	 */
	public function __construct( Config $mainConfig ) {
		$this->mainConfig = $mainConfig;
	}

	/**
	 * @param User $user
	 * @param array &$preferences
	 */
	public function onGetPreferences( $user, &$preferences ) {
		if ( self::isPublicDiscoveryMode( $this->mainConfig ) ) {
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
	 * @param Config $config
	 * @return mixed
	 */
	private static function getDiscoveryMode( Config $config ) {
		return $config->get( 'WikistoriesDiscoveryMode' );
	}

	/**
	 * @param Config $config
	 * @return bool
	 */
	public static function isBetaDiscoveryMode( Config $config ): bool {
		return self::getDiscoveryMode( $config ) === self::WIKISTORIES_MODE_BETA;
	}

	/**
	 * @param Config $config
	 * @return bool
	 */
	public static function isPublicDiscoveryMode( Config $config ): bool {
		return self::getDiscoveryMode( $config ) === self::WIKISTORIES_MODE_PUBLIC;
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
	 * @param ProperPageIdentity $page
	 * @param Authority $deleter
	 */
	private static function deleteStories( ProperPageIdentity $page, Authority $deleter ) {
		$request = RequestContext::getMain()->getRequest();
		$services = MediaWikiServices::getInstance();
		/** @var PageLinksSearch $pageLinksSearch */
		$pageLinksSearch = $services->get( 'Wikistories.PageLinksSearch' );
		$wikiPageFactory = $services->getWikiPageFactory();
		$deletePageFactory = $services->getDeletePageFactory();
		$storiesId = $pageLinksSearch->getPageLinks( $page->getDBkey(), 99 );
		foreach ( $storiesId as $storyId ) {
			$page = $wikiPageFactory->newFromID( $storyId );
			$deletePage = $deletePageFactory->newDeletePage(
				$page,
				$deleter
			);
			$deletePage
				->setSuppress( $request->getBool( 'wpSuppress' ) )
				->deleteIfAllowed( $request->getText( 'wpReason' ) );
		}
	}

	/**
	 * @return array Data used by the 'discover' module
	 */
	public static function getDiscoverBundleData(): array {
		return [ 'storyBuilder' => SpecialPage::getTitleValueFor( 'StoryBuilder' )->getText() ];
	}

	/**
	 * @return array Data used by the 'builder' module to get title translation
	 */
	public static function getArticleSectionTitle(): array {
		return [
			'See_also' => [
				'en' => 'See_also',
				'id' => 'Lihat_pula'
			]
		];
	}

	/**
	 * Register a message to make sure Special:StoryBuilder can redirect
	 * to the login page when the user is logged out.
	 *
	 * @param string[] &$messages List of messages valid on login screen
	 */
	public function onLoginFormValidErrorMessages( array &$messages ) {
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
	public function onPageSaveComplete(
		$wikiPage,
		$user,
		$summary,
		$flags,
		$revisionRecord,
		$editResult
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
	public function onPageDeleteComplete(
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
			$deleteStories = RequestContext::getMain()->getRequest()->getBool( 'wpDeleteStory' );
			DeferredUpdates::addCallableUpdate( static function () use ( $page, $deleter, $deleteStories ) {
				if ( $deleteStories ) {
					self::deleteStories( $page, $deleter );
				} else {
					self::purgeStories( $page );
				}
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
	public function onPageUndeleteComplete(
		ProperPageIdentity $page,
		Authority $restorer,
		string $reason,
		RevisionRecord $restoredRev,
		ManualLogEntry $logEntry,
		int $restoredRevisionCount,
		bool $created,
		array $restoredPageIds
	): void {
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
	public function onParserCacheSaveComplete(
		$parserCache,
		$parserOutput,
		$title,
		$parserOptions,
		$revId
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

	/**
	 * @param WikiPage $wikiPage
	 * @return void
	 */
	public function onArticlePurge( $wikiPage ) {
		if ( $wikiPage->getNamespace() !== NS_STORY ) {
			return;
		}

		$services = MediaWikiServices::getInstance();
		/** @var StoriesCache $cache */
		$cache = $services->get( 'Wikistories.Cache' );
		$cache->invalidateStory( $wikiPage->getId() );
	}

	/**
	 * @param string $name
	 * @param array &$fields
	 * @param Article $article
	 */
	public function onActionModifyFormFields(
		$name,
		&$fields,
		$article
	) {
		// skip when not delete action and not an article
		if ( $name !== 'delete' || $article->getPage()->getNamespace() !== NS_MAIN ) {
			return;
		}

		// skip when no stories found in this article
		$pageLinkSearch = MediaWikiServices::getInstance()->get( 'Wikistories.PageLinksSearch' );
		$title = $article->getPage()->getTitle()->getDBkey();
		$links = $pageLinkSearch->getPageLinks( $title, 1 );
		if ( count( $links ) === 0 ) {
			return;
		}

		// Add DeleteStory Field before ConfirmB
		// @todo Add Unit Test to prevent UI break when DeleteAction.php change
		$confirmBField = $fields[ 'ConfirmB' ];
		unset( $fields[ 'ConfirmB' ] );
		$fields[ 'DeleteStory' ] = [
			'type' => 'check',
			'id' => 'wpDeleteStory',
			'default' => true,
			'tabIndex' => $confirmBField[ 'tabindex' ] + 1,
			'label-message' => 'deletepage-deletestory'
		];
		$fields[ 'ConfirmB' ] = $confirmBField;
	}
}
