<?php
global $wpdb;
$sql = $wpdb->prepare("SELECT id, name FROM {$wpdb->prefix}bia_counties WHERE name NOT LIKE 'denver' ORDER BY name");
$counties = $wpdb->get_results($sql);

?>
<h1>Out of District Delivery</h1>
<label id="districts-label" for="districts">Choose a County:</label>

<select id="counties">
    <option disabled selected value> -- select an option -- </option>
<?php
foreach($counties as $county){
    echo "<option value='{$county->id}'>{$county->name}</option>";
}
?>
</select>
<div>
    <table class="widefat fixed bia-table" id="bia-people-table" style="display: none;" cellspacing="0">
    <thead>
        <tr>
            <td>Zip</td>
            <td>First</td>
            <td>Last</td>
            <td>Disabled?</td>
            <td>Num In House</td>
            <td>Scheduled Delivery Time</td>
            <td>Is Complete?</td>
            <td>View Details</td>
        </tr>
    </thead>
    <tbody></tbody>
    </table>
</div>

<div id="bia-details-modal" title="Request Details">    
</div>