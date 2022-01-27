<template>
    <div class="frames">
        <div
                v-for="frame in thumbnails"
                :key="frame.id"
                class="frame"
                :class="{'selected-frame': frame.selected}"
                :style="frame.style"
                @click="selectFrame(frame.id)" ></div>
        <div class="frame btn-add-frame" @click="addFrame">+</div>
    </div>
</template>
<script>
    var mapGetters = require( 'vuex' ).mapGetters;
    var mapActions = require( 'vuex' ).mapActions;
    
    module.exports =  {
        name: 'Frames',
        computed: mapGetters(['thumbnails']),
        methods: mapActions(['selectFrame', 'addFrame'])
    }
</script>
<style lang="less">
    .frames {
        width: 100%;
        padding: 14px 0;
        display: flex;
        flex-direction: row;
        align-items: center;
        overflow: hidden;
        border-top: solid black 1px;
    }
    .frame {
        width: 28px;
        height: 36px;
        margin-left: 15px;
        flex: none;
        cursor: pointer;
    }
    .btn-add-frame {
        display: grid;
        place-items: center;
        background-size: 20px;
        border: 1px dashed #000000;
        box-sizing: border-box;
        border-radius: 2px;
        font-size: 24px;
        text-align: center;
        vertical-align: middle;
        margin-left: auto;
        margin-right: 15px;
    }
    .selected-frame {
        outline: #3366CC auto 4px;
        outline-offset: 2px;
    }
</style>