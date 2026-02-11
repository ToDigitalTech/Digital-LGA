<?php
/**
 * Business registration form template.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="dlga-form-wrap">
    <h2><?php esc_html_e( 'Register Your Business', 'digital-lga' ); ?></h2>
    <p><?php esc_html_e( 'Join the Digital LGA platform to process payroll and participate in public tenders.', 'digital-lga' ); ?></p>

    <form method="post" enctype="multipart/form-data" class="dlga-form">
        <?php wp_nonce_field( 'dlga_register_business', 'dlga_register_business_nonce' ); ?>

        <fieldset>
            <legend><?php esc_html_e( 'Account Details', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label for="email"><?php esc_html_e( 'Email Address *', 'digital-lga' ); ?></label>
                <input type="email" name="email" id="email" required>
            </div>
            <div class="dlga-form-group">
                <label for="password"><?php esc_html_e( 'Password *', 'digital-lga' ); ?></label>
                <input type="password" name="password" id="password" required minlength="8">
            </div>
        </fieldset>

        <fieldset>
            <legend><?php esc_html_e( 'Company Information', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label for="company_name"><?php esc_html_e( 'Company Name *', 'digital-lga' ); ?></label>
                <input type="text" name="company_name" id="company_name" required>
            </div>
            <div class="dlga-form-row">
                <div class="dlga-form-group">
                    <label for="cac_number"><?php esc_html_e( 'CAC Registration Number *', 'digital-lga' ); ?></label>
                    <input type="text" name="cac_number" id="cac_number" required>
                </div>
                <div class="dlga-form-group">
                    <label for="tax_id"><?php esc_html_e( 'Tax ID *', 'digital-lga' ); ?></label>
                    <input type="text" name="tax_id" id="tax_id" required>
                </div>
            </div>
            <div class="dlga-form-group">
                <label for="industry_type"><?php esc_html_e( 'Industry Type', 'digital-lga' ); ?></label>
                <select name="industry_type" id="industry_type">
                    <option value=""><?php esc_html_e( 'Select...', 'digital-lga' ); ?></option>
                    <option value="construction"><?php esc_html_e( 'Construction', 'digital-lga' ); ?></option>
                    <option value="trading"><?php esc_html_e( 'Trading', 'digital-lga' ); ?></option>
                    <option value="services"><?php esc_html_e( 'Services', 'digital-lga' ); ?></option>
                    <option value="manufacturing"><?php esc_html_e( 'Manufacturing', 'digital-lga' ); ?></option>
                    <option value="technology"><?php esc_html_e( 'Technology', 'digital-lga' ); ?></option>
                    <option value="agriculture"><?php esc_html_e( 'Agriculture', 'digital-lga' ); ?></option>
                    <option value="other"><?php esc_html_e( 'Other', 'digital-lga' ); ?></option>
                </select>
            </div>
            <div class="dlga-form-group">
                <label for="company_address"><?php esc_html_e( 'Company Address *', 'digital-lga' ); ?></label>
                <textarea name="company_address" id="company_address" rows="3" required></textarea>
            </div>
            <div class="dlga-form-group">
                <label for="contact_phone"><?php esc_html_e( 'Contact Phone *', 'digital-lga' ); ?></label>
                <input type="tel" name="contact_phone" id="contact_phone" required>
            </div>
        </fieldset>

        <fieldset>
            <legend><?php esc_html_e( 'Business Type', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label>
                    <input type="checkbox" name="is_public_sector" value="1">
                    <?php esc_html_e( 'Public Sector Business (eligible for infrastructure tenders)', 'digital-lga' ); ?>
                </label>
                <small><?php esc_html_e( 'Leave unchecked for payroll-only access.', 'digital-lga' ); ?></small>
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
                    <option value="keystone">Keystone Bank</option>
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

        <fieldset>
            <legend><?php esc_html_e( 'Verification Documents', 'digital-lga' ); ?></legend>
            <div class="dlga-form-group">
                <label for="cac_certificate"><?php esc_html_e( 'CAC Certificate', 'digital-lga' ); ?></label>
                <input type="file" name="cac_certificate" id="cac_certificate" accept=".pdf,.jpg,.jpeg,.png">
            </div>
            <div class="dlga-form-group">
                <label for="tax_clearance"><?php esc_html_e( 'Tax Clearance Certificate', 'digital-lga' ); ?></label>
                <input type="file" name="tax_clearance" id="tax_clearance" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </fieldset>

        <button type="submit" class="dlga-btn dlga-btn-primary"><?php esc_html_e( 'Register Business', 'digital-lga' ); ?></button>
    </form>
</div>
