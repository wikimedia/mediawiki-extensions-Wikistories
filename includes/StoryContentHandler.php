<?php

namespace MediaWiki\Extension\Wikistories;

use JobQueueGroup;
use MediaWiki\Category\TrackingCategories;
use MediaWiki\Content\Content;
use MediaWiki\Content\JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\Transform\PreloadTransformParams;
use MediaWiki\Content\Transform\PreSaveTransformParams;
use MediaWiki\Content\ValidationParams;
use MediaWiki\Context\IContextSource;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleValue;
use RefreshLinksJob;

class StoryContentHandler extends JsonContentHandler {

	/** @var StoryConverter */
	private $storyConverter;

	/** @var StoryValidator */
	private $storyValidator;

	/** @var StoryRenderer */
	private $storyRenderer;

	/** @var StoryTrackingCategories */
	private $storyTrackingCategories;

	/** @var TrackingCategories */
	private $trackingCategories;

	/** @var JobQueueGroup */
	private $jobQueueGroup;

	/**
	 * @param string $modelId
	 * @param StoryConverter $storyConverter
	 * @param StoryValidator $storyValidator
	 * @param StoryRenderer $storyRenderer
	 * @param StoryTrackingCategories $storyTrackingCategories
	 * @param TrackingCategories $trackingCategories
	 * @param JobQueueGroup $jobQueueGroup
	 */
	public function __construct(
		$modelId,
		StoryConverter $storyConverter,
		StoryValidator $storyValidator,
		StoryRenderer $storyRenderer,
		StoryTrackingCategories $storyTrackingCategories,
		TrackingCategories $trackingCategories,
		JobQueueGroup $jobQueueGroup
	) {
		parent::__construct( $modelId );
		$this->storyConverter = $storyConverter;
		$this->storyValidator = $storyValidator;
		$this->storyRenderer = $storyRenderer;
		$this->storyTrackingCategories = $storyTrackingCategories;
		$this->trackingCategories = $trackingCategories;
		$this->jobQueueGroup = $jobQueueGroup;
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
			'storyview' => [
				'class' => StoryViewAction::class,
				'services' => [
					'Wikistories.Cache',
					'UrlUtils',
				]
			],
		];
	}

	/**
	 * Outputs the plain html version of a story.
	 *
	 * @param Content $content
	 * @param ContentParseParams $cpoParams
	 * @param ParserOutput &$parserOutput
	 */
	public function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$parserOutput
	) {
		'@phan-var StoryContent $content';
		/** @var StoryContent $story */
		$story = $this->storyConverter->toLatest( $content );
		$storyPage = $cpoParams->getPage();
		$storyTitle = Title::newFromPageReference( $storyPage );
		$storyData = $this->storyRenderer->getStoryData( $story, $storyTitle );

		// Links
		$parserOutput->addLink( new TitleValue( NS_MAIN, $storyData[ 'articleTitle' ] ) );
		foreach ( $storyData[ 'frames' ] as $frame ) {
			$parserOutput->addImage( strtr( $frame[ 'filename' ], ' ', '_' ) );
		}

		// Categories
		foreach ( $story->getCategories() as $category ) {
			$parserOutput->addCategory( $category, $storyPage->getDBkey() );
		}

		// Tracking categories
		foreach ( $storyData[ 'trackingCategories' ] as $trackingCategory ) {
			$this->trackingCategories->addTrackingCategory(
				$parserOutput, $trackingCategory, $storyPage
			);
		}

		// refresh links job when there are changes of tracking categories
		if ( $this->storyTrackingCategories->hasDiff( $storyData[ 'trackingCategories' ], $storyTitle ) ) {
			$this->jobQueueGroup->push(
				RefreshLinksJob::newPrioritized( $storyTitle, [] )
			);
		}

		// HTML version
		if ( $cpoParams->getGenerateHtml() ) {
			$parts = $this->storyRenderer->renderNoJS( $storyData );
			$parserOutput->addModuleStyles( [ $parts['style'] ] );
			$parserOutput->setText( $parts['html'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isParserCacheSupported() {
		return true;
	}

	/**
	 * @param IContextSource $context
	 * @param array $options
	 * @return StorySlotDiffRenderer
	 */
	public function getSlotDiffRendererWithOptions( IContextSource $context, $options = [] ) {
		return new StorySlotDiffRenderer(
			$this->storyConverter,
			$this->createTextSlotDiffRenderer( $options )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function preloadTransform( Content $content, PreloadTransformParams $pltParams ): Content {
		'@phan-var StoryContent $content';
		/** @var StoryContent $story */
		$story = $content;
		return $this->storyConverter->toLatest( $story );
	}

	/**
	 * @inheritDoc
	 */
	public function preSaveTransform( Content $content, PreSaveTransformParams $pstParams ): Content {
		'@phan-var StoryContent $content';
		/** @var StoryContent $story */
		$story = $content;
		return $this->storyConverter->withSchemaVersion( $story );
	}

	/**
	 * @inheritDoc
	 */
	public function validateSave( Content $content, ValidationParams $validationParams ) {
		'@phan-var StoryContent $content';
		$status = parent::validateSave( $content, $validationParams );
		if ( !$status->isGood() ) {
			return $status;
		}
		/** @var StoryContent $story */
		$story = $content;
		return $this->storyValidator->isValid( $story );
	}

}
