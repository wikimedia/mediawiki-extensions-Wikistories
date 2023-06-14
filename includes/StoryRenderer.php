<?php

namespace MediaWiki\Extension\Wikistories;

use File;
use FormatMetadata;
use Html;
use MediaWiki\Page\PageLookup;
use MediaWiki\Page\RedirectLookup;
use MediaWiki\Title\Title;
use RepoGroup;
use RequestContext;
use SpecialPage;

class StoryRenderer {

	private const TC_NO_IMAGE = 'wikistories-no-image-category';

	private const TC_NO_ARTICLE = 'wikistories-no-related-article';

	private const TC_OUTDATED_TEXT = 'wikistories-outdated-text-category';

	/** @var RepoGroup */
	private $repoGroup;

	/** @var RedirectLookup */
	private $redirectLookup;

	/** @var PageLookup */
	private $pageLookup;

	/** @var StoryContentAnalyzer */
	private $analyzer;

	/**
	 * @param RepoGroup $repoGroup
	 * @param RedirectLookup $redirectLookup
	 * @param PageLookup $pageLookup
	 * @param StoryContentAnalyzer $analyzer
	 */
	public function __construct(
		RepoGroup $repoGroup,
		RedirectLookup $redirectLookup,
		PageLookup $pageLookup,
		StoryContentAnalyzer $analyzer
	) {
		$this->repoGroup = $repoGroup;
		$this->redirectLookup = $redirectLookup;
		$this->pageLookup = $pageLookup;
		$this->analyzer = $analyzer;
	}

	/**
	 * @param array $storyData
	 * @return array [ 'html', 'style' ]
	 */
	public function renderNoJS( array $storyData ): array {
		$articleTitle = Title::makeTitle(
			NS_MAIN,
			$storyData[ 'articleTitle' ],
			'/story/' . $storyData[ 'articleId' ]
		);
		$missingImages = array_filter( $storyData[ 'frames' ], static function ( $frame ) {
			return $frame[ 'fileNotFound' ];
		} );

		$html = Html::element(
			'a',
			[ 'href' => $articleTitle->getLinkURL() ],
			$articleTitle->getText()
		);

		if ( in_array( self::TC_NO_ARTICLE, $storyData[ 'trackingCategories' ] ) ) {
			$context = RequestContext::getMain();
			$html .= Html::errorBox(
				$context->msg( 'wikistories-nojs-viewer-no-article' )->text()
			);
		}

		if ( count( $missingImages ) > 0 ) {
			$context = RequestContext::getMain();
			$html .= Html::warningBox(
				$context->msg( 'wikistories-nojs-viewer-error' )->params( count( $missingImages ) )->text()
			);
		}

		$html .= Html::rawElement(
			'div',
			[ 'class' => 'ext-wikistories-viewer-nojs' ],
			implode( '', array_map( function ( $frame ) {
				return Html::rawElement(
					'div',
					[
						'class' => 'ext-wikistories-viewer-nojs-frame',
						'style' => empty( $frame[ 'url' ] ) ?
							'background-color: #000'
							:
							'background-image:url(' . $frame[ 'url' ] . ');',
					],
					$this->getNoJsFrameHtmlString( $frame )
				);
			}, $storyData[ 'frames' ] ) )
		);

		return [
			'html' => $html,
			'style' => 'ext.wikistories.viewer-nojs'
		];
	}

	/**
	 * @param StoryContent $story
	 * @param Title $storyTitle
	 * @return array
	 */
	public function getStoryData(
		StoryContent $story,
		Title $storyTitle
	): array {
		$frames = $story->getFrames();
		$filesUsed = array_map( static function ( $frame ) {
			return $frame->image->filename;
		}, $frames );
		$files = $this->repoGroup->findFiles( $filesUsed );
		$firstFrame = reset( $frames );
		$thumb = $firstFrame ? $this->getUrl( $files, $firstFrame->image->filename, 52 ) : '';
		$article = $story->getArticleTitle( $this->pageLookup, $this->redirectLookup );
		$trackingCategories = [];

		if ( empty( $article ) ) {
			$trackingCategories[] = self::TC_NO_ARTICLE;
		}

		$data = [
			'articleId' => $article ? $article->getId() : 0,
			'articleTitle' => $article ? $article->getDBkey() : '',
			'storyId' => $storyTitle->getId(),
			'storyTitle' => $storyTitle->getText(),
			'editUrl' => SpecialPage::getTitleFor( 'StoryBuilder', $storyTitle->getPrefixedDBkey() )->getLinkURL(),
			'talkUrl' => $storyTitle->getTalkPageIfDefined()->getLinkURL(),
			'thumbnail' => $thumb,
			'frames' => array_map( function ( $frame ) use ( $files, $article, &$trackingCategories ) {
				$url = $this->getUrl( $files, $frame->image->filename, 640 );
				if ( empty( $url ) ) {
					$trackingCategories[] = self::TC_NO_IMAGE;
				}
				$outdatedText = $article && $this->analyzer->isOutdatedText(
					$article,
					$frame->text->value,
					$frame->text->fromArticle->originalText ?? ''
				);
				if ( $outdatedText ) {
					$trackingCategories[] = self::TC_OUTDATED_TEXT;
				}
				return [
					'url' => $url,
					'filename' => $frame->image->filename,
					'fileNotFound' => empty( $url ),
					'text' => $frame->text->value,
					'textFromArticle' => $frame->text->fromArticle->originalText ?? '',
					'outdatedText' => $outdatedText,
					'attribution' => $this->getAttribution( $files, $frame->image->filename ),
				];
			}, $frames )
		];
		$data[ 'trackingCategories' ] = array_unique( $trackingCategories );
		return $data;
	}

	/**
	 * @param array $files
	 * @param string $filename
	 * @param int $size
	 * @return string
	 */
	private function getUrl( array $files, string $filename, int $size ): string {
		/** @var File $file */
		$file = $files[ strtr( $filename, ' ', '_' ) ] ?? false;
		if ( !$file ) {
			return '';
		}
		return $file->createThumb( $size );
	}

	/**
	 * @param array $files
	 * @param string $filename
	 * @return array Data structure with attribution information such author or license
	 */
	private function getAttribution( array $files, string $filename ): array {
		/** @var File $file */
		$file = $files[ strtr( $filename, ' ', '_' ) ] ?? false;
		if ( !$file ) {
			return [
				'author' => '',
				'license' => [],
				'url' => '',
			];
		}

		$formatMetadata = new FormatMetadata();
		$metadata = $formatMetadata->fetchExtendedMetadata( $file );
		$rawAuthor = $metadata[ 'Artist' ][ 'value' ] ?? '';
		$author = is_array( $rawAuthor ) ? reset( $rawAuthor ) : $rawAuthor;
		$licenseString = $metadata[ 'LicenseShortName' ][ 'value' ] ?? '';
		$licenseTypes = [ 'CC', 'BY', 'SA', 'Fair', 'Public' ];
		$license = array_filter( $licenseTypes, static function ( $license ) use ( $licenseString ) {
			return strpos( $licenseString, $license ) !== false;
		} );

		return [
			'author' => strip_tags( $author ),
			'license' => $license,
			'url' => $file->getDescriptionUrl(),
		];
	}

	/**
	 * @param array $frame
	 * @return string html of the given frame data
	 */
	private function getNoJsFrameHtmlString( array $frame ): string {
		$html = Html::element(
			'div',
			[ 'class' => 'ext-wikistories-viewer-nojs-frame-text' ],
			$frame[ 'text' ]
		);

		if ( $frame[ 'attribution' ][ 'license'] !== [] ) {
			$html .= Html::rawElement(
				'div',
				[ 'class' => 'ext-wikistories-viewer-nojs-frame-attribution' ],
				$this->getAttributionHtmlString( $frame[ 'attribution' ] )
			);
		}
		return $html;
	}

	/**
	 * @param array $attribution
	 * @return string html of the given attribution data
	 */
	private function getAttributionHtmlString( array $attribution ): string {
		$attributionString = Html::rawElement(
			'div',
			[ 'class' => 'ext-wikistories-viewer-nojs-frame-attribution-info' ],
			implode( '', [
				'license' => $this->getLicensesHtmlString( $attribution[ 'license' ] ),
				'author' => Html::rawElement(
					'div',
					[
						'class' => 'ext-wikistories-viewer-nojs-frame-attribution-info-author',
						'title' => $attribution[ 'author' ],
					],
					$attribution[ 'author' ]
				)
			] )
		);

		$attributionString .= Html::rawElement(
			'div',
			[ 'class' => 'ext-wikistories-viewer-nojs-frame-attribution-more-info' ],
			Html::element(
				'a',
				[ 'href' => $attribution[ 'url' ],
					'target' => '_blank',
					'class' => 'ext-wikistories-viewer-nojs-frame-attribution-more-info-link'
				],
				''
			)
		);

		return $attributionString;
	}

	/**
	 * @param array $license
	 * @return string html of the given license data
	 */
	private function getLicensesHtmlString( $license ): string {
		return implode( '', array_map( static function ( $licenseType ) {
			return Html::rawElement(
				'div',
				[
					'class' => 'ext-wikistories-viewer-nojs-frame-attribution-info-license' .
					' ext-wikistories-viewer-nojs-frame-attribution-info-' . strtolower( $licenseType )
				],
				''
			);
		}, $license ) );
	}
}
