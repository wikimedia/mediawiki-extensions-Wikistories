<?php

namespace MediaWiki\Extension\Wikistories;

class StoryContentHandler extends \JsonContentHandler {

	public function __construct( $modelId = 'story' ) {
		parent::__construct( $modelId );
	}

	protected function getContentClass() {
		return StoryContent::class;
	}

	public function getActionOverrides() {
		return [
			'edit' => StoryEditAction::class,
			'submit' => StorySubmitAction::class,
		];
	}

	public function makeEmptyContent() {
		return new StoryContent( json_encode( [ 'frames' => [] ], JSON_PRETTY_PRINT ) );
	}
}
