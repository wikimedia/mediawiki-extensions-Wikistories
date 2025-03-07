<?php

namespace MediaWiki\Extension\Wikistories;

use LocalRepo;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Wikistories\Hooks\RecentChangesPropagationHooks;
use MediaWiki\Page\DeletePage;
use MediaWiki\Page\DeletePageFactory;
use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\UltimateAuthority;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentityValue;
use MediaWikiIntegrationTestCase;
use PHPUnit\Framework\Assert;
use RepoGroup;
use WikiPage;

/**
 * @group Database
 * @covers MediaWiki\Extension\Wikistories\Hooks
 */
class PageHooksTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		// Set up fake repo
		$localRepo = $this->createNoOpMock(
			LocalRepo::class,
			[ 'invalidateImageRedirect' ]
		);

		$repoGroup = $this->createNoOpMock( RepoGroup::class, [
			'findFiles',
			'getLocalRepo'
		] );

		$repoGroup->method( 'findFiles' )
			->willReturnCallback( static function ( $names ) {
				return array_combine( $names, array_fill( 0, count( $names ), [] ) );
			} );

		$repoGroup->method( 'getLocalRepo' )
			->willReturn( $localRepo );

		$this->setService( 'RepoGroup', $repoGroup );
	}

	public function testCacheInvalidationOnStoryEdit() {
		// Create the target article
		$articleTitle = $this->getExistingTestPage()->getTitle();

		// Create the story page
		$storyTitle = $this->createStoryPage( $articleTitle );

		// flush updates from setup
		$this->runDeferredUpdates();

		// inject cache spy to perform assertions
		$this->injectCacheServiceSpy( [
			'invalidateForArticle'  => $articleTitle->getId(),
			'invalidateStory'  => $storyTitle->getId(),
		] );

		// edit story page
		$story = $this->makeFakeStoryData( $articleTitle );
		$story['frames'][] = [
			'image' => [ 'filename' => 'Foo.jpg' ],
			'text' => [ 'value' => 'Foo' ],
		];

		$content = new StoryContent( json_encode( $story ) );
		$this->editPage( $storyTitle, $content );

		// run updates
		$this->runDeferredUpdates();
	}

	public function testCacheInvalidationOnStoryDeletion() {
		// Create the target article
		$articleTitle = $this->getExistingTestPage()->getTitle();

		// Create the story page
		$storyTitle = $this->createStoryPage( $articleTitle );

		// flush updates from setup
		$this->runDeferredUpdates();

		// inject cache spy to perform assertions
		$this->injectCacheServiceSpy( [
			'invalidateForArticle'  => $articleTitle->getId(),
			'invalidateStory'  => null,
		] );

		// delete story page
		$this->deletePage( $storyTitle->toPageIdentity() );

		// run updates
		$this->runDeferredUpdates();
	}

	public function testCacheInvalidationOnArticleUndeletion() {
		// Create the target article
		$articleTitle = $this->getExistingTestPage()->getTitle();

		// Create the story page
		$storyTitle = $this->createStoryPage( $articleTitle );

		// Delete the article page
		$this->deletePage( $articleTitle->toPageIdentity() );

		// flush updates from setup
		$this->runDeferredUpdates();

		// Inject an ArticlePurge hook that checks that the story page is purged.
		$hookCalledWithId = null;
		$this->setTemporaryHook(
			'ArticlePurge',
			static function ( WikiPage $page ) use ( &$hookCalledWithId ) {
				$hookCalledWithId = $page->getId();
			}
		);

		// undelete article page
		$undeleter ??= new UltimateAuthority( new UserIdentityValue( 0, 'MediaWiki default' ) );
		$this->getServiceContainer()->getUndeletePageFactory()
			->newUndeletePage( $articleTitle->toPageIdentity(), $undeleter )
			->undeleteUnsafe( 'testing' );

		// run updates
		$this->runDeferredUpdates();

		// check that the hook was called on the correct page
		$this->assertSame( $storyTitle->getId(), $hookCalledWithId );
	}

	public function testPurgeStoryOnArticleDeletion() {
		// Create the target article
		$articleTitle = $this->getExistingTestPage()->getTitle();

		// Create the story page
		$storyTitle = $this->createStoryPage( $articleTitle );

		// flush updates from setup
		$this->runDeferredUpdates();

		// Inject fake PageLinkSearch that expects to be called with the article ID
		// and will return the story page's ID.
		$this->installFakePageLinkSearch( $articleTitle, $storyTitle );

		// Inject an ArticlePurge hook that checks that the story page is purged.
		$hookCalledWithId = null;
		$this->setTemporaryHook(
			'ArticlePurge',
			static function ( WikiPage $page ) use ( &$hookCalledWithId ) {
				$hookCalledWithId = $page->getId();
			}
		);

		// delete article page
		$this->deletePage( $articleTitle->toPageIdentity() );

		// run updates
		$this->runDeferredUpdates();

		// check that the hook was called on the correct page
		$this->assertSame( $storyTitle->getId(), $hookCalledWithId );

		// check that the story wasn't deleted
		$this->assertTrue(
			$this->getServiceContainer()->getPageStore()
				->getPageByReference( $storyTitle )
				->exists()
		);
	}

	public function testDeleteStoryOnArticleDeletion() {
		// Request deletion
		$fauxRequest = new FauxRequest( [
			'wpDeleteStory' => 1
		] );
		RequestContext::getMain()->setRequest( $fauxRequest );

		// Create the target article
		$articleTitle = $this->getExistingTestPage()->getTitle();

		// Create the story page
		$storyTitle = $this->createStoryPage( $articleTitle );

		// flush updates from setup
		$this->runDeferredUpdates();

		// Inject fake PageLinkSearch that expects to be called with the article ID
		// and will return the story page's ID.
		$this->installFakePageLinkSearch( $articleTitle, $storyTitle );

		// Before we fake the DeletePageFactory, grab a DeletePage object for
		// deleting the article.
		$deleter ??= new UltimateAuthority( new UserIdentityValue( 0, 'MediaWiki default' ) );
		$realDeletePageCommand = $this->getServiceContainer()
			->getDeletePageFactory()
			->newDeletePage( $articleTitle->toPageIdentity(), $deleter );

		// Inject a fake DeletePageFactory that expects to be called for the
		// story page
		$fakeDeletePageCommand = $this->createNoOpMock(
			DeletePage::class,
			[ 'setSuppress', 'deleteIfAllowed' ]
		);

		$fakeDeletePageCommand->method( 'setSuppress' )
			->willReturn( $fakeDeletePageCommand );

		$deletePageFactory = $this->createNoOpMock(
			DeletePageFactory::class,
			[ 'newDeletePage' ]
		);

		$deletePageFactory->expects( $this->once() )
			->method( 'newDeletePage' )
			->willReturnCallback( static function ( PageIdentity $pageToDelete )
				use ( $storyTitle, $fakeDeletePageCommand )
			{
				Assert::assertSame( $storyTitle->getDBkey(), $pageToDelete->getDBkey() );
				return $fakeDeletePageCommand;
			} );

		$this->setService(
			'DeletePageFactory',
			$deletePageFactory
		);

		// delete article page
		$realDeletePageCommand->deleteUnsafe( 'testing' );

		// run updates
		$this->runDeferredUpdates();
	}

	/**
	 * @covers MediaWiki\Extension\Wikistories\Hooks\RecentChangesPropagationHooks
	 */
	public function testRecentChangesPropagationOnStoryEdit() {
		// Create the target article
		$articleTitle = $this->getExistingTestPage()->getTitle();

		// Create the story page
		$storyTitle = $this->createStoryPage( $articleTitle );

		// flush updates from setup
		$this->runDeferredUpdates();
		$this->truncateTable( 'recentchanges' );

		// edit story page
		$story = $this->makeFakeStoryData( $articleTitle );
		$story['frames'][] = [
			'image' => [ 'filename' => 'Foo.jpg' ],
			'text' => [ 'value' => 'Foo' ],
		];

		$content = new StoryContent( json_encode( $story ) );
		$status = $this->editPage( $storyTitle, $content );
		$newRevision = $status->getNewRevision();

		// run updates
		$this->runDeferredUpdates();

		// find RecentChanges entry
		$rc = $this->getDb()->newSelectQueryBuilder()
			->select( '*' )->from( 'recentchanges' )
			->where( [
					'rc_type' => RC_EXTERNAL,
					'rc_cur_id' => $articleTitle->getId()
			] )
			->caller( __METHOD__ )
			->fetchRow();

		$this->assertNotNull( $rc );
		$this->assertSame( $newRevision->getId(), (int)$rc->rc_this_oldid );
		$this->assertSame( $articleTitle->getDBkey(), $rc->rc_title );
		$this->assertSame(
			RecentChangesPropagationHooks::SRC_WIKISTORIES,
			$rc->rc_source
		);
	}

	private function injectCacheServiceSpy( $expectations ) {
		$storiesCache = $this->createNoOpMock(
			StoriesCache::class,
			array_keys( $expectations )
		);

		foreach ( $expectations as $mth => $with ) {
			if ( $with === null ) {
				$storiesCache->expects( $this->never() )->method( $mth );
			} else {
				$storiesCache->expects( $this->once() )->method( $mth )
					->with( $with );
			}
		}

		$this->setService( 'Wikistories.Cache', $storiesCache );
	}

	private function makeFakeStoryData( PageIdentity $articleTitle ): array {
		return [
			'articleId' => $articleTitle->getId(),
			'frames' => [
				[
					'image' => [ 'filename' => 'Foo.jpg' ],
					'text' => [ 'value' => 'Foo' ],
				],
				[
					'image' => [ 'filename' => 'Foo.jpg' ],
					'text' => [ 'value' => 'Foo' ],
				],
			]
		];
	}

	private function createStoryPage( PageIdentity $articleTitle ): PageIdentity {
		$story = $this->makeFakeStoryData( $articleTitle );

		$storyTitle = Title::makeTitle(
			NS_STORY,
			'Test'
		);
		$content = new StoryContent( json_encode( $story ) );
		$this->editPage(
			$storyTitle,
			$content
		);

		return $storyTitle;
	}

	/**
	 * @param Title $articleTitle
	 * @param PageIdentity $storyTitle
	 *
	 * @return void
	 */
	private function installFakePageLinkSearch( Title $articleTitle, PageIdentity $storyTitle ) {
		$linkSearch = $this->createNoOpMock(
			PageLinksSearch::class,
			[ 'getPageLinks' ]
		);

		$linkSearch->expects( $this->once() )->method( 'getPageLinks' )->with(
				$articleTitle->getDBkey()
			)->willReturn( [ $storyTitle->getId() ] );

		$this->setService(
			'Wikistories.PageLinksSearch',
			$linkSearch
		);
	}

}
