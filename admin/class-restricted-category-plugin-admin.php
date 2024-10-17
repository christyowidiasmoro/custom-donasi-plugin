<?php

// Check if the class 'Restricted_Category_Plugin_Admin' does not already exist
if ( !class_exists( 'Restricted_Category_Plugin_Admin' ) ) {
    // Define the 'Restricted_Category_Plugin_Admin' class
    class Restricted_Category_Plugin_Admin {

        // Constructor method
        public function __construct() {
            // Constructor code here
        }

        // Method to run the admin plugin
        public function run() {
            // Hook for admin customizations
            add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
			
            // Hook to register settings
            add_action( 'admin_init', [ $this, 'register_settings' ] );

            // Hook to handle reset settings action
            add_action( 'admin_post_reset_plugin_settings', [ $this, 'reset_plugin_settings' ] );
        }

        // Method to enqueue admin panel styles and scripts
        public function enqueue_admin_scripts() {
            wp_enqueue_style( 'restricted-category-admin-style', plugin_dir_url( __FILE__ ) . '../assets/css/admin-style.css' );
            wp_enqueue_script( 'restricted-category-admin-script', plugin_dir_url( __FILE__ ) . '../assets/js/admin-script.js', [], false, true );
        }

		// Method to render the admin settings page
		public function category_plugin_admin_page() {
			?>
			<div class="wrap">
				<h1>Custom Product Rules</h1>
				<form method="post" action="options.php">
					<?php
					settings_fields( 'donasi-settings-group' );
					do_settings_sections( 'donasi-settings' );
					submit_button();
					?>
				</form>
				<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
					<input type="hidden" name="action" value="reset_plugin_settings">
					<?php wp_nonce_field('reset_plugin_settings_nonce', 'reset_plugin_settings_nonce_field'); ?>
					<button type="submit" class="button button-secondary">Reset Options</button>
				</form>
			</div>
			<?php
		}
      
		// Method to register settings
		public function register_settings() {
			// Register the new setting for restricted category groups
			register_setting( 'donasi-settings-group', 'restricted_category_groups' );
		
			// Add a section in the admin settings page
			add_settings_section(
				'donasi-settings-section',
				'Category Restrictions',
				function() {
					echo '<p>Do not allowed to mix product categories in the same group.</p>';
				},
				'donasi-settings'
			);

			// Add the settings field for restricted category groups
			add_settings_field(
				'restricted_category_groups',
				'Category Groups',
				[ $this, 'restricted_categories_callback' ],
				'donasi-settings',
				'donasi-settings-section'
			);
      	}

		// Callback method to render the restricted categories settings field
		public function restricted_categories_callback() {
			// Get the current restricted groups from the settings
			$restricted_groups = get_option( 'restricted_category_groups', [] );
			// Get all product categories
			$categories = get_terms( [
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			] );
		
			// Render the restricted category groups
			echo '<div id="restricted-category-groups">';
		
			if ( !empty( $restricted_groups ) ) {
				foreach ( $restricted_groups as $index => $group ) {
					echo '<div class="restricted-group" data-index="' . esc_attr( $index ) . '">';
					echo '<h4>Group ' . ( (int) $index + 1 ) . '</h4>';
					echo $this->render_category_group( $group, $categories, $index );
					echo '<button type="button" class="button delete-group">Delete Group</button>';
					echo '</div>';
				}
			}
		
			echo '</div>';
		
			// Button to add new groups
			echo '<button type="button" class="button" id="add-group">Add Group</button>';
			echo '<script>
				var groupIndex = ' . count($restricted_groups) . ';
				jQuery("#add-group").click(function() {
					groupIndex++;
					jQuery("#restricted-category-groups").append(`<div class="restricted-group" data-index="${groupIndex}"><h4>Group ${groupIndex}</h4>` + ' . json_encode($this->render_category_group([], $categories, 'new')) . ' + `<button type="button" class="button delete-group">Delete Group</button></div>`);
				});
				jQuery(document).on("click", ".delete-group", function() {
					jQuery(this).closest(".restricted-group").remove();
				});
			</script>';
		}

		// Method to render a single category group
		public function render_category_group( $group, $categories, $index ) {
			ob_start();
			foreach ( $categories as $category ) {
				?>
				<label>
					<input type="checkbox" name="restricted_category_groups[<?php echo esc_attr( $index ); ?>][]"
						value="<?php echo esc_attr( $category->slug ); ?>"
						<?php echo in_array( $category->slug, $group ) ? 'checked' : ''; ?>>
					<?php echo esc_html( $category->name ); ?>
				</label><br/>
				<?php
			}
			return ob_get_clean();
		}

        // Method to handle the reset action
        public function reset_plugin_settings() {
            // Check nonce for security
            if (!isset($_POST['reset_plugin_settings_nonce_field']) || !wp_verify_nonce($_POST['reset_plugin_settings_nonce_field'], 'reset_plugin_settings_nonce')) {
                wp_die('Security check failed');
            }

            // Reset the setting
            update_option('restricted_category_groups', []);

            // Redirect back to the settings page
            wp_redirect(admin_url('admin.php?page=donasi-settings'));
            exit;
        }
    
    }
}
