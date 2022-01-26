<template>
    <div class="imagelistview">
        <div class="imagelistview__list-wrapper">
            <div class="imagelistview__list">
                <div v-for="item in items" :key="item.id" :data-id="item.id" @click="onSelect" class="imagelistview__list-image" :style="{width: `${item.width}px`}">
                    <img :src="item.thumb" :alt="item.title" loading="lazy"/>
                    <div :class="{checkbox: true, selected: selected.includes( item.id )}" />
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    module.exports = {
        name: 'ImageListView',
        props: [ 'items', 'select', 'selected' ],
        methods: {
            onSelect(e) {
                const id = e.target.getAttribute( 'data-id' ) ||
                    e.target.parentElement.getAttribute( 'data-id' ) // image tag element

                if ( this.selected.includes( id ) ) {
                    this.selected.splice(this.selected.indexOf( id ), 1);
                } else {
                    this.selected.push( id )
                }

                this.select( this.selected );
            }
        }
    }
</script>

<style lang="less">
    .imagelistview {
        width: 100%;
        height: 100%;
        background-color: #fff;
        text-align: left;
        overflow: scroll;
        display: -ms-flexbox;
        display: flex;
        -ms-flex-wrap: nowrap;
        flex-wrap: nowrap;
        margin: 6px 0 16px;
    }

    .imagelistview__list-wrapper {
        -ms-flex-pack: justify;
        justify-content: space-between;
        width: auto;
        -ms-flex: 1 1 auto;
        flex: 1 1 auto;
        -ms-flex-order: 1;
        order: 1;
        width: 100%;
    }
    .imagelistview__list {
        display: -ms-flexbox;
        display: flex;
        -ms-flex-wrap: wrap;
        flex-wrap: wrap;
        align-content: flex-start;
        justify-content: flex-start;
        margin: -8px;
        max-width: calc(100% + 16px);
    }
    .imagelistview__list .imagelistview__list-image {
        position: relative;
        -ms-flex-pack: justify;
        justify-content: space-between;
        width: auto;
        -ms-flex: 1 1 auto;
        flex: 1 1 auto;
        -ms-flex-order: 1;
        order: 1;
        justify-content: center;
    }
    .imagelistview__list-image {
        display: -ms-flexbox;
        display: flex;
        align-items: center;
        background-color: #eaecf0;
        box-sizing: border-box;
        height: 180px;
        margin: 8px;
        transition: box-shadow 100ms ease,outline 100ms ease;
        cursor: pointer;
    }
    .imagelistview__list-image:hover, .imagelistview__list-image:focus {
        box-shadow: 4px 4px 5px -2px #a2a9b1;
    }
    .imagelistview__list-image img {
        height: 100%;
        max-height: 100%;
        object-fit: cover;
        object-position: center center;
        pointer-events: none;
        width: 100%;
    }
    .imagelistview__list .checkbox {
        background-image: url(../images/check.svg);
        width: 20px;
        height: 20px;
        background-color: #fff;
        background-repeat: no-repeat;
        background-position: center center;
        position: absolute;
        left: 10px;
        top: 10px;
        border: 1px solid #2A4B8D;
        box-sizing: border-box;
        border-radius: 2px;
    }
    .imagelistview__list .checkbox.selected {
        background-color: #2A4B8D;
    }
</style>
