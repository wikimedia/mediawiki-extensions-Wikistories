<?php

namespace MediaWiki\Extension\Wikistories\Notifications;

use EchoEventPresentationModel;
use MediaWiki\Extension\Wikistories\Hooks\EchoNotificationsHandlers;
use MediaWiki\MediaWikiServices;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class ArticleChangedPresentationModel extends EchoEventPresentationModel {

	/**
	 * @return string The symbolic icon name as defined in $wgEchoNotificationIcons
	 */
	public function getIconType() {
		return EchoNotificationsHandlers::NOTIFICATION_TYPE;
	}

	/**
	 * @inheritDoc
	 */
	public function getHeaderMessage() {
		$title = $this->getTruncatedTitleText( $this->getArticleTitle() );
		$msg = $this->msg( 'wikistories-notification-articlechanged-header' );
		$msg->params( $title );
		$msg->params( $this->getTruncatedTitleText( $this->event->getTitle(), true ) );
		return $msg;
	}

	/**
	 * Array of primary link details, with possibly-relative URL & label.
	 *
	 * @return array|false Array of link data, or false for no link:
	 *                    ['url' => (string) url, 'label' => (string) link text (non-escaped)]
	 */
	public function getPrimaryLink() {
		return $this->getStoryBuilderEditLink();
	}

	/**
	 * @inheritDoc
	 */
	public function getSecondaryLinks() {
		$agentLink = $this->getAgentLink();
		$agentLink[ 'prioritized' ] = false;
		return [
			$this->getArticleDiffLink(),
			$this->getPageLink( $this->event->getTitle(), '', false ),
			$this->getPageLink( $this->getArticleTitle(), '', false ),
			$agentLink,
		];
	}

	/**
	 * @return Title
	 */
	private function getArticleTitle(): Title {
		return Title::newFromText( $this->event->getExtraParam( 'articleTitle' ) );
	}

	/**
	 * @return array
	 */
	private function getArticleDiffLink(): array {
		$title = $this->getArticleTitle();
		$articleTopRev = $this->event->getExtraParam( 'articleRevId' );
		if ( $this->isBundled() ) {
			$bundledEvents = $this->getBundledEvents();
			$oldestEvent = end( $bundledEvents );
			$revisionLookup = MediaWikiServices::getInstance()->getRevisionLookup();
			$rev = $revisionLookup->getRevisionById( $oldestEvent->getExtraParam( 'articleRevId' ) );
			$previousRev = $revisionLookup->getPreviousRevision( $rev );
			$params = [
				'diff' => $articleTopRev,
				'oldid' => $previousRev->getId(),
			];
		} else {
			$params = [
				'diff' => 'prev',
				'oldid' => $articleTopRev,
			];
		}
		return [
			'url' => $title->getFullURL( $params ),
			'label' => $this->msg( 'diff' ),
			'description' => '',
			'prioritized' => true,
		];
	}

	/**
	 * @return array
	 */
	private function getStoryBuilderEditLink(): array {
		$url = SpecialPage::getTitleFor( 'StoryBuilder', $this->event->getTitle() )->getFullURL();
		return [
			'url' => $url,
			'label' => '',
			'description' => '',
			'prioritized' => true,
		];
	}
}
