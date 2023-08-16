const splitSentences = require( '../../../../resources/ext.wikistories.builder/util/splitSentences.js' );

describe( 'splitSentences', function () {

	const tests = [
		{
			name: 'case for two sentences',
			text: 'Cats are commonly kept as house pets but can also be farm cats or feral cats! The feral cat ranges freely and avoids human contact.',
			expected: '<span class="classname">Cats are commonly kept as house pets but can also be farm cats or feral cats!</span> <span class="classname">The feral cat ranges freely and avoids human contact.</span>'
		},
		{
			name: 'case for i.e.',
			text: 'As a predator, it is crepuscular, i.e most active at dawn and dusk.',
			expected: '<span class="classname">As a predator, it is crepuscular, i.e most active at dawn and dusk.</span>'
		},
		{
			name: 'case for number',
			text: 'the human density was smaller than 0.5 per km2 prior to 1850.',
			expected: '<span class="classname">the human density was smaller than 0.5 per km2 prior to 1850.</span>'
		},
		{
			name: 'case for Mr. and Mrs.',
			text: 'Mr. Ghost and Mrs. Chicken grossed $4 million in the first five months.',
			expected: '<span class="classname">Mr. Ghost and Mrs. Chicken grossed $4 million in the first five months.</span>'
		},
		{
			name: 'case for no full stop',
			text: 'The Ghost and Mr. Chicken grossed $4 million in the first five months',
			expected: '<span class="classname">The Ghost and Mr. Chicken grossed $4 million in the first five months</span>'
		},
		{
			name: 'case for html element',
			text: '<p>The <b>cat</b> (<i><b>Felis catus</b></i>) is a <a href="#">domestic</a> <a href="#">species</a> of small <a href="#">carnivorous</a> <a href="#">mammal</a>.</p>',
			expected: '<span class="classname"><p>The <b>cat</b> (<i><b>Felis catus</b></i>) is a <a href="#">domestic</a> <a href="#">species</a> of small <a href="#">carnivorous</a> <a href="#">mammal</a>.</p></span>'
		}
	];

	tests.forEach( test => {
		it( test.name, () => {
			const element = document.createElement( 'span' );
			element.appendChild( document.createTextNode( test.text ) );
			const expected = test.expected;

			expect( splitSentences( document.createNodeIterator( element ).root, 'classname' ) ).toEqual( expected );
		} );
	} );

} );
