<?php

namespace MediaWiki\Extension\Wikistories;

use JsonSchema\Validator;
use MediaWiki\Config\ServiceOptions;
use MediaWiki\Page\PageLookup;
use RepoGroup;
use StatusValue;

class StoryValidator {

	public const CONSTRUCTOR_OPTIONS = [
		'WikistoriesMinFrames',
		'WikistoriesMaxFrames',
		'WikistoriesMaxTextLength',
	];

	/** @var ServiceOptions */
	private $options;

	/** @var RepoGroup */
	private $repoGroup;

	/** @var PageLookup */
	private $pageLookup;

	/**
	 * @param ServiceOptions $options
	 * @param RepoGroup $repoGroup
	 * @param PageLookup $pageLookup
	 */
	public function __construct(
		ServiceOptions $options,
		RepoGroup $repoGroup,
		PageLookup $pageLookup
	) {
		$options->assertRequiredOptions( self::CONSTRUCTOR_OPTIONS );
		$this->options = $options;
		$this->repoGroup = $repoGroup;
		$this->pageLookup = $pageLookup;
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

		// fromArticle exists
		$page = $this->pageLookup->getExistingPageByText( $story->getFromArticle() );
		if ( !$page ) {
			return StatusValue::newFatal( 'wikistories-from-article-not-found', $story->getFromArticle() );
		}

		// Validation based on config
		$frameCount = count( $story->getFrames() );
		if ( $frameCount < $this->options->get( 'WikistoriesMinFrames' ) ) {
			return StatusValue::newFatal( 'not-enough-frames' );
		}
		if ( $frameCount > $this->options->get( 'WikistoriesMaxFrames' ) ) {
			return StatusValue::newFatal( 'too-many-frames' );
		}
		$maxTextLength = $this->options->get( 'WikistoriesMaxTextLength' );
		foreach ( $story->getFrames() as $index => $frame ) {
			$textLength = mb_strlen( $frame->text->value );
			if ( $textLength > $maxTextLength ) {
				return StatusValue::newFatal(
					'wikistories-text-too-long',
					$index + 1,
					$textLength,
					$maxTextLength
				);
			}
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
