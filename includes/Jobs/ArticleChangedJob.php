<?php

namespace MediaWiki\Extension\Wikistories\Jobs;

use MediaWiki\Config\Config;
use MediaWiki\Extension\Wikistories\Hooks\EchoNotificationsHandlers;
use MediaWiki\Extension\Wikistories\PageLinksSearch;
use MediaWiki\Extension\Wikistories\StoryContent;
use MediaWiki\Extension\Wikistories\StoryContentAnalyzer;
use MediaWiki\JobQueue\IJobSpecification;
use MediaWiki\JobQueue\Job;
use MediaWiki\JobQueue\JobSpecification;
use MediaWiki\MediaWikiServices;
use MediaWiki\Notification\RecipientSet;
use MediaWiki\Notification\Types\WikiNotification;
use MediaWiki\Page\WikiPageFactory;
use MediaWiki\Registration\ExtensionRegistry;
use MediaWiki\Revision\RevisionLookup;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;

class ArticleChangedJob extends Job {

	private const COMMAND = 'ArticleChangedJob';

	public function __construct(
		string $command,
		array $params,
		private readonly RevisionLookup $revisionLookup,
		private readonly StoryContentAnalyzer $analyzer,
		private readonly WikiPageFactory $wikiPageFactory,
		private readonly PageLinksSearch $pageLinksSearch,
		private readonly Config $config,
	) {
		// Delay to let multiple edits be deduplicated
		$params[ 'jobReleaseTimestamp' ] = time() + 60;
		parent::__construct( self::COMMAND, $params );
	}

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
	public function run(): bool {
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

	private function notify( UserIdentity $agent, Title $storyTitle, string $articleTitle, int $revId ): void {
		MediaWikiServices::getInstance()->getNotificationService()->notify(
			new WikiNotification( EchoNotificationsHandlers::NOTIFICATION_TYPE, $storyTitle, $agent, [
				'articleTitle' => $articleTitle,
				'articleRevId' => $revId,
				'notifyAgent' => true,
			] ),
			new RecipientSet( [] )
		);
	}
}
