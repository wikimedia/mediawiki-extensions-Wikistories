<?php

namespace MediaWiki\Extension\Wikistories;

use EditPage;
use Html;
use OOUI\FieldLayout;
use OOUI\TextInputWidget;

class StoryEditPage extends EditPage {

	protected function showContentForm() {
		$out = $this->context->getOutput();

		/** @var StoryContent $story */
		$story = $this->getCurrentContent();
		'@phan-var StoryContent $story';
		$currentFrames = $story->getFrames();
		$emptyFrame = (object)[ 'img' => '', 'text' => '' ];

		$form = '<div class="story-editor">';
		for ( $i = 0; $i < StoryContent::MAX_FRAMES; $i++ ) {
			$frame = $currentFrames[ $i ] ?? $emptyFrame;
			$form .= Html::element( 'h3', [], "Frame " . ( $i + 1 ) );
			$form .= new FieldLayout(
				new TextInputWidget( [ 'name' => "story_frame_{$i}_img", 'value' => $frame->img ] ),
				[ 'label' => 'Image', 'align' => 'left' ]
			);
			$form .= new FieldLayout(
				new TextInputWidget( [ 'name' => "story_frame_{$i}_text", 'value' => $frame->text ] ),
				[ 'label' => 'Text', 'align' => 'left' ]
			);
		}

		$form .= '</div>';
		$out->enableOOUI();
		$out->addHTML( $form );
	}

	/**
	 * @param \WebRequest &$request
	 * @return false|string|null
	 */
	protected function importContentFormData( &$request ) {
		$story = [ 'frames' => [] ];

		$i = 0;
		while ( true ) {
			$img = $request->getText( "story_frame_{$i}_img" );
			$text = $request->getText( "story_frame_{$i}_text" );
			if ( empty( $img ) && empty( $text ) ) {
				// stop reading as soon as both are empty
				break;
			}
			$story['frames'][] = [ 'img' => $img, 'text' => $text ];
			$i++;
		}

		return json_encode( $story, JSON_PRETTY_PRINT );
	}

}
