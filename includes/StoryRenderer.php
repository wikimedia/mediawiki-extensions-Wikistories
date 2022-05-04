<?php

namespace MediaWiki\Extension\Wikistories;

use Exception;
use File;
use Html;
use RepoGroup;
use TitleValue;

class StoryRenderer {

	/** @var RepoGroup */
	private $repoGroup;

	/**
	 * @param RepoGroup $repoGroup
	 */
	public function __construct( RepoGroup $repoGroup ) {
		$this->repoGroup = $repoGroup;
	}

	/**
	 * @param StoryContent $story
	 * @return array [ 'html', 'style' ]
	 */
	public function renderNoJS( StoryContent $story ): array {
		$story = $this->getStoryForViewer( $story, 0, '' );
		$html = Html::rawElement(
			'div',
			[ 'class' => 'ext-wikistories-viewer-nojs-root' ],
			implode( '', array_map( static function ( $frame ) {
				return Html::rawElement(
					'div',
					[
						'class' => 'ext-wikistories-viewer-frame',
						'style' => 'background-image:url(' . $frame[ 'img' ] . ');',
					],
					Html::element( 'p', [], $frame[ 'text' ] )
				);
			}, $story[ 'frames' ] ) )
		);

		return [
			'html' => $html,
			'style' => 'mw.ext.story.viewer-nojs'
		];
	}

	/**
	 * @param StoryContent $story
	 * @param int $pageId Page Id of this story
	 * @param string $pageTitle Title of this story
	 * @return array Data structure expected by the discovery module and the story viewer
	 */
	public function getStoryForViewer( StoryContent $story, int $pageId, string $pageTitle ): array {
		$frames = $story->getFrames();
		$filesUsed = array_map( static function ( $frame ) {
			return $frame->image->filename;
		}, $frames );
		$files = $this->repoGroup->findFiles( $filesUsed );
		$firstFrame = reset( $frames );
		$thumb = $firstFrame ? $this->getUrl( $files, $firstFrame->image->filename, 52 ) : '';
		return [
			'pageId' => $pageId,
			'title' => $pageTitle,
			'thumbnail' => $thumb,
			'frames' => array_map( function ( $frame ) use ( $files ) {
				return [
					'img' => $this->getUrl( $files, $frame->image->filename, 640 ),
					'text' => $frame->text->value,
				];
			}, $frames )
		];
	}

	/**
	 * @param array $files
	 * @param string $filename
	 * @param int $size
	 * @return string
	 * @throws Exception
	 */
	private function getUrl( array $files, string $filename, int $size ): string {
		$title = new TitleValue( NS_FILE, $filename );
		/** @var File $file */
		$file = $files[ $title->getDBkey() ];
		if ( !$file ) {
			throw new Exception( "Image not found: $filename" );
		}
		return $file->createThumb( $size );
	}
}
