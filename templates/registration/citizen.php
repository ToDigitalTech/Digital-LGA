<?php
/**
 * Citizen registration form template.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="dlga-form-wrap">
    <h2><?php esc_html_e( 'Citizen Registration', 'digital-lga' ); ?></h2>
    <p><?php esc_html_e( 'Register to participate in local governance, submit ideas, and verify projects.', 'digital-lga' ); ?></p>

    <form method="post" enctype="multipart/form-data" class="dlga-form">
        <?php wp_nonce_field( 'dlga_register_citizen', 'dlga_register_citizen_nonce' ); ?>

        <fieldset>
            <legend><?php esc_html_e( 'Personal Information', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label for="full_name"><?php esc_html_e( 'Full Name *', 'digital-lga' ); ?></label>
                <input type="text" name="full_name" id="full_name" required>
            </div>
            <div class="dlga-form-group">
                <label for="email"><?php esc_html_e( 'Email Address *', 'digital-lga' ); ?></label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="dlga-form-group">
                <label for="password"><?php esc_html_e( 'Password *', 'digital-lga' ); ?></label>
                <input type="password" name="password" id="password" required minlength="8">
            </div>
            <div class="dlga-form-group">
                <label for="phone"><?php esc_html_e( 'Phone Number *', 'digital-lga' ); ?></label>
                <input type="tel" name="phone" id="phone" required>
            </div>
            <div class="dlga-form-group">
                <label for="lga_residence"><?php esc_html_e( 'LGA of Residence *', 'digital-lga' ); ?></label>
                <input type="text" name="lga_residence" id="lga_residence" required
                       value="<?php echo esc_attr( DLGA_Settings::get( 'dlga_lga_name', '' ) ); ?>">
            </div>
        </fieldset>

        <fieldset>
            <legend><?php esc_html_e( 'Verification', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label for="id_type"><?php esc_html_e( 'Government ID Type *', 'digital-lga' ); ?></label>
                <select name="id_type" id="id_type" required>
                    <option value=""><?php esc_html_e( 'Select...', 'digital-lga' ); ?></option>
                    <option value="nin"><?php esc_html_e( 'National Identification Number (NIN)', 'digital-lga' ); ?></option>
                    <option value="voters_card"><?php esc_html_e( "Voter's Card", 'digital-lga' ); ?></option>
                    <option value="drivers_license"><?php esc_html_e( "Driver's License", 'digital-lga' ); ?></option>
                    <option value="intl_passport"><?php esc_html_e( 'International Passport', 'digital-lga' ); ?></option>
                </select>
            </div>
            <div class="dlga-form-group">
                <label for="id_number"><?php esc_html_e( 'ID Number *', 'digital-lga' ); ?></label>
                <input type="text" name="id_number" id="id_number" required>
            </div>
            <div class="dlga-form-group">
                <label for="id_photo"><?php esc_html_e( 'ID Photo Upload', 'digital-lga' ); ?></label>
                <input type="file" name="id_photo" id="id_photo" accept="image/*">
            </div>
        </fieldset>

        <button type="submit" class="dlga-btn dlga-btn-primary"><?php esc_html_e( 'Register', 'digital-lga' ); ?></button>
    </form>
</div>
