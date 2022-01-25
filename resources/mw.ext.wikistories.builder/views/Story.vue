<template>
    <div class="storybuilder-story">
        <primary-button class="publish-button"
                text="Next"
        ></primary-button>
        <current-frame></current-frame>
        <frames></frames>
    </div>
</template>

<script>
  var mapGetters = require( 'vuex' ).mapGetters;
  var PrimaryButton = require( '../components/PrimaryButton.vue' );
  var CurrentFrame = require( '../components/CurrentFrame.vue' );
  var Frames = require( '../components/Frames.vue' );
  module.exports = {
    name: 'Story',
    components: {
        'primary-button': PrimaryButton,
        'current-frame': CurrentFrame,
        'frames': Frames
    },
    computed: mapGetters( [ 'currentFrame' ] ),
  }
</script>

<style lang="less">
    .storybuilder-story {
        font-size: 24px;
        height: 100vh;
        overflow: hidden;
        position: relative;
    }

    .publish-button {
        position: absolute;
        right: 24px;
        top: 24px;
    }
</style>
