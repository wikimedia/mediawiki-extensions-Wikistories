<?php

namespace MediaWiki\Extension\Wikistories;

use Content;
use LogicException;
use SlotDiffRenderer;
use TextContent;
use TextSlotDiffRenderer;

class StorySlotDiffRenderer extends SlotDiffRenderer {

	private StoryConverter $storyConverter;
	private TextSlotDiffRenderer $textSlotDiffRenderer;

	public function __construct(
		StoryConverter $storyConverter,
		TextSlotDiffRenderer $textSlotDiffRenderer
	) {
		$this->storyConverter = $storyConverter;
		$this->textSlotDiffRenderer = $textSlotDiffRenderer;
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
	 */
	public function getDiff( Content $oldContent = null, Content $newContent = null ) {
		$this->normalizeContents( $oldContent, $newContent, [ StoryContent::class, TextContent::class ] );

		return $this->textSlotDiffRenderer->getTextDiff(
			$this->getText( $oldContent ),
			$this->getText( $newContent )
		);
	}

	private function getText( Content $content ): string {
		if ( $content instanceof StoryContent ) {
			return $this->storyConverter->toLatest( $content )->getTextForDiff();
		} elseif ( $content instanceof TextContent ) {
			return $content->getText();
		}
		throw new LogicException( 'Cannot diff story with content with model: ' . $content->getModel() );
	}
}
