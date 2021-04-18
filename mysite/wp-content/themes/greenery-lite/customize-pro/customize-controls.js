( function( api ) {

	// Extends our custom "greenery-lite" section.
	api.sectionConstructor['greenery-lite'] = api.Section.extend( {

		// No events for this type of section.
		attachEvents: function () {},

		// Always make the section active.
		isContextuallyActive: function () {
			return true;
		}
	} );

} )( wp.customize );