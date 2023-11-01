<?php

namespace MediaWiki\Extension\Wikistories\Jobs;

use Config;
use ExtensionRegistry;
use IJobSpecification;
use Job;
use JobSpecification;
use MediaWiki\Extension\Wikistories\Hooks\EchoNotificationsHandlers;
use MediaWiki\Extension\Wikistories\PageLinksSearch;
use MediaWiki\Extension\Wikistories\StoryContent;
use MediaWiki\Extension\Wikistories\StoryContentAnalyzer;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

class ArticleChangedJob extends Job {

	private const COMMAND = 'ArticleChangedJob';

	/** @var StoryContentAnalyzer */
	private $analyzer;

	/** @var RevisionLookup */
	private $revisionLookup;

	/** @var WikiPageFactory */
	private $wikiPageFactory;

	/** @var PageLinksSearch */
	private $pageLinksSearch;

	/** @var Config */
	private $config;

	/**
	 * @param string $command
	 * @param array $params
	 * @param RevisionLookup $revisionLookup
	 * @param StoryContentAnalyzer $analyzer
	 * @param WikiPageFactory $wikiPageFactory
	 * @param PageLinksSearch $pageLinksSearch
	 * @param Config $config
	 */
	public function __construct(
		$command,
		$params,
		RevisionLookup $revisionLookup,
		StoryContentAnalyzer $analyzer,
		WikiPageFactory $wikiPageFactory,
		PageLinksSearch $pageLinksSearch,
		Config $config
	) {
		parent::__construct( self::COMMAND, $params );
		// Delay to let multiple edits be deduplicated
		$params[ 'jobReleaseTimestamp' ] = time() + 60;
		$this->analyzer = $analyzer;
		$this->revisionLookup = $revisionLookup;
		$this->wikiPageFactory = $wikiPageFactory;
		$this->pageLinksSearch = $pageLinksSearch;
		$this->config = $config;
	}

	/**
	 * @param int $pageId
	 * @return IJobSpecification
	 */
	public static function newSpec( int $pageId ): IJobSpecification {
		return new JobSpecification(
			self::COMMAND,
			[ 'article_id' => $pageId ],
			[ 'removeDuplicates' => true ]
		);
	}

	/**
	 * Run the job
	 *
	 * @return bool Success
	 */
	public function run() {
		$notify = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) &&
			$this->config->get( 'WikistoriesNotifyAboutStoryMaintenance' );
		$articleId = $this->params[ 'article_id' ];
		$rev = $this->revisionLookup->getRevisionByPageId( $articleId );
		$agent = $rev->getUser();
		$articleTitle = $rev->getPage()->getDBkey();
		$pageIds = $this->pageLinksSearch->getPageLinks( $articleTitle, 99 );
		foreach ( $pageIds as $pageId ) {
			$page = $this->wikiPageFactory->newFromID( $pageId );
			/** @var StoryContent $story */
			$story = $page->getContent();
			'@phan-var StoryContent $story';
			if ( $notify && $this->analyzer->hasOutdatedText( $story ) ) {
				$this->notify( $agent, $page->getTitle(), $articleTitle, $rev->getId() );
			}
			$page->doPurge();
		}
		return true;
	}

	/**
	 * @param UserIdentity $agent
	 * @param Title $storyTitle
	 * @param string $articleTitle
	 * @param int $revId
	 */
	private function notify( UserIdentity $agent, Title $storyTitle, string $articleTitle, int $revId ) {
		\EchoEvent::create( [
			'type' => EchoNotificationsHandlers::NOTIFICATION_TYPE,
			'agent' => $agent,
			'title' => $storyTitle,
			'extra' => [
				'articleTitle' => $articleTitle,
				'articleRevId' => $revId,
				'notifyAgent' => true,
			],
		] );
	}
}
