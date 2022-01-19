<template>
    <div class="storybuilder-story">
        Story builder
        <br/>
        {{ JSON.stringify( this.currentFrame ) }}
    </div>
</template>

<script>
  var mapGetters = require( 'vuex' ).mapGetters;
  module.exports = {
    name: 'Story',
    computed: mapGetters( [ 'currentFrame' ] )
  }
</script>

<style lang="less">
    .storybuilder-story {
        font-size: 24px;
    }
</style>
