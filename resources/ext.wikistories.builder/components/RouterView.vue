<template>
	<component :is="currentRoute.component" v-bind="currentRoute.props"></component>
</template>

<script>
const mapGetters = require( 'vuex' ).mapGetters;

// @vue/component
module.exports = {
	name: 'RouterView',
	compatConfig: { MODE: 3 },
	compilerOptions: { whitespace: 'condense' },
	computed: mapGetters( [ 'currentRoute' ] )
};
</script>
