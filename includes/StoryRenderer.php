<?php

namespace MediaWiki\Extension\Wikistories;

use File;
use FormatMetadata;
use Html;
use MediaWiki\Linker\LinkTarget;
use RepoGroup;
use RequestContext;
use SpecialPage;
use Title;
use TitleFormatter;
use TitleValue;

class StoryRenderer {

	/** @var RepoGroup */
	private $repoGroup;

	/** @var TitleFormatter */
	private $titleFormatter;

	/**
	 * @param RepoGroup $repoGroup
	 * @param TitleFormatter $titleFormatter
	 */
	public function __construct( RepoGroup $repoGroup, TitleFormatter $titleFormatter ) {
		$this->repoGroup = $repoGroup;
		$this->titleFormatter = $titleFormatter;
	}

	/**
	 * @param StoryContent $story
	 * @param int $pageId
	 * @return array [ 'html', 'style' ]
	 */
	public function renderNoJS( StoryContent $story, int $pageId ): array {
		$storyView = $this->getStoryForViewer( $story, 0, new TitleValue( NS_STORY, 'Unused' ) );
		$articleTitle = Title::makeTitle( NS_MAIN, $story->getFromArticle(), '/story/' . $pageId );
		$missingImages = array_filter( $storyView[ 'frames' ], static function ( $frame ) {
			return empty( $frame['url'] );
		} );

		$html = Html::element(
			'a',
			[ 'href' => $articleTitle->getLinkURL() ],
			$articleTitle->getText()
		);

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
			}, $storyView[ 'frames' ] ) )
		);

		return [
			'html' => $html,
			'style' => 'ext.wikistories.viewer-nojs'
		];
	}

	/**
	 * @param StoryContent $story
	 * @param int $pageId Page Id of this story
	 * @param LinkTarget $title Title of this story
	 * @return array Data structure expected by the discovery module and the story viewer
	 */
	public function getStoryForViewer( StoryContent $story, int $pageId, LinkTarget $title ): array {
		$frames = $story->getFrames();
		$filesUsed = array_map( static function ( $frame ) {
			return $frame->image->filename;
		}, $frames );
		$files = $this->repoGroup->findFiles( $filesUsed );
		$firstFrame = reset( $frames );
		$thumb = $firstFrame ? $this->getUrl( $files, $firstFrame->image->filename, 52 ) : '';
		$storyFullTitle = $this->titleFormatter->getPrefixedDBkey( $title );
		return [
			'pageId' => $pageId,
			'title' => $title->getText(),
			'editUrl' => SpecialPage::getTitleFor( 'StoryBuilder', $storyFullTitle )->getLinkURL(),
			'thumbnail' => $thumb,
			'frames' => array_map( function ( $frame ) use ( $files ) {
				$url = $this->getUrl( $files, $frame->image->filename, 640 );
				return [
					'url' => $url,
					'fileNotFound' => empty( $url ),
					'text' => $frame->text->value,
					'attribution' => $this->getAttribution( $files, $frame->image->filename ),
				];
			}, $frames )
		];
	}

	/**
	 * @param StoryContent $story
	 * @param string $pageTitle
	 * @return array
	 */
	public function getStoryForBuilder( StoryContent $story, string $pageTitle ): array {
		$frames = $story->getFrames();
		$filesUsed = array_map( static function ( $frame ) {
			return $frame->image->filename;
		}, $frames );
		$files = $this->repoGroup->findFiles( $filesUsed );
		return [
			'title' => $pageTitle,
			'fromArticle' => $story->getFromArticle(),
			'frames' => array_map( function ( $frame ) use ( $files ) {
				$url = $this->getUrl( $files, $frame->image->filename, 640 );
				return [
					'url' => $url,
					'fileNotFound' => empty( $url ),
					'filename' => $frame->image->filename,
					'text' => $frame->text->value,
					'textFromArticle' => $frame->text->fromArticle->originalText ?? '',
					'attribution' => $this->getAttribution( $files, $frame->image->filename ),
				];
			}, $frames )
		];
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
		$author = strip_tags( $metadata[ 'Artist' ][ 'value' ] ?? '' );
		$licenseString = $metadata[ 'LicenseShortName' ][ 'value' ] ?? '';
		$licenseTypes = [ 'CC', 'BY', 'SA', 'Fair', 'Public' ];
		$license = array_filter( $licenseTypes, static function ( $license ) use ( $licenseString ) {
			return strpos( $licenseString, $license ) !== false;
		} );

		return [
			'author' => $author,
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
					[ 'class' => 'ext-wikistories-viewer-nojs-frame-attribution-info-author' ],
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
