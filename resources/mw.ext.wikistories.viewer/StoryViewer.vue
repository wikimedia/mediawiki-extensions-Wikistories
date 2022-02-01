<template>
	<div class="viewer" :style="style">
		<div class="progress-container">
			<div
				v-for="n in storyLength"
				:key="n"
				class="progress">
				<div v-if="currentFrame.id === n" class="loading"></div>
				<div v-else-if="currentFrame.id > n" class="loaded"></div>
			</div>
		</div>
		<div
			v-if="currentFrame.text"
			class="story-text"
			v-html="currentFrame.text"></div>
		<!--    <ImageAttribution />-->
		<div
			v-if="storyEnd"
			class="restart-btn"
			@click="restartStory">
			Replay
		</div>
	</div>
</template>

<script>
// import ImageAttribution from '@components/ImageAttribution.vue'
// @vue/component
module.exports = {
	name: 'StoryViewer',
	components: {
		// ImageAttribution
	},
	props: {
		story: {
			type: Array,
			required: true
		}
	},
	data: function () {
		return {
			index: 1,
			frameDuration: 2000,
			storyEnd: false
		};
	},
	computed: {
		currentFrame: function () { return this.story[ this.index - 1 ]; },
		storyLength: function () { return this.story.length; },
		style: function () {
			return {
				backgroundImage: 'url(' + this.currentFrame.img + ')',
				backgroundPosition: 'center',
				backgroundSize: 'cover'
			};
		}
	},
	methods: {
		selectFrame: function ( i ) {
			this.index = i;
		},
		playNextFrame: function () {
			const timeoutId = setTimeout( function () {
				this.selectFrame( this.currentFrame.id + 1 );
				clearTimeout( timeoutId );
			}.bind( this ), this.frameDuration );
		},
		restartStory: function () {
			this.storyEnd = false;
			this.selectFrame( 1 );
		},
		endStory: function () {
			const timeoutId = setTimeout( function () {
				this.storyEnd = true;
				clearTimeout( timeoutId );
			}.bind( this ), this.frameDuration );
		}
	},
	beforeMount: function () {
		if ( this.currentFrame.id > 1 ) {
			this.restartStory();
		}
	},
	mounted: function () {
		if ( this.currentFrame.id < this.storyLength ) {
			this.playNextFrame();
		}
	},
	updated: function () {
		if ( this.currentFrame.id < this.storyLength ) {
			this.playNextFrame();
		} else if ( !this.storyEnd ) {
			this.endStory();
		}
	}
};
</script>

<style>
.viewer {
	height: 500px;
	width: 250px;
	position: relative;
	text-align: center;
	border-radius: 10px;
}

.story-text {
	position: absolute;
	bottom: 90px;
	left: 20px;
	right: 20px;
	border-radius: 10px;
	background-color: #fff;
	margin: 0;
	padding: 10px;
}

.restart-btn {
	position: absolute;
	bottom: 40px;
	left: 0;
	right: 0;
	margin: auto;
	background-color: #fff;
	padding: 8px;
	font-weight: bold;
	width: 90px;
	cursor: pointer;
}

.progress-container {
	display: flex;
	flex-direction: row;
	width: 100%;
	padding: 10px 0;
}

.progress {
	height: 4px;
	flex-grow: 1;
	margin: 0 5px;
	display: flex;
	background-color: #c4c4c4;
}

.progress .loading {
	height: 100%;
	width: 100%;
	background-color: #fff;
	animation-name: loading;
	animation-iteration-count: 1;
	/* TODO - ideally the animation duration is
set as var related to frameDuration  */
	animation-duration: 2s;
}

.progress .loaded {
	height: 100%;
	width: 100%;
	background-color: #fff;
}

@keyframes loading {
	from {
		width: 0%;
	}

	to {
		width: 100%;
	}
}
</style>
