<?php

namespace MediaWiki\Extension\Wikistories;

use Content;
use Exception;
use SlotDiffRenderer;
use TextContent;
use TextSlotDiffRenderer;

class StorySlotDiffRenderer extends SlotDiffRenderer {

	/** @var StoryConverter */
	private $storyConverter;

	/**
	 * @param StoryConverter $storyConverter
	 */
	public function __construct( StoryConverter $storyConverter ) {
		$this->storyConverter = $storyConverter;
	}

	/**
	 * Get a diff between two content objects. One of them might be null (meaning a slot was
	 * created or removed), but both cannot be. $newContent (or if it's null then $oldContent)
	 * must have the same content model that was used to obtain this diff renderer.
	 *
	 * IMPORTANT: To develop/debug this code, you'll need to prevent diff caching. This can
	 * be done by adding the following code to your LocalSettings.php (or other config file).
	 * $wgHooks['AbortDiffCache'][] = function () { return false; };
	 *
	 * @param Content|null $oldContent
	 * @param Content|null $newContent
	 * @return string HTML, one or more <tr> tags.
	 * @throws Exception When the story structure is unexpected
	 */
	public function getDiff( Content $oldContent = null, Content $newContent = null ) {
		return TextSlotDiffRenderer::diff(
			$this->getText( $oldContent ),
			$this->getText( $newContent )
		);
	}

	/**
	 * @param Content $content
	 * @return string
	 * @throws Exception When $content is not of a supported model
	 */
	private function getText( Content $content ): string {
		if ( $content instanceof StoryContent ) {
			'@phan-var StoryContent $content';
			return $this->storyConverter->toLatest( $content )->getTextForDiff();
		} elseif ( $content instanceof TextContent ) {
			'@phan-var StoryContent $content';
			return $content->getText();
		}
		throw new Exception( 'Cannot diff story with content with model: ' . $content->getModel() );
	}
}
