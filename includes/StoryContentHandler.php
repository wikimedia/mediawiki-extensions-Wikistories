<?php

namespace MediaWiki\Extension\Wikistories;

class StoryContentHandler extends \JsonContentHandler {

	/**
	 * @param string $modelId
	 */
	public function __construct( $modelId = 'story' ) {
		parent::__construct( $modelId );
	}

	/**
	 * @return string
	 */
	protected function getContentClass() {
		return StoryContent::class;
	}

	/**
	 * @return array
	 */
	public function getActionOverrides() {
		return [
			'edit' => StoryEditAction::class,
			'submit' => StorySubmitAction::class,
		];
	}

	/**
	 * @return StoryContent
	 */
	public function makeEmptyContent() {
		return new StoryContent( json_encode( [ 'frames' => [] ], JSON_PRETTY_PRINT ) );
	}
}
