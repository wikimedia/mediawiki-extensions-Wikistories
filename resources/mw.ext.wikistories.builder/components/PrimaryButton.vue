<template>
    <button name="button" class="primary-button" @click="onClick">{{ text }}</button>
</template>
<script>
    module.exports = {
        name: 'PrimaryButton',
        props: ['text', 'onClick']
    }
</script>
<style lang="less">
    .primary-button {
        background: #3366CC;
        border-radius: 2px;
        padding: 6px 12px;
        margin: 0;
        font-family: Helvetica Neue;
        font-style: normal;
        font-weight: bold;
        font-size: 16px;
        line-height: 22px;
        color: #ffffff;
        z-index: 101;
        flex: none;
        order: 0;
        flex-grow: 0;
        margin: 0px 6px;
        border: none;
        cursor: pointer;
    }
</style>
