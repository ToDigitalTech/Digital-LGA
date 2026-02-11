<?php
/**
 * Submit job idea form template.
 *
 * @package DigitalLGA
 */

if ( ! defined( 'WPINC' ) ) {
    die;
}
?>
<div class="dlga-form-wrap">
    <h2><?php esc_html_e( 'Submit Infrastructure Idea', 'digital-lga' ); ?></h2>
    <p><?php esc_html_e( 'Tell us what infrastructure needs fixing or building in your area.', 'digital-lga' ); ?></p>

    <form method="post" enctype="multipart/form-data" class="dlga-form" id="dlga-job-idea-form">
        <?php wp_nonce_field( 'dlga_submit_idea', 'dlga_idea_nonce' ); ?>

        <div class="dlga-form-group">
            <label for="idea_title"><?php esc_html_e( 'Title *', 'digital-lga' ); ?></label>
            <input type="text" name="idea_title" id="idea_title" required
                   placeholder="<?php esc_attr_e( 'e.g., Pothole on Opebi Street', 'digital-lga' ); ?>">
        </div>

        <div class="dlga-form-group">
            <label for="idea_description"><?php esc_html_e( 'Description *', 'digital-lga' ); ?></label>
            <textarea name="idea_description" id="idea_description" rows="6" required
                      placeholder="<?php esc_attr_e( 'Describe the issue and why it needs fixing...', 'digital-lga' ); ?>"></textarea>
        </div>

        <div class="dlga-form-group">
            <label for="idea_location"><?php esc_html_e( 'Location *', 'digital-lga' ); ?></label>
            <input type="text" name="idea_location" id="idea_location" required
                   placeholder="<?php esc_attr_e( 'e.g., Opebi Street, near Shoprite', 'digital-lga' ); ?>">
        </div>

        <div class="dlga-form-group">
            <label for="idea_category"><?php esc_html_e( 'Category *', 'digital-lga' ); ?></label>
            <select name="idea_category" id="idea_category" required>
                <option value=""><?php esc_html_e( 'Select category...', 'digital-lga' ); ?></option>
                <option value="road"><?php esc_html_e( 'Road Maintenance', 'digital-lga' ); ?></option>
                <option value="lighting"><?php esc_html_e( 'Street Lighting', 'digital-lga' ); ?></option>
                <option value="drainage"><?php esc_html_e( 'Drainage Systems', 'digital-lga' ); ?></option>
                <option value="waste"><?php esc_html_e( 'Waste Management', 'digital-lga' ); ?></option>
                <option value="public-space"><?php esc_html_e( 'Public Spaces', 'digital-lga' ); ?></option>
                <option value="building"><?php esc_html_e( 'Public Buildings', 'digital-lga' ); ?></option>
                <option value="emergency"><?php esc_html_e( 'Emergency Infrastructure', 'digital-lga' ); ?></option>
                <option value="other"><?php esc_html_e( 'Other', 'digital-lga' ); ?></option>
            </select>
        </div>

        <div class="dlga-form-group">
            <label><?php esc_html_e( 'Urgency Level *', 'digital-lga' ); ?></label>
            <div class="dlga-radio-group">
                <label><input type="radio" name="urgency" value="low"> <?php esc_html_e( 'Low', 'digital-lga' ); ?></label>
                <label><input type="radio" name="urgency" value="medium" checked> <?php esc_html_e( 'Medium', 'digital-lga' ); ?></label>
                <label><input type="radio" name="urgency" value="high"> <?php esc_html_e( 'High', 'digital-lga' ); ?></label>
            </div>
        </div>

        <div class="dlga-form-group">
            <label for="idea_photos"><?php esc_html_e( 'Photos (up to 5)', 'digital-lga' ); ?></label>
            <input type="file" name="idea_photos[]" id="idea_photos" accept="image/*" multiple>
            <small><?php esc_html_e( 'Upload photos showing the issue', 'digital-lga' ); ?></small>
        </div>

        <div class="dlga-form-group">
            <label for="estimated_cost"><?php esc_html_e( 'Estimated Cost (optional)', 'digital-lga' ); ?></label>
            <input type="number" name="estimated_cost" id="estimated_cost" min="0" step="1000"
                   placeholder="<?php esc_attr_e( 'If you have an idea of the cost', 'digital-lga' ); ?>">
        </div>

        <button type="submit" class="dlga-btn dlga-btn-primary"><?php esc_html_e( 'Submit Idea', 'digital-lga' ); ?></button>
    </form>

    <div class="dlga-info-box">
        <h3><?php esc_html_e( 'What happens next?', 'digital-lga' ); ?></h3>
        <ol>
            <li><?php esc_html_e( 'The committee reviews your submission within 3 days', 'digital-lga' ); ?></li>
            <li><?php esc_html_e( 'If approved, it becomes an official tender on the job board', 'digital-lga' ); ?></li>
            <li><?php esc_html_e( 'Businesses bid on the project', 'digital-lga' ); ?></li>
            <li><?php esc_html_e( 'Community vets the bids publicly', 'digital-lga' ); ?></li>
            <li><?php esc_html_e( 'Committee selects the winner and work begins', 'digital-lga' ); ?></li>
            <li><?php esc_html_e( "You'll be notified at each step via email", 'digital-lga' ); ?></li>
        </ol>
    </div>
</div>
