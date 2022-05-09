<?php

namespace MediaWiki\Extension\Wikistories;

use JsonSchema\Validator;
use MediaWiki\Config\ServiceOptions;
use RepoGroup;
use StatusValue;

class StoryValidator {

	public const CONSTRUCTOR_OPTIONS = [
		'WikistoriesMinFrames',
		'WikistoriesMaxFrames',
	];

	/** @var ServiceOptions */
	private $options;

	/** @var RepoGroup */
	private $repoGroup;

	/**
	 * @param ServiceOptions $options
	 * @param RepoGroup $repoGroup
	 */
	public function __construct( ServiceOptions $options, RepoGroup $repoGroup ) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->repoGroup = $repoGroup;
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

		// Files exist
		$filesUsed = array_map( static function ( $frame ) {
			return strtr( $frame->image->filename, ' ', '_' );
		}, $story->getFrames() );
		$files = $this->repoGroup->findFiles( $filesUsed );

		foreach ( $filesUsed as $name ) {
			if ( !isset( $files[ $name ] ) ) {
				return StatusValue::newFatal( 'wikistories-file-not-found', $name );
			}
		}

		return StatusValue::newGood();
	}
}
