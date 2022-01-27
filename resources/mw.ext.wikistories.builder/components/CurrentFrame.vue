<template>
    <div class="current-frame" :style="currentFrame.style">
        <p v-if="currentFrame.text" v-html="currentFrame.text"></p>
        <image-attribution></image-attribution>
    </div>
</template>

<script>
    var mapGetters = require( 'vuex' ).mapGetters;
    var ImageAttribution = require( './ImageAttribution.vue' )
    module.exports = {
        name: 'CurrentFrame',
        components: {
            'image-attribution': ImageAttribution
        },
        computed: mapGetters(['currentFrame'])
    }
</script>

<style lang="less">
    .current-frame {
        height: 90%;
        position: relative;
        text-align: center;

        p {
            position: absolute;
            bottom: 60px;
            left: 20px;
            right: 20px;
            border-radius: 10px;
            background-color: white;
            margin: 0;
            padding: 10px;
        }
    }
    
</style>
