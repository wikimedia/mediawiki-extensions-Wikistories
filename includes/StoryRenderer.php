<?php

namespace MediaWiki\Extension\Wikistories;

use Html;

class StoryRenderer {

	/**
	 * @var StoryContent
	 */
	private $story;

	/**
	 * StoryRenderer constructor.
	 *
	 * @param StoryContent $story
	 */
	public function __construct( $story ) {
		$this->story = $story;
	}

	/**
	 * @return array [ 'html', 'style' ]
	 */
	public function renderNoJS() {
		$html = Html::rawElement(
			'div',
			[ 'class' => 'ext-wikistories-viewer-nojs-root' ],
			implode( '', array_map( static function ( $frame ) {
				return Html::rawElement(
					'div', [
					'class' => 'ext-wikistories-viewer-frame',
					'style' => 'background-image:url(' . $frame->img . ');',
				],
					Html::element( 'p', [], $frame->text )
				);
			}, $this->story->getFrames() ) )
		);

		return [
			'html' => $html,
			'style' => 'mw.ext.story.viewer-nojs'
		];
	}

	/**
	 * @return array
	 */
	public function renderJs() {
		return [
			'html' => Html::element( 'div', [ 'class' => 'story-viewer-js-root' ] ),
			'style' => 'mw.ext.story.viewer',
			'script' => 'mw.ext.story.viewer',
			'configVars' => [ 'story' => $this->story->getData()->getValue() ]
		];
	}

}
