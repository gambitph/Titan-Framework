<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class TitanFrameworkOptionSave extends TitanFrameworkOption {

	public $defaultSecondarySettings = array(
		'save' => '',
		'confirm_save' => false,
		'confirm_save_string' => '',
		'reset' => '',
		'use_reset' => true,
		'confirm_reset' => true,
		'confirm_reset_string' => '',
		'action' => 'save',
	);

	public function display() {
		if ( !empty( $this->owner->postID ) ) {
			return;
		}

		if ( empty( $this->settings['save'] ) ) {
			$this->settings['save'] = __( 'Save Changes', TF_I18NDOMAIN );
		}
		if ( empty( $this->settings['reset'] ) ) {
			$this->settings['reset'] = __( 'Reset to Defaults', TF_I18NDOMAIN );
		}
		if ( empty( $this->settings['confirm_save_string'] ) ) {
			$this->settings['confirm_save_string'] = __( 'Save changes now?', TF_I18NDOMAIN );
		}
		if ( empty( $this->settings['confirm_reset_string'] ) ) {
			$this->settings['confirm_reset_string'] = __( 'Reset all fields to defaults now?', TF_I18NDOMAIN );
		}

		//Create modifications if confirmation settings are true.
		if ( $this->settings['confirm_save'] ) {
			$append_save = " onclick=\"javascript: return confirm_action( 'submit', '".$this->settings['confirm_save_string']."' ); \"";
		}
		else {
			$append_save = "";
		}
		if ( $this->settings['confirm_reset'] ) {
			$append_reset = " onclick=\"javascript: return confirm_action( 'reset', '".$this->settings['confirm_reset_string']."', false ); \"";
		}
		else {
			$append_reset = " onclick=\"javascript: jQuery( '#tf-reset-form' ).submit(); return false;\"";
		}		
		
		?>
		</tbody>
		</table>

		<p class='submit'>
			<button name="action" value="<?php echo $this->settings['action'] ?>" class="button button-primary" <?php echo $append_save ?>>
				<?php echo $this->settings['save'] ?>
			</button>

			<?php
			if ( $this->settings['use_reset'] ):
			?>
			<button name="action" class="button button-secondary" <?php echo $append_reset ?>>
				<?php echo $this->settings['reset'] ?>
			</button>
			<?php
			endif;
			?>
		</p>

		<table class='form-table'>
			<tbody>
		<?php
	}
}