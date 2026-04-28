<?php
/**
 * Admin UI and handlers for SuperShows Trade Shows Directory.
 *
 * @package SuperShowsTradeShowsDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides admin page controls for trade show records.
 */
class SuperShows_TradeShows_Admin {

	/**
	 * Admin page slug.
	 */
	private const MENU_SLUG = 'supershows-tradeshows';

	/**
	 * Wires admin hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_post_supershows_tradeshows_create', array( __CLASS__, 'handle_create' ) );
		add_action( 'admin_post_supershows_tradeshows_update', array( __CLASS__, 'handle_update' ) );
	}

	/**
	 * Registers plugin admin pages.
	 *
	 * @return void
	 */
	public static function register_admin_menu(): void {
		add_menu_page(
			__( 'SuperShows Trade Shows', 'supershows-tradeshows-directory' ),
			__( 'SuperShows', 'supershows-tradeshows-directory' ),
			'manage_options',
			self::MENU_SLUG,
			array( __CLASS__, 'render_page' ),
			'dashicons-calendar-alt',
			56
		);
	}

	/**
	 * Loads admin CSS/JS only on the plugin page.
	 *
	 * @param string $hook_suffix Current admin hook.
	 *
	 * @return void
	 */
	public static function enqueue_assets( string $hook_suffix ): void {
		if ( 'toplevel_page_' . self::MENU_SLUG !== $hook_suffix ) {
			return;
		}

		wp_enqueue_style(
			'supershows-tradeshows-admin',
			SUPERSHOWS_TRADE_SHOWS_URL . 'assets/css/admin.css',
			array(),
			SUPERSHOWS_TRADE_SHOWS_VERSION
		);

		wp_enqueue_media();

		wp_enqueue_script(
			'supershows-tradeshows-admin',
			SUPERSHOWS_TRADE_SHOWS_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			SUPERSHOWS_TRADE_SHOWS_VERSION,
			true
		);
	}

	/**
	 * Renders the admin page.
	 *
	 * @return void
	 */
	public static function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'supershows-tradeshows-directory' ) );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'create';

		self::render_notices();
		?>
		<div class="wrap supershows-admin-wrap">
			<h1><?php esc_html_e( 'SuperShows Trade Shows', 'supershows-tradeshows-directory' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a href="<?php echo esc_url( self::admin_tab_url( 'create' ) ); ?>" class="nav-tab <?php echo ( 'create' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Create Trade Show', 'supershows-tradeshows-directory' ); ?>
				</a>
				<a href="<?php echo esc_url( self::admin_tab_url( 'edit' ) ); ?>" class="nav-tab <?php echo ( 'edit' === $active_tab ) ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Edit Trade Show', 'supershows-tradeshows-directory' ); ?>
				</a>
			</h2>
			<?php if ( 'edit' === $active_tab ) : ?>
				<?php self::render_edit_tab(); ?>
			<?php else : ?>
				<?php self::render_create_form(); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Handles create form submission.
	 *
	 * @return void
	 */
	public static function handle_create(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'supershows-tradeshows-directory' ) );
		}

		check_admin_referer( 'supershows_tradeshows_create' );

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( '' === $name ) {
			self::redirect_with_notice( 'error', __( 'Trade show name is required.', 'supershows-tradeshows-directory' ) );
		}

		$address_street  = isset( $_POST['address_street'] ) ? sanitize_text_field( wp_unslash( $_POST['address_street'] ) ) : '';
		$address_city    = isset( $_POST['address_city'] ) ? sanitize_text_field( wp_unslash( $_POST['address_city'] ) ) : '';
		$address_state   = isset( $_POST['address_state'] ) ? sanitize_text_field( wp_unslash( $_POST['address_state'] ) ) : '';
		$address_zip     = isset( $_POST['address_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['address_zip'] ) ) : '';
		$address_country = isset( $_POST['address_country'] ) ? sanitize_text_field( wp_unslash( $_POST['address_country'] ) ) : '';

		$start_datetime = self::sanitize_datetime( $_POST['start_datetime'] ?? '' );
		$end_datetime   = self::sanitize_datetime( $_POST['end_datetime'] ?? '' );
		$start_month    = null;
		$start_year     = null;

		if ( null !== $start_datetime ) {
			$start_timestamp = strtotime( $start_datetime . ' UTC' );
			if ( false !== $start_timestamp ) {
				$start_month = (int) gmdate( 'n', $start_timestamp );
				$start_year  = (int) gmdate( 'Y', $start_timestamp );
			}
		}

		$industries_csv = isset( $_POST['industries'] ) ? sanitize_text_field( wp_unslash( $_POST['industries'] ) ) : '';
		$industries     = self::parse_csv_values( $industries_csv );

		$logo_image_id        = self::sanitize_absint_or_null( $_POST['logo_wordpress_image_id'] ?? '' );
		$gallery_image_ids    = self::parse_csv_int_values( $_POST['gallery_image_ids'] ?? '' );
		$page_id              = null;
		$description          = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';

		$address_json = wp_json_encode(
			array(
				'street'  => $address_street,
				'city'    => $address_city,
				'state'   => $address_state,
				'zip'     => $address_zip,
				'country' => $address_country,
			)
		);

		$imagery_json = wp_json_encode(
			array(
				'logo_image_id'         => $logo_image_id,
				'gallery_image_ids'     => $gallery_image_ids,
			)
		);

		$dates_json = wp_json_encode(
			array(
				'start_datetime' => $start_datetime,
				'end_datetime'   => $end_datetime,
			)
		);

		global $wpdb;
		$table_name = SuperShows_TradeShows_Activator::table_name();

		$inserted = $wpdb->insert(
			$table_name,
			array(
				'name'                    => $name,
				'address_json'            => $address_json,
				'address_city'            => $address_city,
				'address_state'           => $address_state,
				'address_zip'             => $address_zip,
				'related_url'             => self::sanitize_url( $_POST['related_url'] ?? '' ),
				'contact_phone'           => isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '',
				'contact_email'           => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
				'facebook_url'            => self::sanitize_url( $_POST['facebook_url'] ?? '' ),
				'instagram_url'           => self::sanitize_url( $_POST['instagram_url'] ?? '' ),
				'linkedin_url'            => self::sanitize_url( $_POST['linkedin_url'] ?? '' ),
				'youtube_url'             => self::sanitize_url( $_POST['youtube_url'] ?? '' ),
				'imagery_json'            => $imagery_json,
				'logo_wordpress_image_id' => $logo_image_id,
				'page_id'                 => $page_id,
				'dates_json'              => $dates_json,
				'start_datetime'          => $start_datetime,
				'end_datetime'            => $end_datetime,
				'start_month'             => $start_month,
				'start_year'              => $start_year,
				'description'             => $description,
				'industries_json'         => wp_json_encode( $industries ),
				'industries_search'       => implode( ' ', $industries ),
			),
			array(
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( false === $inserted ) {
			self::redirect_with_notice( 'error', __( 'Unable to save trade show. Please try again.', 'supershows-tradeshows-directory' ) );
		}

		$trade_show_id = (int) $wpdb->insert_id;
		$page_id       = self::create_trade_show_page( $name, $start_year, $description );

		if ( null !== $page_id ) {
			$wpdb->update(
				$table_name,
				array(
					'page_id' => $page_id,
				),
				array(
					'id' => $trade_show_id,
				),
				array( '%d' ),
				array( '%d' )
			);

			self::redirect_with_notice( 'success', __( 'Trade show and linked WordPress page saved.', 'supershows-tradeshows-directory' ) );
		}

		self::redirect_with_notice( 'error', __( 'Trade show saved, but the WordPress page could not be created.', 'supershows-tradeshows-directory' ) );
	}

	/**
	 * Handles edit form submission for existing entries.
	 *
	 * @return void
	 */
	public static function handle_update(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'supershows-tradeshows-directory' ) );
		}

		$trade_show_id = isset( $_POST['trade_show_id'] ) ? absint( wp_unslash( $_POST['trade_show_id'] ) ) : 0;
		if ( $trade_show_id <= 0 ) {
			self::redirect_with_notice( 'error', __( 'Invalid trade show ID.', 'supershows-tradeshows-directory' ), 'edit' );
		}

		check_admin_referer( 'supershows_tradeshows_update_' . $trade_show_id );

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
		if ( '' === $name ) {
			self::redirect_with_notice( 'error', __( 'Trade show name is required.', 'supershows-tradeshows-directory' ), 'edit' );
		}

		$address_street  = isset( $_POST['address_street'] ) ? sanitize_text_field( wp_unslash( $_POST['address_street'] ) ) : '';
		$address_city    = isset( $_POST['address_city'] ) ? sanitize_text_field( wp_unslash( $_POST['address_city'] ) ) : '';
		$address_state   = isset( $_POST['address_state'] ) ? sanitize_text_field( wp_unslash( $_POST['address_state'] ) ) : '';
		$address_zip     = isset( $_POST['address_zip'] ) ? sanitize_text_field( wp_unslash( $_POST['address_zip'] ) ) : '';
		$address_country = isset( $_POST['address_country'] ) ? sanitize_text_field( wp_unslash( $_POST['address_country'] ) ) : '';
		$start_datetime  = self::sanitize_datetime( $_POST['start_datetime'] ?? '' );
		$end_datetime    = self::sanitize_datetime( $_POST['end_datetime'] ?? '' );
		$start_month     = null;
		$start_year      = null;

		if ( null !== $start_datetime ) {
			$start_timestamp = strtotime( $start_datetime . ' UTC' );
			if ( false !== $start_timestamp ) {
				$start_month = (int) gmdate( 'n', $start_timestamp );
				$start_year  = (int) gmdate( 'Y', $start_timestamp );
			}
		}

		$industries_csv = isset( $_POST['industries'] ) ? sanitize_text_field( wp_unslash( $_POST['industries'] ) ) : '';
		$industries     = self::parse_csv_values( $industries_csv );
		$logo_image_id  = self::sanitize_absint_or_null( $_POST['logo_wordpress_image_id'] ?? '' );
		$gallery_ids    = self::parse_csv_int_values( $_POST['gallery_image_ids'] ?? '' );
		$page_id        = self::sanitize_absint_or_null( $_POST['page_id'] ?? '' );
		$description    = isset( $_POST['description'] ) ? wp_kses_post( wp_unslash( $_POST['description'] ) ) : '';

		$address_json = wp_json_encode(
			array(
				'street'  => $address_street,
				'city'    => $address_city,
				'state'   => $address_state,
				'zip'     => $address_zip,
				'country' => $address_country,
			)
		);

		$imagery_json = wp_json_encode(
			array(
				'logo_image_id'     => $logo_image_id,
				'gallery_image_ids' => $gallery_ids,
			)
		);

		$dates_json = wp_json_encode(
			array(
				'start_datetime' => $start_datetime,
				'end_datetime'   => $end_datetime,
			)
		);

		global $wpdb;
		$table_name = SuperShows_TradeShows_Activator::table_name();
		$updated    = $wpdb->update(
			$table_name,
			array(
				'name'                    => $name,
				'address_json'            => $address_json,
				'address_city'            => $address_city,
				'address_state'           => $address_state,
				'address_zip'             => $address_zip,
				'related_url'             => self::sanitize_url( $_POST['related_url'] ?? '' ),
				'contact_phone'           => isset( $_POST['contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['contact_phone'] ) ) : '',
				'contact_email'           => isset( $_POST['contact_email'] ) ? sanitize_email( wp_unslash( $_POST['contact_email'] ) ) : '',
				'facebook_url'            => self::sanitize_url( $_POST['facebook_url'] ?? '' ),
				'instagram_url'           => self::sanitize_url( $_POST['instagram_url'] ?? '' ),
				'linkedin_url'            => self::sanitize_url( $_POST['linkedin_url'] ?? '' ),
				'youtube_url'             => self::sanitize_url( $_POST['youtube_url'] ?? '' ),
				'imagery_json'            => $imagery_json,
				'logo_wordpress_image_id' => $logo_image_id,
				'page_id'                 => $page_id,
				'dates_json'              => $dates_json,
				'start_datetime'          => $start_datetime,
				'end_datetime'            => $end_datetime,
				'start_month'             => $start_month,
				'start_year'              => $start_year,
				'description'             => $description,
				'industries_json'         => wp_json_encode( $industries ),
				'industries_search'       => implode( ' ', $industries ),
			),
			array(
				'id' => $trade_show_id,
			),
			array(
				'%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s',
				'%s', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s',
			),
			array( '%d' )
		);

		if ( false === $updated ) {
			self::redirect_with_notice( 'error', __( 'Unable to update trade show.', 'supershows-tradeshows-directory' ), 'edit' );
		}

		if ( null !== $page_id && $page_id > 0 ) {
			$year       = $start_year ?? (int) gmdate( 'Y' );
			$page_title = trim( sprintf( '%s %d', $name, $year ) );
			wp_update_post(
				array(
					'ID'           => $page_id,
					'post_title'   => $page_title,
					'post_name'    => sanitize_title( $page_title ),
					'post_content' => $description,
				)
			);
		}

		self::redirect_with_notice( 'success', __( 'Trade show changes saved.', 'supershows-tradeshows-directory' ), 'edit' );
	}

	/**
	 * Creates a WordPress page for the trade show.
	 *
	 * @param string   $name        Trade show name.
	 * @param int|null $start_year  Trade show start year.
	 * @param string   $description Trade show description content.
	 *
	 * @return int|null
	 */
	private static function create_trade_show_page( string $name, ?int $start_year, string $description ): ?int {
		$year       = $start_year ?? (int) gmdate( 'Y' );
		$page_title = trim( sprintf( '%s %d', $name, $year ) );
		$page_slug  = sanitize_title( $page_title );

		$page_id = wp_insert_post(
			array(
				'post_type'    => 'page',
				'post_title'   => $page_title,
				'post_name'    => $page_slug,
				'post_status'  => 'publish',
				'post_content' => $description,
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return null;
		}

		return (int) $page_id;
	}

	/**
	 * Renders create form tab.
	 *
	 * @return void
	 */
	private static function render_create_form(): void {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="supershows-form">
			<input type="hidden" name="action" value="supershows_tradeshows_create" />
			<?php wp_nonce_field( 'supershows_tradeshows_create' ); ?>

				<div class="supershows-card">
					<h3><?php esc_html_e( 'Trade Show Basics', 'supershows-tradeshows-directory' ); ?></h3>
					<div class="supershows-grid supershows-grid-4">
						<?php self::render_text_field( 'name', __( 'Trade Show Name', 'supershows-tradeshows-directory' ), '', true ); ?>
						<?php self::render_text_field( 'industries', __( 'Industries (comma separated)', 'supershows-tradeshows-directory' ) ); ?>
						<?php self::render_url_field( 'related_url', __( 'Website URL', 'supershows-tradeshows-directory' ) ); ?>
					</div>
				</div>

				<div class="supershows-card">
					<h3><?php esc_html_e( 'Logos & Imagery', 'supershows-tradeshows-directory' ); ?></h3>
					<div class="supershows-grid supershows-grid-3">
						<?php self::render_media_field( 'logo_wordpress_image_id', __( 'Logo Image (WordPress Media)', 'supershows-tradeshows-directory' ), false ); ?>
						<?php self::render_media_field( 'gallery_image_ids', __( 'Gallery Images', 'supershows-tradeshows-directory' ), true ); ?>
					</div>
				</div>

			<div class="supershows-card">
				<h3><?php esc_html_e( 'Contact & Web Presence', 'supershows-tradeshows-directory' ); ?></h3>
				<div class="supershows-grid supershows-grid-4">
					<?php self::render_text_field( 'contact_phone', __( 'Phone Number', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_email_field( 'contact_email', __( 'Email', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_url_field( 'facebook_url', __( 'Facebook URL', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_url_field( 'instagram_url', __( 'Instagram URL', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_url_field( 'linkedin_url', __( 'LinkedIn URL', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_url_field( 'youtube_url', __( 'YouTube URL', 'supershows-tradeshows-directory' ) ); ?>
				</div>
			</div>

			<div class="supershows-card">
				<h3><?php esc_html_e( 'Location & Dates', 'supershows-tradeshows-directory' ); ?></h3>
				<div class="supershows-grid supershows-grid-5">
					<?php self::render_text_field( 'address_street', __( 'Street Address', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_text_field( 'address_city', __( 'City', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_text_field( 'address_state', __( 'State', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_text_field( 'address_zip', __( 'Zip Code', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_text_field( 'address_country', __( 'Country', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_datetime_field( 'start_datetime', __( 'Start Date/Time', 'supershows-tradeshows-directory' ) ); ?>
					<?php self::render_datetime_field( 'end_datetime', __( 'End Date/Time', 'supershows-tradeshows-directory' ) ); ?>
				</div>
			</div>

			<div class="supershows-card">
				<h3><?php esc_html_e( 'Description', 'supershows-tradeshows-directory' ); ?></h3>
				<label for="supershows-description" class="supershows-label"><?php esc_html_e( 'Trade Show Description', 'supershows-tradeshows-directory' ); ?></label>
				<?php
				wp_editor(
					'',
					'supershows-description',
					array(
						'textarea_name' => 'description',
						'textarea_rows' => 10,
						'media_buttons' => true,
					)
				);
				?>
			</div>

			<?php submit_button( __( 'Save Trade Show', 'supershows-tradeshows-directory' ) ); ?>
		</form>
		<?php
	}

	/**
	 * Renders edit tab with accordion rows for existing entries.
	 *
	 * @return void
	 */
	private static function render_edit_tab(): void {
		$trade_shows = self::get_trade_shows();
		?>
		<div class="supershows-edit-wrap">
			<h2><?php esc_html_e( 'Edit Trade Show', 'supershows-tradeshows-directory' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Review saved trade shows and expand an entry to inspect all stored fields.', 'supershows-tradeshows-directory' ); ?></p>

			<?php if ( empty( $trade_shows ) ) : ?>
				<div class="notice notice-info inline">
					<p><?php esc_html_e( 'No trade shows found yet. Add one from the Create Trade Show tab.', 'supershows-tradeshows-directory' ); ?></p>
				</div>
			<?php else : ?>
				<div class="supershows-accordion-list">
					<div class="supershows-accordion-head supershows-accordion-row">
						<div><?php esc_html_e( 'Trade Show Name', 'supershows-tradeshows-directory' ); ?></div>
						<div><?php esc_html_e( 'Industries', 'supershows-tradeshows-directory' ); ?></div>
						<div><?php esc_html_e( 'Year', 'supershows-tradeshows-directory' ); ?></div>
						<div><?php esc_html_e( 'Website', 'supershows-tradeshows-directory' ); ?></div>
						<div><?php esc_html_e( 'Actions', 'supershows-tradeshows-directory' ); ?></div>
					</div>
					<?php foreach ( $trade_shows as $trade_show ) : ?>
						<?php self::render_edit_accordion_row( $trade_show ); ?>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Renders one accordion row for a saved trade show.
	 *
	 * @param object $trade_show Trade show row.
	 *
	 * @return void
	 */
	private static function render_edit_accordion_row( object $trade_show ): void {
		$address_data  = self::decode_json_assoc( $trade_show->address_json ?? '' );
		$imagery_data  = self::decode_json_assoc( $trade_show->imagery_json ?? '' );
		$gallery_ids   = self::normalize_int_array( $imagery_data['gallery_image_ids'] ?? array() );
		$industries    = self::decode_json_list( $trade_show->industries_json ?? '' );
		$section_id    = 'supershows-entry-' . (int) $trade_show->id;
		$logo_image_id = absint( $trade_show->logo_wordpress_image_id ?? 0 );
		?>
		<div class="supershows-accordion-item">
			<div class="supershows-accordion-row">
				<div><?php echo esc_html( $trade_show->name ?? '' ); ?></div>
				<div><?php echo esc_html( implode( ', ', $industries ) ); ?></div>
				<div><?php echo esc_html( (string) ( $trade_show->start_year ?? '' ) ); ?></div>
				<div>
					<?php if ( ! empty( $trade_show->related_url ) ) : ?>
						<a href="<?php echo esc_url( $trade_show->related_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $trade_show->related_url ); ?></a>
					<?php endif; ?>
				</div>
				<div class="supershows-actions-cell">
					<button type="button" class="button-link supershows-accordion-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $section_id ); ?>">
						<?php esc_html_e( 'Edit', 'supershows-tradeshows-directory' ); ?>
						<span class="dashicons dashicons-arrow-down-alt2" aria-hidden="true"></span>
					</button>
				</div>
			</div>

			<div id="<?php echo esc_attr( $section_id ); ?>" class="supershows-accordion-panel" hidden>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="supershows-form">
					<input type="hidden" name="action" value="supershows_tradeshows_update" />
					<input type="hidden" name="trade_show_id" value="<?php echo esc_attr( (string) $trade_show->id ); ?>" />
					<?php wp_nonce_field( 'supershows_tradeshows_update_' . (int) $trade_show->id ); ?>

					<div class="supershows-card">
						<h3><?php esc_html_e( 'Trade Show Basics', 'supershows-tradeshows-directory' ); ?></h3>
						<div class="supershows-grid supershows-grid-4">
							<?php self::render_edit_text_field( 'name', __( 'Trade Show Name', 'supershows-tradeshows-directory' ), (string) ( $trade_show->name ?? '' ), (int) $trade_show->id, true ); ?>
							<?php self::render_edit_text_field( 'industries', __( 'Industries', 'supershows-tradeshows-directory' ), implode( ', ', $industries ), (int) $trade_show->id ); ?>
							<?php self::render_edit_url_field( 'related_url', __( 'Website URL', 'supershows-tradeshows-directory' ), (string) ( $trade_show->related_url ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_text_field( 'page_id', __( 'Linked Page ID', 'supershows-tradeshows-directory' ), (string) ( $trade_show->page_id ?? '' ), (int) $trade_show->id ); ?>
						</div>
					</div>

					<div class="supershows-card">
						<h3><?php esc_html_e( 'Logos & Imagery', 'supershows-tradeshows-directory' ); ?></h3>
						<div class="supershows-grid supershows-grid-3">
							<?php self::render_edit_media_field( 'logo_wordpress_image_id', __( 'Logo Image ID', 'supershows-tradeshows-directory' ), (string) $logo_image_id, (int) $trade_show->id, false ); ?>
							<?php self::render_edit_media_field( 'gallery_image_ids', __( 'Gallery Image IDs', 'supershows-tradeshows-directory' ), implode( ',', $gallery_ids ), (int) $trade_show->id, true ); ?>
						</div>
					</div>

					<div class="supershows-card">
						<h3><?php esc_html_e( 'Contact & Web Presence', 'supershows-tradeshows-directory' ); ?></h3>
						<div class="supershows-grid supershows-grid-4">
							<?php self::render_edit_text_field( 'contact_phone', __( 'Phone Number', 'supershows-tradeshows-directory' ), (string) ( $trade_show->contact_phone ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_email_field( 'contact_email', __( 'Email', 'supershows-tradeshows-directory' ), (string) ( $trade_show->contact_email ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_url_field( 'facebook_url', __( 'Facebook URL', 'supershows-tradeshows-directory' ), (string) ( $trade_show->facebook_url ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_url_field( 'instagram_url', __( 'Instagram URL', 'supershows-tradeshows-directory' ), (string) ( $trade_show->instagram_url ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_url_field( 'linkedin_url', __( 'LinkedIn URL', 'supershows-tradeshows-directory' ), (string) ( $trade_show->linkedin_url ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_url_field( 'youtube_url', __( 'YouTube URL', 'supershows-tradeshows-directory' ), (string) ( $trade_show->youtube_url ?? '' ), (int) $trade_show->id ); ?>
						</div>
					</div>

					<div class="supershows-card">
						<h3><?php esc_html_e( 'Location & Dates', 'supershows-tradeshows-directory' ); ?></h3>
						<div class="supershows-grid supershows-grid-5">
							<?php self::render_edit_text_field( 'address_street', __( 'Street Address', 'supershows-tradeshows-directory' ), (string) ( $address_data['street'] ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_text_field( 'address_city', __( 'City', 'supershows-tradeshows-directory' ), (string) ( $trade_show->address_city ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_text_field( 'address_state', __( 'State', 'supershows-tradeshows-directory' ), (string) ( $trade_show->address_state ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_text_field( 'address_zip', __( 'Zip Code', 'supershows-tradeshows-directory' ), (string) ( $trade_show->address_zip ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_text_field( 'address_country', __( 'Country', 'supershows-tradeshows-directory' ), (string) ( $address_data['country'] ?? '' ), (int) $trade_show->id ); ?>
							<?php self::render_edit_datetime_field( 'start_datetime', __( 'Start Date/Time', 'supershows-tradeshows-directory' ), self::to_datetime_local_value( (string) ( $trade_show->start_datetime ?? '' ) ), (int) $trade_show->id ); ?>
							<?php self::render_edit_datetime_field( 'end_datetime', __( 'End Date/Time', 'supershows-tradeshows-directory' ), self::to_datetime_local_value( (string) ( $trade_show->end_datetime ?? '' ) ), (int) $trade_show->id ); ?>
						</div>
					</div>

					<div class="supershows-card">
						<h3><?php esc_html_e( 'Description', 'supershows-tradeshows-directory' ); ?></h3>
						<div>
							<label class="supershows-label" for="supershows-<?php echo esc_attr( (string) $trade_show->id ); ?>-description"><?php esc_html_e( 'Trade Show Description', 'supershows-tradeshows-directory' ); ?></label>
							<textarea id="supershows-<?php echo esc_attr( (string) $trade_show->id ); ?>-description" name="description" class="supershows-textarea" rows="8"><?php echo esc_textarea( wp_strip_all_tags( (string) ( $trade_show->description ?? '' ) ) ); ?></textarea>
						</div>
					</div>

					<?php submit_button( __( 'Save Changes', 'supershows-tradeshows-directory' ), 'primary', 'submit', false ); ?>
				</form>
			</div>
		</div>
		<?php
	}

	/**
	 * Loads trade show rows ordered by newest first.
	 *
	 * @return object[]
	 */
	private static function get_trade_shows(): array {
		global $wpdb;

		$table_name = SuperShows_TradeShows_Activator::table_name();
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results( "SELECT * FROM {$table_name} ORDER BY id DESC" );
		// phpcs:enable

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Decodes JSON string into associative array.
	 *
	 * @param mixed $json JSON string.
	 *
	 * @return array<string, mixed>
	 */
	private static function decode_json_assoc( $json ): array {
		if ( ! is_string( $json ) || '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );
		return is_array( $decoded ) ? $decoded : array();
	}

	/**
	 * Decodes JSON list into string array.
	 *
	 * @param mixed $json JSON string.
	 *
	 * @return string[]
	 */
	private static function decode_json_list( $json ): array {
		if ( ! is_string( $json ) || '' === $json ) {
			return array();
		}

		$decoded = json_decode( $json, true );
		if ( ! is_array( $decoded ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map(
					static fn( $value ) => is_scalar( $value ) ? (string) $value : '',
					$decoded
				),
				static fn( string $value ) => '' !== $value
			)
		);
	}

	/**
	 * Normalizes mixed values to unique positive integer array.
	 *
	 * @param mixed $values Potential list values.
	 *
	 * @return int[]
	 */
	private static function normalize_int_array( $values ): array {
		if ( ! is_array( $values ) ) {
			return array();
		}

		$ints = array_filter(
			array_map( 'absint', $values ),
			static fn( int $value ) => $value > 0
		);

		return array_values( array_unique( $ints ) );
	}

	/**
	 * Renders status notices from query args.
	 *
	 * @return void
	 */
	private static function render_notices(): void {
		$notice = isset( $_GET['supershows_notice'] ) ? sanitize_key( wp_unslash( $_GET['supershows_notice'] ) ) : '';
		if ( '' === $notice ) {
			return;
		}

		$message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
		$class   = ( 'success' === $notice ) ? 'notice notice-success is-dismissible' : 'notice notice-error is-dismissible';
		?>
		<div class="<?php echo esc_attr( $class ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php
	}

	/**
	 * Redirects back to create tab with notice values.
	 *
	 * @param string $type    Notice type.
	 * @param string $message Notice message.
	 * @param string $tab     Tab slug.
	 *
	 * @return void
	 */
	private static function redirect_with_notice( string $type, string $message, string $tab = 'create' ): void {
		$url = add_query_arg(
			array(
				'page'              => self::MENU_SLUG,
				'tab'               => $tab,
				'supershows_notice' => $type,
				'message'           => $message,
			),
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Builds admin tab URL.
	 *
	 * @param string $tab Tab slug.
	 *
	 * @return string
	 */
	private static function admin_tab_url( string $tab ): string {
		return add_query_arg(
			array(
				'page' => self::MENU_SLUG,
				'tab'  => $tab,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Sanitizes URL form values.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	private static function sanitize_url( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		return esc_url_raw( wp_unslash( $value ) );
	}

	/**
	 * Converts scalar value to nullable absint.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return int|null
	 */
	private static function sanitize_absint_or_null( $value ): ?int {
		if ( ! is_scalar( $value ) ) {
			return null;
		}

		$absint = absint( wp_unslash( (string) $value ) );
		return ( $absint > 0 ) ? $absint : null;
	}

	/**
	 * Sanitizes HTML5 datetime-local input.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string|null
	 */
	private static function sanitize_datetime( $value ): ?string {
		if ( ! is_string( $value ) ) {
			return null;
		}

		$clean = preg_replace( '/[^0-9T:\-]/', '', wp_unslash( $value ) );
		if ( ! is_string( $clean ) || '' === $clean ) {
			return null;
		}

		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $clean ) ) {
			return null;
		}

		$datetime = DateTime::createFromFormat( 'Y-m-d\TH:i', $clean, new DateTimeZone( 'UTC' ) );
		if ( false === $datetime ) {
			return null;
		}

		return $datetime->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Parses comma-separated values into normalized list.
	 *
	 * @param string $csv Csv string.
	 *
	 * @return string[]
	 */
	private static function parse_csv_values( string $csv ): array {
		$values = array_filter(
			array_map(
				'sanitize_text_field',
				array_map( 'trim', explode( ',', $csv ) )
			),
			static fn( $value ) => '' !== $value
		);

		return array_values( array_unique( $values ) );
	}

	/**
	 * Parses comma-separated integer values.
	 *
	 * @param mixed $csv Csv string.
	 *
	 * @return int[]
	 */
	private static function parse_csv_int_values( $csv ): array {
		if ( ! is_scalar( $csv ) ) {
			return array();
		}

		$values = array_map( 'trim', explode( ',', wp_unslash( (string) $csv ) ) );
		$ints   = array_filter(
			array_map( 'absint', $values ),
			static fn( $value ) => $value > 0
		);

		return array_values( array_unique( $ints ) );
	}

	/**
	 * Renders text input field.
	 *
	 * @param string $name     Field name.
	 * @param string $label    Field label.
	 * @param string $value    Current value.
	 * @param bool   $required Required flag.
	 *
	 * @return void
	 */
	private static function render_text_field( string $name, string $label, string $value = '', bool $required = false ): void {
		?>
		<div>
			<label class="supershows-label" for="supershows-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="text" id="supershows-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" value="<?php echo esc_attr( $value ); ?>" <?php echo $required ? 'required' : ''; ?> />
		</div>
		<?php
	}

	/**
	 * Renders editable text input used in edit accordions.
	 *
	 * @param string $name      Field name.
	 * @param string $label     Label.
	 * @param string $value     Value.
	 * @param int    $record_id Record ID.
	 * @param bool   $required  Required flag.
	 *
	 * @return void
	 */
	private static function render_edit_text_field( string $name, string $label, string $value, int $record_id, bool $required = false ): void {
		$field_id = 'supershows-' . $record_id . '-' . $name;
		?>
		<div>
			<label class="supershows-label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" value="<?php echo esc_attr( $value ); ?>" <?php echo $required ? 'required' : ''; ?> />
		</div>
		<?php
	}

	/**
	 * Renders editable URL input used in edit accordions.
	 *
	 * @param string $name      Field name.
	 * @param string $label     Label.
	 * @param string $value     Value.
	 * @param int    $record_id Record ID.
	 *
	 * @return void
	 */
	private static function render_edit_url_field( string $name, string $label, string $value, int $record_id ): void {
		$field_id = 'supershows-' . $record_id . '-' . $name;
		?>
		<div>
			<label class="supershows-label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="url" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" value="<?php echo esc_attr( $value ); ?>" />
		</div>
		<?php
	}

	/**
	 * Renders editable email input used in edit accordions.
	 *
	 * @param string $name      Field name.
	 * @param string $label     Label.
	 * @param string $value     Value.
	 * @param int    $record_id Record ID.
	 *
	 * @return void
	 */
	private static function render_edit_email_field( string $name, string $label, string $value, int $record_id ): void {
		$field_id = 'supershows-' . $record_id . '-' . $name;
		?>
		<div>
			<label class="supershows-label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="email" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" value="<?php echo esc_attr( $value ); ?>" />
		</div>
		<?php
	}

	/**
	 * Renders editable datetime-local input used in edit accordions.
	 *
	 * @param string $name      Field name.
	 * @param string $label     Label.
	 * @param string $value     Value.
	 * @param int    $record_id Record ID.
	 *
	 * @return void
	 */
	private static function render_edit_datetime_field( string $name, string $label, string $value, int $record_id ): void {
		$field_id = 'supershows-' . $record_id . '-' . $name;
		?>
		<div>
			<label class="supershows-label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="datetime-local" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" value="<?php echo esc_attr( $value ); ?>" />
		</div>
		<?php
	}

	/**
	 * Renders editable media selector used in edit accordions.
	 *
	 * @param string $name      Field name.
	 * @param string $label     Label.
	 * @param string $value     Value.
	 * @param int    $record_id Record ID.
	 * @param bool   $multiple  Multiple select flag.
	 *
	 * @return void
	 */
	private static function render_edit_media_field( string $name, string $label, string $value, int $record_id, bool $multiple ): void {
		$field_id = 'supershows-' . $record_id . '-' . $name;
		?>
		<div>
			<label class="supershows-label" for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="supershows-media-wrap">
				<input type="text" id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" value="<?php echo esc_attr( $value ); ?>" />
				<button type="button" class="button supershows-media-button" data-target="<?php echo esc_attr( $field_id ); ?>" data-multiple="<?php echo $multiple ? '1' : '0'; ?>">
					<?php echo $multiple ? esc_html__( 'Select Images', 'supershows-tradeshows-directory' ) : esc_html__( 'Select Image', 'supershows-tradeshows-directory' ); ?>
				</button>
			</div>
		</div>
		<?php
	}

	/**
	 * Converts mysql datetime value into datetime-local input value.
	 *
	 * @param string $value Mysql datetime.
	 *
	 * @return string
	 */
	private static function to_datetime_local_value( string $value ): string {
		$value = trim( $value );
		if ( '' === $value || '0000-00-00 00:00:00' === $value ) {
			return '';
		}

		$timestamp = strtotime( $value . ' UTC' );
		if ( false === $timestamp ) {
			return '';
		}

		return gmdate( 'Y-m-d\\TH:i', $timestamp );
	}

	/**
	 * Renders URL input field.
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 *
	 * @return void
	 */
	private static function render_url_field( string $name, string $label ): void {
		?>
		<div>
			<label class="supershows-label" for="supershows-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="url" id="supershows-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" />
		</div>
		<?php
	}

	/**
	 * Renders email input field.
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 *
	 * @return void
	 */
	private static function render_email_field( string $name, string $label ): void {
		?>
		<div>
			<label class="supershows-label" for="supershows-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="email" id="supershows-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" />
		</div>
		<?php
	}

	/**
	 * Renders datetime input field.
	 *
	 * @param string $name  Field name.
	 * @param string $label Field label.
	 *
	 * @return void
	 */
	private static function render_datetime_field( string $name, string $label ): void {
		?>
		<div>
			<label class="supershows-label" for="supershows-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<input type="datetime-local" id="supershows-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" />
		</div>
		<?php
	}

	/**
	 * Renders media selector field.
	 *
	 * @param string $name     Field name.
	 * @param string $label    Field label.
	 * @param bool   $multiple Whether field allows multiple selections.
	 *
	 * @return void
	 */
	private static function render_media_field( string $name, string $label, bool $multiple ): void {
		?>
		<div>
			<label class="supershows-label" for="supershows-<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label>
			<div class="supershows-media-wrap">
				<input type="text" id="supershows-<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" class="regular-text supershows-input" readonly />
				<button type="button" class="button supershows-media-button" data-target="supershows-<?php echo esc_attr( $name ); ?>" data-multiple="<?php echo $multiple ? '1' : '0'; ?>">
					<?php echo $multiple ? esc_html__( 'Select Images', 'supershows-tradeshows-directory' ) : esc_html__( 'Select Image', 'supershows-tradeshows-directory' ); ?>
				</button>
			</div>
		</div>
		<?php
	}
}
