<template>
  <div class="storybuilder-search">
    <navigator
      :next="editStory"
      :info="selection.length"
    ></navigator>
    <form @submit="onSubmit($event)">
      <div class="label">Search</div>
      <input class="query" type="text" :value="query" @input="onInput"/>
      <div class="icon"></div>
      <div v-if="query" class="close" @click="onClear"></div>
      <div v-if="loading" class="loading-bar"></div>
    </form>
    <image-list :items="results" :select="onItemSelect" :selected="selection"></image-list>
  </div>
</template>

<script>
  var mapGetters = require( 'vuex' ).mapGetters;
  var mapActions = require( 'vuex' ).mapActions;
  var ImageListView = require( '../components/ImageListView.vue' );
  var Navigator = require( '../components/Navigator.vue' );

  module.exports = {
    name: 'Search',
    components: {
      'image-list': ImageListView,
      'navigator': Navigator
    },
    computed: mapGetters(['selection', 'loading', 'results', 'query']),
    methods: {
      ...mapActions(['select', 'search', 'clear', 'resetFrame']),
      onSubmit: e => e.preventDefault(),
      onInput: function(e) {
          e.preventDefault();
          this.search(e.target.value);
        },
        onClear: function(e) {
          e.preventDefault();
          this.clear();
        },
        onItemSelect: function( data ) {
          this.select( data );
        },
        editStory: function(){
          var array = this.selection.map( ( id, index ) => {
            var item = this.results.find( result => result.id === id );
            return {
              id: index + 1,
              img: item.thumb,
              text: item.desc,
              imgTitle: item.title,
              attribution: item.attribution
            }
          })
          this.resetFrame(array);
          this.$router.push( { name: 'Story' } );
        }
    }
  }
</script>

<style lang="less">
    .storybuilder-search {
      height: 100%;
      padding: 0 15px 0 15px;
      background-color: #fff;
    }
    form {
      position: relative;
      text-align: left;
      padding: 10px 0;
    }
    .label {
      font-size: 18px;
      font-style: normal;
      font-weight: bold;
      line-height: 25px;
      letter-spacing: 0px;
      margin: 5px 10px;
    }
    .query {
      height: 36px;
      border: 2px solid #3366CC;
      box-sizing: border-box;
      border-radius: 2px;
      padding-left: 35px;
      width: 100%;
    }
    .icon {
      background-image: url(../images/search.svg);
      width: 20px;
      height: 20px;
      position: absolute;
      bottom: 18px;
      left: 10px;
    }
    .close {
      background-image: url(../images/close.svg);
      width: 20px;
      height: 20px;
      position: absolute;
      bottom: 18px;
      right: 10px;
      padding: 0;
      cursor: pointer;
    }
    .loading-bar {
      position: absolute;
      height: 3px;
      width: 130px;
      border-radius: 3px;
      margin-top: 10px;
      background: #3366cc;
      animation-name: loader;
      animation-duration: 2s;
      animation-iteration-count: infinite;
      animation-timing-function: ease;
    }
    @keyframes loader {
      0%   {transform: translateX(0px);}
      50%  {transform: translateX(calc( 100vw - 40px ));}
      100% {transform: translateX(0px);}
    }
    @keyframes loader-desktop {
      0%   {transform: translateX(0px);}
      50%  {transform: translateX(calc( 500px - 175px ));}
      100% {transform: translateX(0px);}
    }
    @media screen and (min-width: 500px) {
      .loading-bar {
        animation-name: loader-desktop;
      }
    }
</style>
