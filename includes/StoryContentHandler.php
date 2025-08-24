<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Category\TrackingCategories;
use MediaWiki\Content\Content;
use MediaWiki\Content\JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\Transform\PreloadTransformParams;
use MediaWiki\Content\Transform\PreSaveTransformParams;
use MediaWiki\Content\ValidationParams;
use MediaWiki\Context\IContextSource;
use MediaWiki\JobQueue\JobQueueGroup;
use MediaWiki\JobQueue\Jobs\RefreshLinksJob;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Title\Title;
use MediaWiki\Title\TitleFactory;
use MediaWiki\Title\TitleValue;

class StoryContentHandler extends JsonContentHandler {

	public function __construct(
		string $modelId,
		private readonly StoryConverter $storyConverter,
		private readonly StoryValidator $storyValidator,
		private readonly StoryRenderer $storyRenderer,
		private readonly StoryTrackingCategories $storyTrackingCategories,
		private readonly TrackingCategories $trackingCategories,
		private readonly JobQueueGroup $jobQueueGroup,
		private readonly TitleFactory $titleFactory,
	) {
		parent::__construct( $modelId );
	}

	protected function getContentClass(): string {
		return StoryContent::class;
	}

	public function getActionOverrides(): array {
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
	 */
	public function fillParserOutput(
		Content $content,
		ContentParseParams $cpoParams,
		ParserOutput &$parserOutput
	): void {
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
		foreach ( $story->getCategories() as $categoryName ) {
			$categoryTitle = $this->titleFactory->makeTitleSafe( NS_CATEGORY, $categoryName );
			if ( $categoryTitle ) {
				$parserOutput->addCategory( $categoryTitle, $storyPage->getDBkey() );
			}
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
			$parserOutput->setContentHolderText( $parts['html'] );
		}
	}

	/**
	 * @inheritDoc
	 */
	public function isParserCacheSupported(): bool {
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
