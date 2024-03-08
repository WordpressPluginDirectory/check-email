<?php namespace CheckEmail\Core\UI\Page;

use CheckEmail\Core\DB\Check_Email_Table_Manager;
use CheckEmail\Core\UI\list_table\Check_Email_Log_List_Table;

/**
 * Log List Page.
 */
class Check_Email_Log_List_Page extends Check_Email_BasePage {
	protected $log_list_table;
	const PAGE_SLUG = 'check-email-logs';
	const LOG_LIST_ACTION_NONCE_FIELD = 'check-email-log-list-nonce-field';
	const LOG_LIST_ACTION_NONCE = 'check-email-log-list-nonce';
    const CAPABILITY = 'manage_check_email';

	/**
	 * Setup hooks.
	 */
	public function load() {
		parent::load();

		add_filter( 'set-screen-option', array( $this, 'save_screen_options' ), 10, 3 );

		add_action( 'admin_enqueue_scripts', array( $this, 'load_view_logs_assets' ) );
	}
        
	public function register_page() {
                $option = get_option( 'check-email-log-core' );
                
                if ( is_array( $option ) && array_key_exists( 'enable_logs', $option ) && 'true' === strtolower( $option['enable_logs'] ) ) {             
                    $this->page = add_submenu_page(
                            Check_Email_Status_Page::PAGE_SLUG,
                            esc_html__( 'View Logs', 'check-email'),
                            esc_html__( 'View Logs', 'check-email'),
                            'manage_check_email',
                            self::PAGE_SLUG,
                            array( $this, 'render_page' )
                    );
                    
                    add_action( "load-{$this->page}", array( $this, 'load_page' ) );
                    do_action( 'check_email_load_log_list_page', $this->page );
                } 

	}

	public function render_page() {
		$check_email    = wpchill_check_email();
		$plugin_dir_url = plugin_dir_url( $check_email->get_plugin_file() );
		wp_enqueue_style( 'check-email-view-logs-css', $plugin_dir_url . 'assets/css/admin/view-logs.css', array( 'jquery-ui-css' ), $check_email->get_version() );
                $option = get_option( 'check-email-log-core' );
                if ( is_array( $option ) && array_key_exists( 'enable_logs', $option ) && 'true' === strtolower( $option['enable_logs'] ) ) {
                    add_thickbox();

                    $this->log_list_table->prepare_items();
                    ?>
                    <div class="wrap">
                            <h2><?php esc_html_e( 'Email Logs', 'check-email' ); ?></h2>
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

		$this->log_list_table = new Check_Email_Log_List_Table( $this );
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

	public function load_view_logs_assets( $hook ) {

		$check_email      = wpchill_check_email();
		$plugin_dir_url = plugin_dir_url( $check_email->get_plugin_file() );

		wp_register_style( 'jquery-ui-css', $plugin_dir_url . 'assets/vendor/jquery-ui/themes/base/jquery-ui.min.css', array(), '1.12.1' );

		wp_register_script( 'insertionQ', $plugin_dir_url . 'assets/vendor/insertion-query/insQ.min.js', array( 'jquery' ), '1.0.6', true );

		wp_enqueue_script( 'check-email-view-logs', $plugin_dir_url . 'assets/js/admin/view-logs.js', array( 'insertionQ', 'jquery-ui-core', 'jquery-ui-datepicker', 'jquery-ui-tooltip', 'jquery-ui-tabs' ), $check_email->get_version(), true );
	}
}
