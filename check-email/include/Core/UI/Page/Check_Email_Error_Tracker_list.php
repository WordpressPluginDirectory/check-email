<?php namespace CheckEmail\Core\UI\Page;

use CheckEmail\Core\UI\list_table\Check_Email_Error_Tracker;

/**
 * Log List Page.
 */
class Check_Email_Error_Tracker_list extends Check_Email_BasePage {
	protected $log_list_table;
	const PAGE_SLUG = 'check-email-error-tracker';
	const LOG_LIST_ACTION_NONCE_FIELD = 'check-email-log-list-nonce-field';
	const LOG_LIST_ACTION_NONCE = 'check-email-log-list-nonce';
    const CAPABILITY = 'manage_check_email';

	public function __construct() {
		$option = get_option( 'check-email-log-core' );
		if ( isset($_GET['_wpnonce']) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ck_error_tracker') ) {
			if( isset( $_GET['enable-error-tracker'] ) && sanitize_text_field( wp_unslash($_GET['enable-error-tracker'] ) ) && current_user_can('manage_options')) {
				$option['email_error_tracking'] = 'true';
				update_option('check-email-log-core',$option);
			}
		}
	}

	/**
	 * Setup hooks.
	 */
	public function load() {
		parent::load();

		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_error_tracker_assets' ) );
	}
        
	public function register_page() {
        $option = get_option( 'check-email-log-core' );
        if (!isset($option['email_error_tracking']) ||  $option['email_error_tracking'] ) {
            $this->page = add_submenu_page(
                    Check_Email_Status_Page::PAGE_SLUG,
                    esc_html__( 'Error Tracker', 'check-email'),
                    esc_html__( 'Error Tracker', 'check-email'),
                    'manage_check_email',
                    self::PAGE_SLUG,
                    array( $this, 'render_page' ),
                    2
            );
            add_action( "load-{$this->page}", array( $this, 'load_page' ) );
            do_action( 'check_email_load_log_list_page', $this->page );
        }

	}

	public function render_page() {
		$check_email    = wpchill_check_email();
		$plugin_dir_url = plugin_dir_url( $check_email->get_plugin_file() );
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		wp_enqueue_style( 'check-email-view-logs-css', $plugin_dir_url . 'assets/css/admin/view-logs'. $suffix .'.css', array( 'jquery-ui-css' ), $check_email->get_version() );
		wp_enqueue_style( 'check-email-export-logs-css', $plugin_dir_url . 'assets/css/admin/export-logs'. $suffix .'.css', array( 'jquery-ui-css' ), $check_email->get_version() );
                $option = get_option( 'check-email-log-core' );
                if ( is_array( $option ) && array_key_exists( 'email_error_tracking', $option ) && 'true' === strtolower( $option['email_error_tracking'] ) ) { 
                    add_thickbox();

                    $this->log_list_table->prepare_items();
                    ?>
                    <div class="wrap">
                            <h2><?php esc_html_e( 'Error Tracker', 'check-email' ); ?></h2>
                            <?php settings_errors(); ?>

                            <form id="email-logs-list" method="get">
                                    <input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE_SLUG ); ?>">
                                    <?php $this->log_list_table->search_box( esc_html__( 'Search Logs', 'check-email' ), 'search_id' ); ?>

                                    <?php
                                    // Disable the output of referrer hidden field, since it will be generated by the log list table.
                                    wp_nonce_field( self::LOG_LIST_ACTION_NONCE, self::LOG_LIST_ACTION_NONCE_FIELD, false );
                                    $this->log_list_table->display();
                                    ?>
                            </form>
                    </div>
		<?php
                }
	}

	public function load_page() {

		// Add screen options
		$this->get_screen()->add_option(
			'per_page',
			array(
				'label'   => esc_html__( 'Entries per page', 'check-email' ),
				'default' => 20,
				'option'  => 'per_page',
			)
		);

		$this->log_list_table = new Check_Email_Error_Tracker( $this );
	}

	public function get_per_page() {
		$screen = get_current_screen();
		$option = $screen->get_option( 'per_page', 'option' );

		$per_page = get_user_meta( get_current_user_id(), $option, true );

		if ( empty( $per_page ) || $per_page < 1 ) {
			$per_page = $screen->get_option( 'per_page', 'default' );
		}

		return $per_page;
	}

	public function get_nonce_args() {
		return array(
			self::LOG_LIST_ACTION_NONCE_FIELD => wp_create_nonce( self::LOG_LIST_ACTION_NONCE ),
		);
	}

	public function get_table_manager() {
		$check_email = wpchill_check_email();

		return $check_email->table_manager;
	}

	public function save_screen_options( $status, $option, $value ) {
		if ( 'per_page' == $option ) {
			return $value;
		} else {
			return $status;
		}
	}

	public function load_error_tracker_assets( $hook ) {
	}
}
