<?php

namespace MediaWiki\Extension\Wikistories;

use DeferredUpdates;
use MediaWiki\MediaWikiServices;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\User\UserIdentity;
use OutputPage;
use SpecialPage;
use WikiPage;

class Hooks {

	/**
	 * @param OutputPage $out
	 */
	public static function onBeforePageDisplayMobile( OutputPage $out ) {
		$title = $out->getTitle();
		$storiesFlag = $out->getRequest()->getBool( 'wikistories' );
		if ( $storiesFlag && $title->getNamespace() === NS_MAIN && $title->exists() ) {
			$out->addJsConfigVars(
				'wgWikistoriesCreateUrl',
				SpecialPage::getTitleFor( 'StoryBuilder', $out->getTitle() )->getLinkURL()
			);
			$out->addModules( [ 'mw.ext.story.discover' ] );
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
