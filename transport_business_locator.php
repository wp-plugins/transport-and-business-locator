<?php
/* Plugin Name: Transport and Business Locator
 * Plugin URI: http://bobcares.com
* Description: This plugin will help the users to find the bus stations, stores, atm's etc within a particular radius of the searched location. Users will be able to configure the radius and the locators from the settings page.
* Version: 1.0.0
* Author: Bobcares <pm@bobcares.com>
* Author URI: http://bobcares.com
* License:
*/

/*
 * function to display contents in the webpage
* @param null
* @return display contents in a webpage
*/


if (!function_exists('writeLog')) {

	/**
	 * Function to add the plugin log to wordpress log file, added by BDT
	 * @param object $log
	 */
	function writeLog($log, $line = "",$file = "")  {

		if (WP_DEBUG === true) {

			$pluginLog = $log ." on line [" . $line . "] of [" . $file . "]\n";

			if ( is_array( $pluginLog ) || is_object( $pluginLog ) ) {
				print_r( $pluginLog, true );
			} else {
				error_log( $pluginLog );
			}

		}
	}

}


function locationDisplay() {

	//Setting default values for latitude, longitude and the locator type
	$address = "Dallas";
	$skeyword = "";

		
	// Handling the form post values for address and the locator type
	if (isset($_POST['search'])) {
		$address = sanitize_text_field(trim($_POST['address']));
		$skeyword = sanitize_text_field($_POST['skeyword']);		
		writeLog(" address ".$address." and locator type ".$skeyword." are posted", basename(__LINE__), basename(__FILE__));
	}
	
	?>
<!DOCTYPE html>
<html>
<head>
<title>Transport and Business Locator</title>
<meta charset="utf-8">

<!-- Style changes for the html elements -->
<style>
html,body,#map-canvas {
	height: 100%;
	margin: 0px;
	padding: 0px;
}

#map-canvas,#search,#download,#loading {
	border: 1px solid #FA9E24;
	padding: 10px;
	margin: 10px;
}

table {
	border-collapse: collapse;
	width: 98%;
}

table,th,td {
	border: 1px solid #FA9E24;
	padding: 3px;
	background-color: #DFDFDF;
}

#search,#download,#loading {
	background-color: #DFDFDF;
}

#place-canvas {
	height: 65px;
	overflow-y: auto;
	width: 99%;
}

#places {
	padding: 10px;
	margin: 10px;
}

#download {
	width: 150px;
}

#download a {
	color: black;
	text-decoration: none;
}

#map-canvas {
	height: 300px;
	width: 97%;
}

#fetch {
	margin-top: 16px;
}

.locator {
	margin-top: 16px;
}
</style>

<!-- JS section handling the map display  -->
<script
	src="https://maps.googleapis.com/maps/api/js?v=3.exp&libraries=places"></script>
<script src="<?php plugin_dir_url( __FILE__ ).'excellentexport.js'?>"></script>
<script src="<?php plugin_dir_url( __FILE__ ).'getaddresses.php'?>"></script>
<script>
			// Declaring the common variables
			var map;
			var infowindow;
			var geocoder;
			var address;

			/**
			 * Get the place information
			 * @param String address
			 * @returns NULL
			 */
			function getPlace(address) {
				geocoder.geocode({'address': address}, function(results, status) {
					if (status == google.maps.GeocoderStatus.OK) {
						markPlace(results[0].geometry.location);
						
					}
					
				});
				
	   
			}

			// Function initialising the map object and to display map on demand
			function initialize() {
				geocoder = new google.maps.Geocoder();
				getPlace('<?php echo $address;?>');
			}

			/**
			 * Mark the places in map
			 * @param Object address
			 * @returns NULL
			 */
			function markPlace(address) {
				
				// Map parameters
				var request = {
					location: address,
					radius: ['<?php echo get_option('radius');?>'],
					types: ['<?php echo $skeyword;?>']
				};

				// create an instance of the map
				map = new google.maps.Map(document.getElementById('map-canvas'), {center: address, zoom: 15});

				// Initialising the info window
				infowindow = new google.maps.InfoWindow();

				// Fetching the requested place details using google placeservice api
				var service = new google.maps.places.PlacesService(map);

				// call the API service to do radar search
				service.radarSearch(request, callback);
			}

			/**
			 * Call back function handling the request
			 * @param Array results
			 * @param String status
			 * @returns NULL
			 */
			function callback(results, status) {

				// Checks whether api returns success status		
				if (status == google.maps.places.PlacesServiceStatus.OK) {

					// loop through the results
					for (var i = 0; i < results.length; i++) {
						createMarker(results[i]);
					}

					// Continue only if there is data
					if (results.length > 0) {

						var limit = 1;
						var block = 10;
						var table = document.getElementById("places");

						// loop through results
						for (var offset = limit; offset < results.length; offset = limit) {

							limit += block;

							// set the limit
							if ((limit + 1) > results.length)
								limit = results.length;

							document.getElementById("loading").innerHTML = "Loading " + offset + " to " + limit + " of " + results.length + " addresses ...";

							var set = results.slice(offset, limit);
							var places = JSON.stringify(set);

							var xmlhttp = new XMLHttpRequest();
							
							// AJAX call to fetch the address list
							xmlhttp.onreadystatechange = function() {

								// proceed on sucess
								if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {

									var places = JSON.parse(xmlhttp.responseText);

									// loop through the results
									for (var i = 0; i < places.length; i++) {
										var row = table.insertRow(table.rows.length);
										var cell1 = row.insertCell(0);
										cell1.innerHTML = places[i];
									}

									// display download button when all data are fetched
									if (table.rows.length >= results.length) {
										document.getElementById("download").style = "display: block";
										document.getElementById("loading").style = "display: none";

									}
								}
							}
							xmlhttp.open("GET", "<?php plugin_dir_url( __FILE__ ).'getaddresses.php'?>?total=" + results.length + "&block=" + block + "&offset=" + offset + "&limit=" + limit + "&places=" + places, false);
							xmlhttp.send();
						}
					}
				}
			}

			/**
			 * Function displaying the marker for the requested place
			 * 
			 * @param Object place
			 * @returns NULL             
			 */
			function createMarker(place) {
				var placeLoc = place.geometry.location;
				var marker = new google.maps.Marker({
					map: map,
					position: place.geometry.location
				});

				google.maps.event.addListener(marker, 'click', function() {
					infowindow.setContent(place.name);
					infowindow.open(map, this);
				});
			}

			// Calls the initialize() to display the requested map on window load
			google.maps.event.addDomListener(window, 'load', initialize);

		</script>

</head>
<body style="background-color: #FCFCFC;">

	<!-- Search form -->
	<div id="search">
		<form id="search_form"
			action="<?php plugin_dir_url( __FILE__ ).'placelocator.php'?>"
			name="search_form" method="POST">

			<div class="place">

				<label>Place : </label><input type="text" name="address"
					value="<?php echo $address;?>">

			</div>

			<div class="locator">
				<label>Locator Type : </label>
				<!--<input type="text" name="skeyword" value="<?php echo $skeyword; ?>">-->

				<select id="select" name="skeyword">
					<option selected="selected">Choose an option</option>
					<?php $values = get_option('locations'); 

					foreach($values as $locate) {
								echo '<option value="'.$locate.'">'.$locate.'</option>';
							}?>

					<!--   <option value="atm">ATM</option>
							<option value="bus_station"> Bus Station</option>
							<option value="store">Store</option> -->

				</select>
			</div>

			<script>
						function myFunction() {
						document.getElementById("se").value = "$skeyword";
						}
					</script>

			<input id="fetch" type="submit" name="search" value="Fetch Places">
		</form>
	</div>

	<!-- <div id="loading">Loading....</div> -->

	<!-- Download button -->
	<div id="download" style="display: none;">
		<a download="data.xls" href="#"
			onclick="return ExcellentExport.excel(this, 'places', 'Places');">Export
			to Excel</a>
	</div>

	<!-- Displays the place list -->
	<div id="place-canvas">
		<table id="places">
			<tr>
				<th>Nearby <?php echo str_replace('_', ' ', $skeyword);?>
				</th>
			</tr>
		</table>
	</div>

	<!-- Displays the map -->
	<div id="map-canvas"></div>
</body>
</html>
<?php
}

//Adding a shortcode for displaying the location details
add_shortcode('locDisplay','locationDisplay');

add_action('admin_menu', 'placeLocator');

function placeLocator() {
	add_menu_page('place_locator', 'Transport & Business Locator', 'read', 'my-unique-identifier', 'placeLocatorMenu');
	//  add_submenu_page('place_locator', 'New', 'read', 'my-unique-identifier', 'my_plugin_function');
}

function placeLocatorMenu() {
?>

<html>
<style>
fieldset {
	margin-right: 0 auto;
	margin-left: 0 auto;
	margin-top: 10%;
	width: 20%;
	padding-left: 30%;
}

#multiSelect {
	width: 70%;
	margin-top: 20px;
	margin-bottom: 20px;
}

#radius {
	width: 70%;
	margin-top: 20px;
	margin-bottom: 20px;
	display: block;
}

#submit {
	width: 72%;
}

form {
	margin-top: 10px;
}
</style>
<br />
<form method="POST">

<?php

//Fetch the radius value
$radius = get_option('radius');

//Storing the details
$locations= get_option('locations');

?>

	<h2>Transport and Business Locator Options</h2>

	<h5>Please select the locator type and radius</h5>
	<table>
		<tr>
			<td>Locator Type :</td>
			<td><select multiple id="multiSelect" name="skeyword[]">

					<!-- Modified to identify the selected options and display it -->
					<!--<option selected="selected">Choose options</option>-->
					<option <?php if ((is_array($locations)) && (in_array("church", $locations))) echo "selected"; ?> value="church">Church</option>
					<option <?php if ((is_array($locations)) && (in_array("mosque", $locations))) echo "selected"; ?> value="mosque">Mosque</option>
					<option <?php if ((is_array($locations)) && (in_array("movie-theatre", $locations))) echo "selected"; ?> value="movie-theatre">Movie Theatre</option>
					<option <?php if ((is_array($locations)) && (in_array("atm", $locations))) echo "selected"; ?> value="atm">ATM</option>
					<option <?php if ((is_array($locations)) && (in_array("bank", $locations))) echo "selected"; ?> value="bank">Bank</option>
					<option <?php if ((is_array($locations)) &&  (in_array("store", $locations))) echo "selected"; ?> value="store">Store</option>
					<option <?php if ((is_array($locations)) &&  (in_array("pharmacy", $locations))) echo "selected"; ?> value="pharmacy">Pharmacy</option>
			</select>
			</td>
		</tr>

		<tr>
			
			<!-- Modified by Sreenath to dispaly the radius value entered by the user -->
			<td>Radius :</td>
			<td><input id="radius" type="text" name="radius" value = <?php echo $radius; ?>>
			</td>
		</tr>

		<tr>
			<td><input id="submit" type="submit" name="submitsettings"
				value="Submit">
			</td>
		</tr>
	</table>
</form>

<!-- 
<form>
<h2>Place Locator Options</h2>
<fieldset>
<label style="font-size:18px;">Locator Type : </label>

						<select multiple id="multiSelect" name="skeyword">
							<!--<option selected="selected">Choose options</option>-->
<!-- <option value="atm">ATM</option>
							<option value="bus_station"> Bus Station</option>
							<option value="store">Store</option>
						</select></br>
					   
 <label style="font-size:18px;">Radius : </label><input id="radius" type="text" name="radius"></br>
 <input id="submit" type="submit" name="submit" value="Submit">
</fieldset> 
</form>-->
<?php
}

if (isset($_REQUEST['submitsettings']))  {

	//Removing the already existing values for radius and location
	delete_option('radius');
	delete_option('locations');

	//Initializing
	$location  = '';

	//Sanitizng the values
	$radius = sanitize_text_field($_REQUEST['radius']);

	//Storing the radius value in options table
	add_option('radius', $radius);

	//Cannot use sanitize_text_field as the location list will be in serialized format
	$location = apply_filters( "sanitize_option_locations", $_REQUEST['skeyword'], 'locations' );

	//Storing the location list
	add_option('locations', $location);
}
