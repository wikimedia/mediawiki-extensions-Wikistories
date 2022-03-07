<?php

namespace MediaWiki\Extension\Wikistories;

use JsonSchema\Validator;
use MediaWiki\Config\ServiceOptions;
use StatusValue;

class StoryValidator {

	public const CONSTRUCTOR_OPTIONS = [
		'WikistoriesMinFrames',
		'WikistoriesMaxFrames',
	];

	/** @var ServiceOptions */
	private $options;

	/**
	 * @param ServiceOptions $options
	 */
	public function __construct( ServiceOptions $options ) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
	}

	/**
	 * @param StoryContent $story
	 * @return StatusValue
	 */
	public function isValid( StoryContent $story ): StatusValue {
		// Special case: empty content needs to be valid
		// todo: make sure we can't save empty content stories with API
		if ( $story->getText() === '{}' ) {
			return StatusValue::newGood();
		}

		// Validation based on json schema
		$schemaPath = __DIR__ . "/../story.schema.v1.json";
		$validator = new Validator();
		$validator->check( $story->getData()->getValue(), (object)[ '$ref' => 'file://' . $schemaPath ] );
		if ( !$validator->isValid() ) {
			// todo: find a way to include error messages from the schema validator
			return StatusValue::newFatal( 'invalid-format' );
		}

		// Validation based on config
		$frameCount = count( $story->getFrames() );
		if ( $frameCount < $this->options->get( 'WikistoriesMinFrames' ) ) {
			return StatusValue::newFatal( 'not-enough-frames' );
		}
		if ( $frameCount > $this->options->get( 'WikistoriesMaxFrames' ) ) {
			return StatusValue::newFatal( 'too-many-frames' );
		}

		return StatusValue::newGood();
	}
}
