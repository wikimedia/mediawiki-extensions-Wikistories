const contributionAttemptId = mw.user.generateRandomSessionId();

const logContributionEvent = ( data ) => {
	const streamName = 'mediawiki.wikistories_contribution_event';
	const event = $.extend( {
		/* eslint-disable camelcase */
		$schema: '/analytics/mediawiki/wikistories_contribution_event/1.3.0',
		meta: {
			stream: streamName,
			domain: location.host
		},
		access_method: mw.config.get( 'skin' ) === 'minerva' ? 'mobile web' : 'desktop',
		user_name: mw.user.isAnon() ? undefined : mw.config.get( 'wgUserName' ),
		user_edit_count_bucket: mw.user.isAnon() ? undefined : mw.config.get( 'wgUserEditCountBucket' ).slice( 0, -6 ),
		user_is_anonymous: mw.user.isAnon(),
		context_page_title: mw.config.get( 'wgWikistoriesStoryContent' ).articleTitle,
		activity_session_id: mw.eventLog.id.getSessionId(),
		contribution_attempt_id: contributionAttemptId
		/* eslint-enable camelcase */
	}, data );

	mw.eventLog.submit( streamName, event );
};

const logStoryBuilderOpen = ( storyExists ) => {
	logContributionEvent( {
		/* eslint-disable camelcase */
		event_type: 'story_builder_open',
		story_already_exists: storyExists
		/* eslint-enable camelcase */
	} );
};

const logPublishSuccess = ( storyTitle, storyExists ) => {
	logContributionEvent( {
		/* eslint-disable camelcase */
		event_type: 'publish_success',
		story_title: storyTitle,
		story_already_exists: storyExists
		/* eslint-enable camelcase */
	} );
};

const logPublishFailure = ( storyTitle, storyExists, failureMessage ) => {
	logContributionEvent( {
		/* eslint-disable camelcase */
		event_type: 'publish_failure',
		story_title: storyTitle,
		story_already_exists: storyExists,
		publish_failure_message: failureMessage
		/* eslint-enable camelcase */
	} );
};

const logShareAction = ( storyTitle ) => {
	logContributionEvent( {
		/* eslint-disable camelcase */
		event_type: 'story_share',
		story_title: storyTitle
		/* eslint-enable camelcase */
	} );
};

module.exports = {
	logStoryBuilderOpen: logStoryBuilderOpen,
	logPublishSuccess: logPublishSuccess,
	logPublishFailure: logPublishFailure,
	logShareAction: logShareAction
};
