import Image from './../resources/components/StoryImage.vue';

// More on default export: https://storybook.js.org/docs/vue/writing-stories/introduction#default-export
export default {
	title: 'Wikistories/Image',
	component: Image,
	// More on argTypes: https://storybook.js.org/docs/vue/api/argtypes
	argTypes: {
		src: { type: 'string' },
	},
};

// More on component templates: https://storybook.js.org/docs/vue/writing-stories/introduction#using-args
const Template = (args) => ({
	// Components used in your story `template` are defined in the `components` object
	components: { 'x-image': Image },
	// The story's `args` need to be mapped into the template through the `setup()` method
	setup() {
		return {
			src: args.src,
			focalRect: args.focalRect,
			styles: {
				imgSection: {
					position: 'relative',
					margin: '10px',
				},
				focalRect: {
					position: 'absolute',
					left: args.focalRect ? args.focalRect.x * args.imageSize.width + 'px' : null ,
					top: args.focalRect ? args.focalRect.y * args.imageSize.height + 'px' : null ,
					width: args.focalRect ? args.focalRect.width * args.imageSize.width + 'px' : null ,
					height: args.focalRect ? args.focalRect.height * args.imageSize.height + 'px' : null ,
					border: 'solid red 1px',
					pointerEvents: 'none'
				},
			},
			devices: args.devices.map( d => {
				return {
					name: d.name,
					style: {
						width: d.width + 'px',
						height: d.height + 'px',
						margin: '10px',
						position: 'relative'
					}
				};
			} )
		};
	},
	template:
		'<h2>Original image with focal rectangle (in red)</h2>' +
		'<div :style="styles.imgSection">' +
			'<img :src="src" />'  +
			'<div :style="styles.focalRect"/>' +
		'</div>' +
		'<h2>&lt;Image&gt; component for various devices</h2>' +
		'<div v-for="device in devices" :style="device.style" :title="device.name">' +
			'<x-image :src="src" :rect="focalRect" :alt="src" :thumbnail="false" :allow-gestures="false" />' +
		'</div>',
});

const imageSize = { width: 640, height: 427 }

const devices = [
	{
		name: 'iPhone SE',
		width: 375,
		height: 667,
	},
	{
		name: 'iPhone 12 Pro',
		width: 390,
		height: 844,
	},
	{
		name: 'Desktop (small)',
		width: 1200,
		height: 800,
	},
];

export const CatFace = Template.bind({});
CatFace.args = {
	src: 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Felis_catus-cat_on_snow.jpg/640px-Felis_catus-cat_on_snow.jpg',
	focalRect: {
		x: ( 120 / imageSize.width ),
		y: ( 25 / imageSize.height ),
		width: ( 140 / imageSize.width ),
		height: ( 160 / imageSize.height ),
	},
	devices: devices,
	imageSize: imageSize
};

export const CatPaw = Template.bind({});
CatPaw.args = {
	src: 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Felis_catus-cat_on_snow.jpg/640px-Felis_catus-cat_on_snow.jpg',
	focalRect: {
		x: ( 200 / imageSize.width ),
		y: ( 260 / imageSize.height ),
		width: ( 120 / imageSize.width ),
		height: ( 140 / imageSize.height ),
	},
	devices: devices,
	imageSize: imageSize
};

export const CatTail = Template.bind({});
CatTail.args = {
	src: 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Felis_catus-cat_on_snow.jpg/640px-Felis_catus-cat_on_snow.jpg',
	focalRect: {
		x: ( 490 / imageSize.width ),
		y: ( 210 / imageSize.height ),
		width: ( 135 / imageSize.width ),
		height: ( 110 / imageSize.height ),
	},
	devices: devices,
	imageSize: imageSize
};

export const CatWhole = Template.bind({});
CatWhole.args = {
	src: 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Felis_catus-cat_on_snow.jpg/640px-Felis_catus-cat_on_snow.jpg',
	focalRect: {
		x: ( 125 / imageSize.width ),
		y: ( 35 / imageSize.height ),
		width: ( 370 / imageSize.width ),
		height: ( 370 / imageSize.height ),
	},
	devices: devices,
	imageSize: imageSize
};

export const NullRect = Template.bind({});
NullRect.args = {
	src: 'https://upload.wikimedia.org/wikipedia/commons/thumb/b/b6/Felis_catus-cat_on_snow.jpg/640px-Felis_catus-cat_on_snow.jpg',
	focalRect: null,
	devices: devices,
	imageSize: imageSize
};
