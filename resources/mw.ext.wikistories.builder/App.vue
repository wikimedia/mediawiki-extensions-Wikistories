<template>
    <div class="storybuilder-app">
        <router-view></router-view>
    </div>
</template>

<script>
  module.exports = {
    name: 'App'
  }
</script>

<style lang="less">
    .storybuilder-app {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;

        -webkit-font-smoothing: antialiased;
        -moz-osx-font-smoothing: grayscale;
        color: #2c3e50;
        margin: 0 auto;
        background-color: white;
        width: 100%;
        max-width: 500px;
        height: 100vh;
        overflow: hidden;
    }
</style>
