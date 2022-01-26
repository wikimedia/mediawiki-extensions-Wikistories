<template>
    <div class="current-frame" :style="currentFrame.style">
        <p v-if="currentFrame.text" v-html="currentFrame.text"></p>
<!--        TODO: Replace the link below with the proper CTA (design not finalized)-->
        <router-link class="select-text-btn" to="/article">Select text from article</router-link>
        <image-attribution></image-attribution>
    </div>
</template>

<script>
    const mapGetters = require( 'vuex' ).mapGetters;
    const ImageAttribution = require( './ImageAttribution.vue' );
    module.exports = {
        name: 'CurrentFrame',
        components: {
            'image-attribution': ImageAttribution
        },
        computed: mapGetters( [ 'currentFrame' ] )
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

        .select-text-btn {
            color: #fff;
            position: absolute;
            bottom: 20px;
            left: 20px;
            right: 20px;
            font-size: 0.5em;

        }
    }
</style>
