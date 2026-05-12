( function( api ) {

	// Extends our custom "audio-podcast" section.
	api.sectionConstructor['audio-podcast'] = api.Section.extend( {

		// No events for this type of section.
		attachEvents: function () {},

		// Always make the section active.
		isContextuallyActive: function () {
			return true;
		}
	} );

} )( wp.customize );