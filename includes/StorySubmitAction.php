<?php

namespace MediaWiki\Extension\Wikistories;

class StorySubmitAction extends StoryEditAction {

	public function getName() {
		return 'submit';
	}

	public function show() {
		parent::show();
	}
}
