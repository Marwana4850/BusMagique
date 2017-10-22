function myMap() {
    var myCenter = new google.maps.LatLng(48.85,2.35);
    var mapCanvas = document.getElementById("map");
    var mapOptions = {center: myCenter, zoom: 8};
    var map = new google.maps.Map(mapCanvas, mapOptions);
    var marker = new google.maps.Marker({position:myCenter});
    marker.setMap(map);

    // Zoom to 9 when clicking on marker
    google.maps.event.addListener(marker,'click',function() {
        map.setZoom(9);
        map.setCenter(marker.getPosition());
    });
}