<?php

namespace MediaWiki\Extension\Wikistories;

use Article;
use MediaWiki\Config\Config;
use MediaWiki\Deferred\DeferredUpdates;
use MediaWiki\Extension\Wikistories\Jobs\ArticleChangedJob;
use MediaWiki\Hook\ActionModifyFormFieldsHook;
use MediaWiki\Hook\LoginFormValidErrorMessagesHook;
use MediaWiki\Hook\ParserCacheSaveCompleteHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Page\Hook\ArticlePurgeHook;
use MediaWiki\Parser\ParserCache;
use MediaWiki\Parser\ParserOptions;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;
use MediaWiki\User\User;
use WikiPage;

class Hooks implements
	LoginFormValidErrorMessagesHook,
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
