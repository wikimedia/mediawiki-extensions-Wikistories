<?php

namespace MediaWiki\Extension\Wikistories;

use EditPage;
use Html;
use MediaWiki\MediaWikiServices;
use OOUI\FieldLayout;
use OOUI\TextInputWidget;

class StoryEditPage extends EditPage {

	protected function showContentForm() {
		$maxFrames = $this->context->getConfig()->get( 'WikistoriesMaxFrames' );
		$maxTextLength = $this->context->getConfig()->get( 'WikistoriesMaxTextLength' );
		$out = $this->context->getOutput();
		/** @var StoryConverter $storyConverter */
		$storyConverter = MediaWikiServices::getInstance()
			->get( 'Wikistories.StoryConverter' );

		/** @var StoryContent $originalStory */
		$originalStory = $this->getContentObject();
		'@phan-var StoryContent $originalStory';

		$story = $storyConverter->toLatest( $originalStory );

		$currentFrames = $story->getFrames();
		$emptyFrame = (object)[
			'image' => (object)[ 'filename' => '', 'repo' => '' ],
			'text' => (object)[ 'value' => '' ],
		];

		$form = '<div class="ext-wikistories-editform">';
		$form .= new FieldLayout(
			new TextInputWidget( [ 'name' => "story_from_article", 'value' => $story->getFromArticle() ] ),
			[ 'label' => 'Related Article', 'align' => 'left' ]
		);
		for ( $i = 0; $i < $maxFrames; $i++ ) {
			$frame = $currentFrames[ $i ] ?? $emptyFrame;
			$form .= Html::element( 'h3', [], "Frame " . ( $i + 1 ) );
			$form .= new FieldLayout(
				new TextInputWidget(
					[ 'name' => "story_frame_{$i}_image_filename", 'value' => $frame->image->filename ]
				),
				[ 'label' => 'Image', 'align' => 'left' ]
			);
			$form .= new FieldLayout(
				new TextInputWidget(
					[ 'name' => "story_frame_{$i}_image_repo", 'value' => $frame->image->repo ]
				),
				[ 'label' => 'Repo', 'align' => 'left' ]
			);
			$form .= new FieldLayout(
				new TextInputWidget(
					[
						'name' => "story_frame_{$i}_text_value",
						'value' => $frame->text->value,
						'maxlength' => $maxTextLength,
					]
				),
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
		$story = [
			'fromArticle' => $request->getText( 'story_from_article' ),
			'frames' => []
		];

		$i = 0;
		while ( true ) {
			$filename = $request->getText( "story_frame_{$i}_image_filename" );
			$repo = $request->getText( "story_frame_{$i}_image_repo" );
			$text = $request->getText( "story_frame_{$i}_text_value" );
			if ( empty( $filename ) && empty( $repo ) && empty( $text ) ) {
				// stop reading as soon as all fields are empty
				break;
			}
			$story['frames'][] = [
				'image' => [
					'filename' => $filename,
					'repo' => $repo,
				],
				'text' => [
					'value' => $text
				],
			];
			$i++;
		}

		return json_encode( $story, JSON_PRETTY_PRINT );
	}

}
