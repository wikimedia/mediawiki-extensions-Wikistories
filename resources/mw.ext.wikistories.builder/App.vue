<template>
    <div class="storybuilder-app">
        <router-view></router-view>
    </div>
</template>

<script>
  var mapActions = require( 'vuex' ).mapActions;

  module.exports = {
    name: 'App',
    methods: mapActions( [ 'initNewStory' ] ),
    created: function () {
      this.initNewStory();
    }
  }
</script>

<style lang="less">
    .storybuilder-app {
        background-color: #2B81AF;
        padding: 20px;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
    }
</style>
