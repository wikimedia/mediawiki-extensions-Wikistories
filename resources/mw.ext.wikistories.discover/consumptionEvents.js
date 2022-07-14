const logConsumptionEvent = ( data ) => {
	const streamName = 'analytics/mediawiki/wikistories_consumption_event';
	const event = $.extend( {
		$schema: '/analytics/mediawiki/wikistories_consumption_event/1.0.0',
		meta: {
			stream: 'mediawiki.wikistories_consumption_event',
			domain: location.host,
			dt: new Date().toISOString()
		},
		/* eslint-disable camelcase */
		activity_session_id: mw.eventLog.id.getSessionId(),
		pageview_id: mw.user.getPageviewToken(),
		access_method: mw.config.get( 'skin' ) === 'minerva' ? 'mobile web' : 'desktop',
		page_title: mw.config.get( 'wgTitle' )
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

const logStoryView = ( storyTitle, frameCount, framesViewed, storyOpenTime, storiesCount ) => {
	logConsumptionEvent( {
		/* eslint-disable camelcase */
		event_type: 'story_view',
		story_title: storyTitle,
		story_frame_count: frameCount,
		story_frames_viewed: framesViewed,
		story_completed: frameCount === framesViewed,
		story_open_time: storyOpenTime,
		page_story_count: storiesCount
		/* eslint-enable camelcase */
	} );
};

module.exports = {
	logStoriesImpression: logStoriesImpression,
	logStoryView: logStoryView
};
