<?php

namespace MediaWiki\Extension\Wikistories;

use MediaWiki\Actions\EditAction;

class StoryEditAction extends EditAction {

	public function show() {
		$this->useTransactionalTimeLimit();
		$editPage = new StoryEditPage( $this->getArticle() );
		$editPage->setContextTitle( $this->getTitle() );
		$editPage->edit();
	}

}
