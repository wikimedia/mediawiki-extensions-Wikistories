<template>
	<component :is="currentRoute.component" v-bind="currentRoute.props"></component>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;

// @vue/component
module.exports = {
	name: 'RouterView',
	computed: mapGetters( [ 'currentRoute' ] )
};
</script>
