<?php

namespace MediaWiki\Extension\Wikistories;

class StorySubmitAction extends StoryEditAction {
	public function getName(): string {
		return 'submit';
	}

	public function show(): void {
		parent::show();
	}
}
