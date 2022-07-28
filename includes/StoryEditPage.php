<?php

namespace MediaWiki\Extension\Wikistories;

use EditPage;
use Html;
use MediaWiki\MediaWikiServices;
use OOUI\FieldLayout;
use OOUI\HiddenInputWidget;
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
			'image' => (object)[ 'filename' => '' ],
			'text' => (object)[
				'value' => '',
				'fromArticle' => (object)[
					'articleTitle' => '',
					'originalText' => '',
				],
			],
		];

		$form = '<div class="ext-wikistories-editform">';
		$form .= new FieldLayout(
			new TextInputWidget( [ 'name' => "story_from_article", 'value' => $story->getFromArticle() ] ),
			[
				'label' => $this->context->msg( 'wikistories-nojs-form-label-related-article' )->text(),
				'align' => 'left'
 ]
		);
		for ( $i = 0; $i < $maxFrames; $i++ ) {
			$frame = $currentFrames[ $i ] ?? $emptyFrame;
			$form .= Html::element( 'h3', [],
				$this->context->msg( 'wikistories-nojs-form-label-frame' )->params( $i + 1 )->text()
 );
			$form .= new FieldLayout(
				new TextInputWidget(
					[ 'name' => "story_frame_{$i}_image_filename", 'value' => $frame->image->filename ]
				),
				[
					'label' => $this->context->msg( 'wikistories-nojs-form-label-image' )->text(),
					'align' => 'left'
				]
			);
			$form .= new FieldLayout(
				new TextInputWidget(
					[
						'name' => "story_frame_{$i}_text_value",
						'value' => $frame->text->value,
						'maxlength' => $maxTextLength,
					]

				),
				[
					'label' => $this->context->msg( 'wikistories-nojs-form-label-text' )->text(),
					'align' => 'left'
				]
			);
			$form .= new HiddenInputWidget(
				[
					'name' => "story_frame_{$i}_text_fromArticle_articleTitle",
					'value' => $frame->text->fromArticle->articleTitle ?? '',
				]

			);
			$form .= new HiddenInputWidget(
				[
					'name' => "story_frame_{$i}_text_fromArticle_originalText",
					'value' => $frame->text->fromArticle->originalText ?? '',
				]

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
			$text = $request->getText( "story_frame_{$i}_text_value" );
			$articleTitle = $request->getText( "story_frame_{$i}_text_fromArticle_articleTitle" );
			$originalText = $request->getText( "story_frame_{$i}_text_fromArticle_originalText" );
			if ( empty( $filename ) && empty( $text ) ) {
				// stop reading as soon as all fields are empty
				break;
			}
			$story['frames'][] = [
				'image' => [
					'filename' => $filename,
				],
				'text' => [
					'value' => $text,
					'fromArticle' => [
						'articleTitle' => $articleTitle,
						'originalText' => $originalText,
					],
				],
			];
			$i++;
		}

		return json_encode( $story, JSON_PRETTY_PRINT );
	}

}
