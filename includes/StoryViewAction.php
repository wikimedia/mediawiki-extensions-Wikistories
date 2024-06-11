<?php

namespace MediaWiki\Extension\Wikistories;

use Article;
use FormlessAction;
use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Utils\UrlUtils;

class StoryViewAction extends FormlessAction {

	/** @var StoriesCache */
	private $storiesCache;

	/** @var UrlUtils */
	private $urlUtils;

	/**
	 * @param Article $article
	 * @param IContextSource $context
	 * @param StoriesCache $storiesCache
	 * @param UrlUtils $urlUtils
	 */
	public function __construct(
		Article $article,
		IContextSource $context,
		StoriesCache $storiesCache,
		UrlUtils $urlUtils
	) {
		parent::__construct( $article, $context );
		$this->storiesCache = $storiesCache;
		$this->urlUtils = $urlUtils;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'storyview';
	}

	/**
	 * @inheritDoc
	 */
	public function onView() {
		$out = $this->getOutput();
		$out->setArticleBodyOnly( true );
		$out->setPageTitle( $this->getTitle()->getText() );
		$out->addMeta( 'viewport', 'width=device-width, initial-scale=1' );
		$bodyHtml = '';

		$storyExists = $this->getWikiPage()->getId() > 0;
		if ( $storyExists ) {
			$storyData = $this->storiesCache->getStory( $this->getArticle()->getPage()->getId() );
			$this->addLinkPreviewTags( $storyData );
			$out->addJsConfigVars( [ 'wgWikistoriesStoryContent' => $storyData ] );
			$out->addModules( [ 'ext.wikistories.viewaction' ] );
		} else {
			$out->addModuleStyles( [ 'ext.wikistories.viewaction.styles' ] );
			$bodyHtml = Html::rawElement(
				'div',
				[ 'class' => 'ext-wikistories-storyviewaction-notfound' ],
				Html::element(
					'div',
					[ 'class' => 'ext-wikistories-storyviewaction-notfound-icon' ]
				) .
				Html::element(
					'div',
					[ 'class' => 'ext-wikistories-storyviewaction-notfound-message' ],
					$this->msg( 'ext-wikistories-storyviewaction-notfound-message' )->text()
				)
			);
		}

		return $out->headElement( $out->getSkin() ) .
			$bodyHtml .
			$out->getBottomScripts() .
			'</body></html>';
	}

	/**
	 * @param array $storyData
	 */
	private function addLinkPreviewTags( array $storyData ) {
		$out = $this->getOutput();
		// Open graph: https://ogp.me/
		$out->addMeta( 'og:title', $storyData[ 'storyTitle' ] );
		$out->addMeta( 'og:url', $storyData[ 'shareUrl' ] );
		$out->addMeta( 'og:description', $storyData[ 'frames' ][ 0 ][ 'text' ] );
		$out->addMeta( 'og:image', $storyData[ 'frames' ][ 0 ][ 'url' ] );

		// Twitter: https://developer.twitter.com/en/docs/twitter-for-websites/cards/overview/markup
		$out->addMeta( 'twitter:card', 'summary_large_image' );
		$out->addMeta( 'twitter:domain', $this->urlUtils->getServer( PROTO_HTTPS ) ?? '' );
		$out->addMeta( 'twitter:url', $storyData[ 'shareUrl' ] );
		$out->addMeta( 'twitter:title', $storyData[ 'storyTitle' ] );
		$out->addMeta( 'twitter:description', $storyData[ 'frames' ][ 0 ][ 'text' ] );
		$out->addMeta( 'twitter:image', $storyData[ 'frames' ][ 0 ][ 'url' ] );
	}

}
