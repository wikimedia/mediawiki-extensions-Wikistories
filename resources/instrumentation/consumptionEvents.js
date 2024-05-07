const logConsumptionEvent = ( data ) => {
	const streamName = 'mediawiki.wikistories_consumption_event';
	const event = $.extend( {
		$schema: '/analytics/mediawiki/wikistories_consumption_event/1.2.0',
		meta: {
			stream: streamName,
			domain: location.host
		},
		/* eslint-disable camelcase */
		activity_session_id: mw.eventLog.id.getSessionId(),
		pageview_id: mw.user.getPageviewToken(),
		access_method: mw.config.get( 'skin' ) === 'minerva' ? 'mobile web' : 'desktop',
		page_title: mw.config.get( 'wgPageName' ),
		user_is_anonymous: mw.user.isAnon()
		/* eslint-enable camelcase */
	}, data );
	mw.eventLog.submit( streamName, event );
};

const logStoriesImpression = ( storiesCount ) => {
	logConsumptionEvent( {
		/* eslint-disable camelcase */
		event_type: 'story_impression',
		page_story_count: storiesCount
		/* eslint-enable camelcase */
	} );
};

const logViewEvent = (
	eventType, storyTitle, frameCount, framesViewed,
	storyOpenTime, storiesCount
) => {
	logConsumptionEvent( {
		/* eslint-disable camelcase */
		event_type: eventType,
		story_title: storyTitle,
		story_frame_count: frameCount,
		story_frames_viewed: framesViewed,
		story_completed: frameCount === framesViewed,
		story_open_time: storyOpenTime,
		page_story_count: storiesCount
		/* eslint-enable camelcase */
	} );
};

const logStoryView = ( storyTitle, frameCount, framesViewed, storyOpenTime, storiesCount ) => {
	const uri = new mw.Uri();
	const eventType = uri.query.action === 'storyview' ? 'story_share' : 'story_view';
	logViewEvent( eventType, storyTitle, frameCount, framesViewed, storyOpenTime, storiesCount );
};

module.exports = {
	logStoriesImpression: logStoriesImpression,
	logStoryView: logStoryView
};
