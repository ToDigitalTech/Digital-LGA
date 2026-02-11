<?php
/**
 * Civil servant registration form template.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}

$service_types = DLGA_Civil_Servant::get_service_types();
?>
<div class="dlga-form-wrap">
    <h2><?php esc_html_e( 'Civil Servant Registration', 'digital-lga' ); ?></h2>
    <p><?php esc_html_e( 'Register to receive monthly distributions from the community fund.', 'digital-lga' ); ?></p>

    <form method="post" enctype="multipart/form-data" class="dlga-form">
        <?php wp_nonce_field( 'dlga_register_civil_servant', 'dlga_register_civil_servant_nonce' ); ?>

        <fieldset>
            <legend><?php esc_html_e( 'Account Details', 'digital-lga' ); ?></legend>
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
                <label for="lga_service"><?php esc_html_e( 'LGA of Service *', 'digital-lga' ); ?></label>
                <input type="text" name="lga_service" id="lga_service" required
                       value="<?php echo esc_attr( DLGA_Settings::get( 'dlga_lga_name', '' ) ); ?>">
            </div>
        </fieldset>

        <fieldset>
            <legend><?php esc_html_e( 'Service Details', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label for="service_type"><?php esc_html_e( 'Service Type *', 'digital-lga' ); ?></label>
                <select name="service_type" id="service_type" required>
                    <option value=""><?php esc_html_e( 'Select...', 'digital-lga' ); ?></option>
                    <?php foreach ( $service_types as $type ) : ?>
                        <option value="<?php echo esc_attr( $type['slug'] ); ?>">
                            <?php echo esc_html( $type['name'] ); ?>
                            <?php if ( $type['pool_access'] ) : ?>
                                (<?php esc_html_e( 'Pool Eligible', 'digital-lga' ); ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="dlga-form-group">
                <label for="badge_number"><?php esc_html_e( 'Badge/ID Number *', 'digital-lga' ); ?></label>
                <input type="text" name="badge_number" id="badge_number" required>
            </div>
            <div class="dlga-form-group">
                <label for="station"><?php esc_html_e( 'Station/Unit *', 'digital-lga' ); ?></label>
                <input type="text" name="station" id="station" required>
            </div>
            <div class="dlga-form-group">
                <label for="years_of_service"><?php esc_html_e( 'Years of Service', 'digital-lga' ); ?></label>
                <input type="number" name="years_of_service" id="years_of_service" min="0" max="50">
            </div>
        </fieldset>

        <fieldset>
            <legend><?php esc_html_e( 'Verification Documents', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label for="government_id"><?php esc_html_e( 'Government ID', 'digital-lga' ); ?></label>
                <input type="file" name="government_id" id="government_id" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="dlga-form-group">
                <label for="badge_photo"><?php esc_html_e( 'Badge Photo', 'digital-lga' ); ?></label>
                <input type="file" name="badge_photo" id="badge_photo" accept="image/*">
            </div>
            <div class="dlga-form-group">
                <label for="employment_proof"><?php esc_html_e( 'Official Letter / Proof of Employment', 'digital-lga' ); ?></label>
                <input type="file" name="employment_proof" id="employment_proof" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </fieldset>

        <fieldset>
            <legend><?php esc_html_e( 'Banking Details', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label for="bank_name"><?php esc_html_e( 'Bank Name *', 'digital-lga' ); ?></label>
                <select name="bank_name" id="bank_name" required>
                    <option value=""><?php esc_html_e( 'Select Bank...', 'digital-lga' ); ?></option>
                    <option value="access">Access Bank</option>
                    <option value="gtbank">GTBank</option>
                    <option value="zenith">Zenith Bank</option>
                    <option value="uba">UBA</option>
                    <option value="first_bank">First Bank</option>
                    <option value="union">Union Bank</option>
                    <option value="sterling">Sterling Bank</option>
                    <option value="fidelity">Fidelity Bank</option>
                    <option value="polaris">Polaris Bank</option>
                    <option value="wema">Wema Bank</option>
                    <option value="stanbic">Stanbic IBTC</option>
                    <option value="ecobank">Ecobank</option>
                    <option value="fcmb">FCMB</option>
                    <option value="other"><?php esc_html_e( 'Other', 'digital-lga' ); ?></option>
                </select>
            </div>
            <div class="dlga-form-row">
                <div class="dlga-form-group">
                    <label for="account_number"><?php esc_html_e( 'Account Number *', 'digital-lga' ); ?></label>
                    <input type="text" name="account_number" id="account_number" required pattern="[0-9]{10}" maxlength="10">
                </div>
                <div class="dlga-form-group">
                    <label for="account_name"><?php esc_html_e( 'Account Name *', 'digital-lga' ); ?></label>
                    <input type="text" name="account_name" id="account_name" required>
                </div>
            </div>
        </fieldset>

        <button type="submit" class="dlga-btn dlga-btn-primary"><?php esc_html_e( 'Register', 'digital-lga' ); ?></button>
    </form>
</div>
