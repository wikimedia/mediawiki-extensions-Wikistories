const logContributionEvent = ( data ) => {
	if ( !mw.eventLog ) {
		return;
	}

	const streamName = 'mediawiki.wikistories_contribution_event';
	const event = $.extend( {
		/* eslint-disable camelcase */
		$schema: '/analytics/mediawiki/wikistories_contribution_event/1.0.0',
		meta: {
			stream: streamName,
			domain: new mw.Uri().host,
			dt: new Date().toISOString()
		},
		access_method: mw.config.get( 'skin' ) === 'minerva' ? 'mobile web' : 'desktop',
		user_name: mw.user.isAnon() ? undefined : mw.config.get( 'wgUserName' ),
		user_edit_count_bucket: mw.user.isAnon() ? undefined : mw.config.get( 'wgUserEditCountBucket' ).slice( 0, -6 ),
		user_is_anonymous: mw.user.isAnon(),
		context_page_title: mw.config.get( 'wgWikistoriesStoryContent' ).fromArticle,
		story_already_exists: false,
		activity_session_id: mw.eventLog.id.getSessionId()
		/* eslint-enable camelcase */
	}, data );

	mw.eventLog.submit( streamName, event );
};

const logStoryBuilderOpen = () => {
	logContributionEvent( {
		// eslint-disable-next-line camelcase
		event_type: 'story_builder_open'
	} );
};

const logPublishSuccess = ( storyTitle ) => {
	logContributionEvent( {
		/* eslint-disable camelcase */
		event_type: 'publish_success',
		story_title: storyTitle
		/* eslint-enable camelcase */
	} );
};

const logPublishFailure = ( storyTitle, failureMessage ) => {
	logContributionEvent( {
		/* eslint-disable camelcase */
		event_type: 'publish_failure',
		story_title: storyTitle,
		publish_failure_message: failureMessage
		/* eslint-enable camelcase */
	} );
};

module.exports = {
	logStoryBuilderOpen: logStoryBuilderOpen,
	logPublishSuccess: logPublishSuccess,
	logPublishFailure: logPublishFailure
};
