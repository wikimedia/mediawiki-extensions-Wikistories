<?php

namespace MediaWiki\Extension\Wikistories;

class StorySubmitAction extends StoryEditAction {
	/**
	 * @return string
	 */
	public function getName() {
		return 'submit';
	}

	public function show() {
		parent::show();
	}
}
