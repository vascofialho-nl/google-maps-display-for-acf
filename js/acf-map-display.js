// ====================================================================================================== 
// scripts file
// ====================================================================================================== 


(function( $ ) { 

    /**
     * Initialize a Google Map on the given jQuery element.
     * 
     * Finds all markers within the element and renders them on the map.
     * Sets map options like zoom level, map type, and the Map ID from a data attribute or default.
     * Calls centerMap() to adjust viewport to include all markers.
     * 
     * @param {jQuery} $el The jQuery element representing the map container.
     * @returns {google.maps.Map} The created Google Map instance.
     */
    function initMap( $el ) {
        // Find all child elements with the class 'marker' to place on the map
        var $markers = $el.find('.marker');

        // Get mapId from data attribute if set; fallback to default Map ID string (update as needed)
        var mapId = $el.data('mapid') || '4f4f04b83dc77fd8699d2a25';

        // Set map options: zoom level from data attribute or default 16, map type Roadmap, and Map ID
        var mapArgs = {
            zoom        : $el.data('zoom') || 16,
            mapTypeId   : google.maps.MapTypeId.ROADMAP,
            mapId       : mapId
        };

        // Instantiate a new Google Map object on the element
        var map = new google.maps.Map( $el[0], mapArgs );

        // Create an array on the map object to hold marker instances
        map.markers = [];

        // Iterate over each marker element to initialize and place markers
        $markers.each(function(){
            initMarker( $(this), map );
        });

        // Adjust map center and zoom to include all markers
        centerMap( map );

        // Return the map instance for possible further use
        return map;
    }

    /**
     * Initialize a single marker on the map
     * 
     * Reads latitude and longitude data attributes from the marker element,
     * creates a Google Maps AdvancedMarkerElement object, and adds it to the map.
     * If the marker element contains HTML content, attaches an info window that
     * opens on marker click.
     * 
     * @param {jQuery} $marker The jQuery element representing a marker.
     * @param {google.maps.Map} map The Google Map instance where the marker is added.
     */
	function initMarker($marker, map) {
	  var lat = $marker.data('lat');
	  var lng = $marker.data('lng');
	  var latLng = { lat: parseFloat(lat), lng: parseFloat(lng) };

	  // Create an AdvancedMarkerElement
	  const marker = new google.maps.marker.AdvancedMarkerElement({
		position: latLng,
		map: map,
	  });

	  map.markers.push(marker);

	  if ($marker.html()) {
		const infowindow = new google.maps.InfoWindow({
		  content: $marker.html(),
		});

		marker.addListener('click', () => {
		  infowindow.open({
			map: map,
			position: marker.position,
		  });
		});
	  }
	}

    /**
     * Center the map to display all markers optimally
     * 
     * Uses Google Maps LatLngBounds to create boundaries that encompass
     * all markers, then sets the map viewport accordingly.
     * If there is only one marker, centers the map on it without zooming out.
     * 
     * @param {google.maps.Map} map The Google Map instance to center.
     */
    function centerMap( map ) {
        // Create a new LatLngBounds object to hold map boundaries
        var bounds = new google.maps.LatLngBounds();

        // Extend bounds to include each marker's position
        map.markers.forEach(function( marker ){
           bounds.extend(marker.position);
        });

        // If only one marker, center the map on that marker
        if( map.markers.length == 1 ){
            map.setCenter( bounds.getCenter() );
        } else {
            // For multiple markers, adjust zoom and center to fit all markers
            map.fitBounds( bounds );
        }
    }

    /**
     * On document ready, find all elements with class 'acf-map'
     * and initialize Google Maps on each of them.
     */
    $(document).ready(function(){
        $('.acf-map').each(function(){
            initMap( $(this) );
        });
    });

})(jQuery);

console.log("Google Maps Display for ACF script loaded!");




