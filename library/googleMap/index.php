<script src="http://maps.google.com/maps?file=api&v=2&key=ABQIAAAA5j6CaAq6jb-L4n_GUBBUCBQEEC45hxakQjMCX1FRL5ZlqDBldhSq5Z7QOaLiLLdrGyCpnJBRdKRODA&sensor=false"
        type="text/javascript">
</script>

<script src="circle.js" type="text/javascript">
</script>


<?php  

$getList = $_GET["cilist"];
$url = $_GET["url"];

$list = explode(',', $getList);

foreach($list as $key => $value) {
	$list[$key] = explode('|', $value);
}


$amount = count($list);

?>

    <script type="text/javascript">

    var map;
    var geocoder;
    var address;

    var markers = new Array();
    
function initialize() {
  if (GBrowserIsCompatible()) {
    var map = new GMap2(document.getElementById("map_canvas"),
    	{ size: new GSize(690,490) } );
    map.setCenter(new GLatLng(48.208569, 16.381875), 13);

	map.addControl(new GScaleControl());
    geocoder = new GClientGeocoder();
    
    var customUI = map.getDefaultUI();
    // Remove MapType.G_HYBRID_MAP
    //customUI.maptypes.hybrid = false;
    map.setUI(customUI);


    
    //GEvent.addListener(map, "click", getAddress);


    

	// handle new marker action


 function getAddress(overlay, latlng) {
      if (latlng != null) {
        address = latlng;
        geocoder.getLocations(latlng, showAddress);
      }
    }







	

 function showAddress(response) {
    // map.clearOverlays();
     if (!response || response.Status.code != 200) {
       alert("Status Code:" + response.Status.code);
     } else {
       place = response.Placemark[0];
       point = new GLatLng(place.Point.coordinates[1],
                           place.Point.coordinates[0]);
       marker = new GMarker(point);
       map.addOverlay(marker);
       marker.openInfoWindowHtml(
       '<b>orig latlng:</b>' + response.name + '<br/>' + 
       '<b>latlng:</b>' + place.Point.coordinates[1] + "," + place.Point.coordinates[0] + '<br>' +
       '<b>Status Code:</b>' + response.Status.code + '<br>' +
       '<b>Status Request:</b>' + response.Status.request + '<br>' +
       '<b>Address:</b>' + place.address + '<br>' +
       '<b>Accuracy:</b>' + place.AddressDetails.Accuracy + '<br>' +
       '<b>Country code:</b> ' + place.AddressDetails.Country.CountryNameCode);
     }
   }
 




    
    // Creates a marker at the given point
    // Clicking the marker will hide it
    function createMarker(latlng, number, isActive) {
       var sender = "sender.png";

       if(isActive) {
    	   sender = "sender.png";
       } else {
    	   sender = "inactivesender.png";
       }
       
      var marker = new GMarker(latlng, new GIcon(G_DEFAULT_ICON, sender));
      marker.value = number;
      GEvent.addListener(marker,"click", function() {
        var myHtml = "<br>Sendernummer: " + number + "<br>Sendername: Testsender <br> Standort: Demostandort<br> <a href='<?php echo $url ?>ci/detail/ciid/"+number+"' target='_parent'>Detail</a>";
        map.openInfoWindowHtml(latlng, myHtml);
      });
      return marker;
	}

    // Add 5 markers to the map at random locations
    // Note that we don't add the secret message to the marker's instance data
    var bounds = map.getBounds();
    var southWest = bounds.getSouthWest();
    var northEast = bounds.getNorthEast();
    var lngSpan = northEast.lng() - southWest.lng();
    var latSpan = northEast.lat() - southWest.lat();



    function removeMarker() {

    	for(var i = 0; i < markers.length; i++) {
			map.removeOverlay(markers[i]);
			markers[i] = null;
    	}
    }
    
    removeMarker();
    map.clearOverlays();
    
    <?php 
    $counter = 0;
    foreach($list as $marker) { ?>

    var latlng = new GLatLng(<?php  echo $marker[1]; ?>, <?php  echo $marker[2]; ?>);
    map.addOverlay(createMarker(latlng, <?php  echo $marker[0]; ?>, <?php  echo $marker[4]; ?>));

 	// Create and add the circle
    markers[<?php echo $counter;?>]  = new CircleOverlay(latlng, <?php echo $marker[3]; ?>, '#336699', 1, 1, '#336699', 0.25);
	map.addOverlay(markers[<?php echo $counter;?>] );

   <?php 
    $counter++; } ?>
  } 
}
    </script>
    
    
    
      <body onload="initialize()" onunload="GUnload()" style="margin: 0px; top: 0px; left: 0px;">
    <div id="map_canvas" style="width: 700px; height: 350px"></div>
  </body>