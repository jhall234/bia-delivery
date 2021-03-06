<div class="bia-form">
    <form id="bia-form" action="<?php echo esc_url(admin_url( 'admin-ajax.php' )) ?>">
        <h3>Food Delivery Application</h3>

        <label for="bia-form-first-name">First Name</label>
        <input type="text" name="first_name" id="bia-form-first-name" placeholder="ex: John"/>

        <label for="bia-form-last-name">Last Name</label>
        <input type="text" id="bia-form-last-name" name="last_name" placeholder="ex: Doe"/>

        <label for="bia-form-date-of-birth">Date of Birth</label>
        <input id="bia-form-date-of-birth" name="date_of_birth" type="date">

        <label for="bia-form-phone">Phone Number</label>
        <input id="bia-form-phone" name="phone" type="tel" placeholder="ex: 303-123-1234">

        <label for="bia-form-email">Email</label>
        <input id="bia-form-email" name="email" type="email" placeholder="ex: example@example.com">

        <label for="bia-form-address">Street Address</label>
        <input type="text" id="bia-form-address" name="address" placeholder="ex: 1234 W Denver Rd">

        <label for="bia-form-zip">Zip Code</label>
        <input id="bia-form-zip" name="zip" type="text" pattern="[0-9]{5}" placeholder="ex: 80212">

        <label for="bia-form-num-in-house">Number In Household</label>
        <input type="text" name="num_in_house" id="bia-form-num-in-house" placeholder="ex: 3">
        <div>
            <label>Are You Over 65 or Disabled?</label>
            <div>
                <div class='bia-radio-inline bia-radio-yes'>
                    <input type="radio" name="is_disabled" id="bia-form-yes" value="1">
                    <label for="bia-form-yes">Yes</label>
                </div>
                <div class='bia-radio-inline bia-radio-no'>
                    <input type="radio" name="is_disabled" id="bia-form-no" value="0" checked>
                    <label for="bia-form-no">No</label>
                </div>
            </div>
        </div>

        <div>
            <label>Are you able to send & recieve texts</label>
            <div>
                <div class='bia-radio-inline bia-radio-yes'>
                    <input type="radio" name="is_able_to_text" id="bia-form-is-able-to-text-yes" value="1">
                    <label for="bia-form-is-able-to-text-yes">Yes</label>
                </div>
                <div class='bia-radio-inline bia-radio-no'>
                    <input type="radio" name="is_able_to_text" id="bia-form-is-able-to-text-no" value="0" checked>
                    <label for="bia-form-is-able-to-text-no">No</label>
                </div>
            </div>
        </div>

        <label for="bia-form-special-instructions">Special Delivery Instructions</label>
        <textarea id="bia-form-special-instructions" name="special_instructions" placeholder="ex: Please ring doorbell" ></textarea>



        <input class="button button-primary" id="bia-form-submit" type="submit" value="submit" />
        <?php wp_nonce_field('bia_delivery_nonce', 'bia_delivery_nonce') ?>
    </form>
</div>
