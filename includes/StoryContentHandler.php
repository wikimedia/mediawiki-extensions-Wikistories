<?php

namespace MediaWiki\Extension\Wikistories;

use Content;
use IContextSource;
use JsonContentHandler;
use MediaWiki\Content\Renderer\ContentParseParams;
use MediaWiki\Content\Transform\PreloadTransformParams;
use MediaWiki\Content\Transform\PreSaveTransformParams;
use MediaWiki\Content\ValidationParams;
use ParserOutput;
use TitleValue;

class StoryContentHandler extends JsonContentHandler {

	/** @var StoryConverter */
	private $storyConverter;

	/** @var StoryValidator */
	private $storyValidator;

	/** @var StoriesCache */
	private $storiesCache;

	/** @var StoryRenderer */
	private $storyRenderer;

	/**
	 * @param string $modelId
	 * @param StoryConverter $storyConverter
	 * @param StoryValidator $storyValidator
	 * @param StoriesCache $storiesCache
	 * @param StoryRenderer $storyRenderer
	 */
	public function __construct(
		$modelId,
		StoryConverter $storyConverter,
		StoryValidator $storyValidator,
		StoriesCache $storiesCache,
		StoryRenderer $storyRenderer
	) {
		parent::__construct( $modelId );
		$this->storyConverter = $storyConverter;
		$this->storyValidator = $storyValidator;
		$this->storiesCache = $storiesCache;
		$this->storyRenderer = $storyRenderer;
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
	 * Outputs the plain html version of a story.
	 *
	 * @param Content $content
	 * @param ContentParseParams $cpoParams
	 * @param ParserOutput &$output
	 */
	public function fillParserOutput( Content $content, ContentParseParams $cpoParams, ParserOutput &$output ) {
		'@phan-var StoryContent $content';

		if ( !$cpoParams->getGenerateHtml() ) {
			return;
		}

		$html = '';
		/** @var StoryContent $story */
		$story = $this->storyConverter->toLatest( $content );

		// register links from story frames to source articles
		$relatedArticles = [ $story->getFromArticle() ];
		foreach ( $story->getFrames() as $frame ) {
			if ( isset( $frame->text->fromArticle->articleTitle ) ) {
				$relatedArticles[] = $frame->text->fromArticle->articleTitle;
			}
		}
		foreach ( array_unique( $relatedArticles ) as $relatedArticle ) {
			$output->addLink( new TitleValue( NS_MAIN, $relatedArticle ) );
			// todo: only invalidate the cache if the links have changed
			$this->storiesCache->invalidateForArticle( $relatedArticle );
		}

		// no-js
		$parts = $this->storyRenderer->renderNoJS( $story );
		$output->addModuleStyles( [ $parts['style'] ] );
		$html .= $parts['html'];

		$output->setText( $html );
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
		return new StorySlotDiffRenderer();
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
