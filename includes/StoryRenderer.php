<?php

namespace MediaWiki\Extension\Wikistories;

use Html;
use TitleValue;

class StoryRenderer {

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
		return [
			'pageId' => $pageId,
			'title' => $pageTitle,
			'thumbnail' => $this->getThumbUrl( $story ),
			'frames' => array_map( function ( $frame ) {
				return [
					'img' => $this->getFileUrl( $frame->image->filename, $frame->image->repo, 640 ),
					'text' => $frame->text->value,
				];
			}, $story->getFrames() )
		];
	}

	/**
	 * @param StoryContent $story
	 * @return string Thumb url of the first frame of this story
	 */
	private function getThumbUrl( StoryContent $story ): string {
		if ( count( $story->getFrames() ) === 0 ) {
			return '';
		}
		$frame = $story->getFrames()[0];
		return $this->getFileUrl( $frame->image->filename, $frame->image->repo, 52 );
	}

	/**
	 * @param string $file
	 * @param string $repo
	 * @param int $size
	 * @return string Thumb url for given file, repo and size
	 */
	private function getFileUrl( string $file, string $repo, int $size ): string {
		$title = new TitleValue( NS_FILE, $file );
		$dbKey = $title->getDBkey();
		$md5 = md5( $dbKey );
		$a = substr( $md5, 0, 1 );
		$b = substr( $md5, 0, 2 );
		$encodedKey = urlencode( $dbKey );
		return "https://upload.wikimedia.org/wikipedia/$repo/thumb/$a/$b/$encodedKey/${size}px-$encodedKey";
	}
}
