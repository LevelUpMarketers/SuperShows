<?php
/**
 * Front-end shortcode rendering for SuperShows.
 *
 * @package SuperShowsTradeShowsDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides front-end shortcode UI for browsing trade shows.
 */
class SuperShows_TradeShows_Frontend {
	private const PER_PAGE = 9;

	/**
	 * Shortcode tag.
	 */
	private const SHORTCODE = 'supershows_tradeshows';

	/**
	 * Wires shortcode + asset hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'render_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_ajax_supershows_search_tradeshows', array( __CLASS__, 'handle_search_ajax' ) );
		add_action( 'wp_ajax_nopriv_supershows_search_tradeshows', array( __CLASS__, 'handle_search_ajax' ) );
	}

	/**
	 * Enqueues front-end stylesheet when shortcode is present.
	 *
	 * @return void
	 */
	public static function enqueue_assets(): void {
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		$is_directory_shortcode = has_shortcode( $post->post_content, self::SHORTCODE );
		$is_listing_template   = SuperShows_TradeShows_Template::template_slug() === get_page_template_slug( $post );
		if ( ! $is_directory_shortcode && ! $is_listing_template ) {
			return;
		}

		wp_enqueue_style(
			'supershows-tradeshows-frontend',
			SUPERSHOWS_TRADE_SHOWS_URL . 'assets/css/frontend.css',
			array(),
			SUPERSHOWS_TRADE_SHOWS_VERSION
		);

		if ( $is_directory_shortcode ) {
			wp_enqueue_script(
				'supershows-tradeshows-frontend',
				SUPERSHOWS_TRADE_SHOWS_URL . 'assets/js/frontend.js',
				array(),
				SUPERSHOWS_TRADE_SHOWS_VERSION,
				true
			);

			wp_localize_script(
				'supershows-tradeshows-frontend',
				'sdDirectoryParent',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'supershows_search_tradeshows' ),
					'perPage' => self::PER_PAGE,
					'strings' => array(
						'noResults' => __( 'No trade shows matched your search.', 'supershows-tradeshows-directory' ),
						'error'     => __( 'Something went wrong while searching. Please try again.', 'supershows-tradeshows-directory' ),
						'view'      => __( 'Learn More', 'supershows-tradeshows-directory' ),
					),
				)
			);
		}
	}

	/**
	 * Renders shortcode output.
	 *
	 * @return string
	 */
	public static function render_shortcode(): string {
		$trade_shows = self::get_trade_shows( 1, self::PER_PAGE );
		$filters     = self::build_filter_options( self::get_trade_shows() );

		ob_start();
		?>
		<div class="sd-parent-wrap supershows-directory-wrap">
			<div class="sd-directory-search" id="directory-search">
				<h2><?php esc_html_e( 'Search & Filter Trade Shows', 'supershows-tradeshows-directory' ); ?></h2>
				<p><?php esc_html_e( 'Choose any combination of filters to find the right trade show, then click Search to apply them.', 'supershows-tradeshows-directory' ); ?></p>
				<form class="sd-directory-search__form" method="post" action="#">
					<div>
						<label for="sd-directory-search-name"><?php esc_html_e( 'Search by name', 'supershows-tradeshows-directory' ); ?></label>
						<input type="text" id="sd-directory-search-name" name="search" placeholder="<?php esc_attr_e( 'Enter a trade show name', 'supershows-tradeshows-directory' ); ?>">
					</div>
					<div>
						<label for="sd-directory-search-industry"><?php esc_html_e( 'Industry', 'supershows-tradeshows-directory' ); ?></label>
						<select id="sd-directory-search-industry" name="industry">
							<option value="" disabled selected><?php esc_html_e( 'Select an Industry...', 'supershows-tradeshows-directory' ); ?></option>
							<?php foreach ( $filters['industries'] as $industry ) : ?>
								<option value="<?php echo esc_attr( $industry ); ?>"><?php echo esc_html( $industry ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="sd-directory-search-state"><?php esc_html_e( 'State', 'supershows-tradeshows-directory' ); ?></label>
						<select id="sd-directory-search-state" name="state">
							<option value="" disabled selected><?php esc_html_e( 'Select a State...', 'supershows-tradeshows-directory' ); ?></option>
							<?php foreach ( $filters['states'] as $state ) : ?>
								<option value="<?php echo esc_attr( $state ); ?>"><?php echo esc_html( $state ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div>
						<label for="sd-directory-search-month-year"><?php esc_html_e( 'Month & Year', 'supershows-tradeshows-directory' ); ?></label>
						<select id="sd-directory-search-month-year" name="month_year">
							<option value="" disabled selected><?php esc_html_e( 'Select a Month & Year...', 'supershows-tradeshows-directory' ); ?></option>
							<?php foreach ( $filters['month_years'] as $month_year ) : ?>
								<option value="<?php echo esc_attr( $month_year['value'] ); ?>"><?php echo esc_html( $month_year['label'] ); ?></option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="sd-directory-search__actions">
						<button type="submit" class="sd-directory-search__submit"><?php esc_html_e( 'Search', 'supershows-tradeshows-directory' ); ?></button>
						<button type="reset" class="sd-directory-search__reset"><?php esc_html_e( 'Reset search', 'supershows-tradeshows-directory' ); ?></button>
					</div>
				</form>
			</div>

			<div class="sd-directory-status" role="status" aria-live="polite"></div>
			<div class="sd-directory-results">
				<?php foreach ( $trade_shows as $trade_show ) : ?>
					<?php self::render_trade_show_card( $trade_show ); ?>
				<?php endforeach; ?>
				<div class="sd-directory-load-sentinel" aria-hidden="true"><div class="sd-directory-load-indicator"></div></div>
			</div>
			<div class="sd-directory-pagination"></div>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Renders one front-end card.
	 *
	 * @param object $trade_show Trade show row.
	 *
	 * @return void
	 */
	private static function render_trade_show_card( object $trade_show ): void {
		$logo_id    = absint( $trade_show->logo_wordpress_image_id ?? 0 );
		$logo_url   = ( $logo_id > 0 ) ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
		$page_link  = '';
		$industries = self::decode_json_list( $trade_show->industries_json ?? '' );
		$meta_line  = implode( ' • ', $industries );

		if ( ! empty( $trade_show->page_id ) ) {
			$page_link = get_permalink( (int) $trade_show->page_id );
		}
		if ( empty( $page_link ) && ! empty( $trade_show->related_url ) ) {
			$page_link = $trade_show->related_url;
		}

		$style = '';
		if ( ! empty( $logo_url ) ) {
			$style = sprintf( '--sd-card-screenshot: url(%s);', esc_url_raw( $logo_url ) );
		}
		?>
		<a class="sd-directory-card<?php echo ! empty( $logo_url ) ? ' has-screenshot' : ''; ?>" href="<?php echo esc_url( $page_link ?: '#' ); ?>" <?php echo ! empty( $style ) ? 'style="' . esc_attr( $style ) . '"' : ''; ?>>
			<div class="sd-directory-card__logo">
				<?php if ( $logo_id > 0 ) : ?>
					<?php echo wp_kses_post( wp_get_attachment_image( $logo_id, 'medium', false, array( 'alt' => ( $trade_show->name ?? '' ) . ' logo' ) ) ); ?>
				<?php endif; ?>
			</div>
			<h3 class="sd-directory-card__title"><?php echo esc_html( $trade_show->name ?? '' ); ?></h3>
			<p class="sd-directory-card__meta"><?php echo esc_html( $meta_line ); ?></p>
			<span class="sd-directory-card__cta"><?php esc_html_e( 'Learn More', 'supershows-tradeshows-directory' ); ?></span>
		</a>
		<?php
	}

	/**
	 * Loads all trade shows for default card output.
	 *
	 * @return object[]
	 */
	private static function get_trade_shows( int $page = 1, int $per_page = 0 ): array {
		global $wpdb;

		$table_name = SuperShows_TradeShows_Activator::table_name();
		$sql        = "SELECT * FROM {$table_name} ORDER BY start_datetime DESC, start_year DESC, start_month DESC, name ASC";
		if ( $per_page > 0 ) {
			$offset = max( 0, ( $page - 1 ) * $per_page );
			$sql   .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );
		}
		$results = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return is_array( $results ) ? $results : array();
	}

	/**
	 * Build UI options for filters.
	 *
	 * @param object[] $trade_shows Rows.
	 *
	 * @return array<string, array<int, mixed>>
	 */
	private static function build_filter_options( array $trade_shows ): array {
		$industries      = array();
		$states          = array();
		$min_month_index = null;
		$max_month_index = null;

		foreach ( $trade_shows as $trade_show ) {
			foreach ( self::decode_json_list( $trade_show->industries_json ?? '' ) as $industry ) {
				$industries[] = $industry;
			}

			if ( ! empty( $trade_show->address_state ) ) {
				$states[] = (string) $trade_show->address_state;
			}

			$month = isset( $trade_show->start_month ) ? (int) $trade_show->start_month : 0;
			$year  = isset( $trade_show->start_year ) ? (int) $trade_show->start_year : 0;
			if ( $month >= 1 && $month <= 12 && $year > 0 ) {
				$index = ( $year * 12 ) + $month;
				if ( null === $min_month_index || $index < $min_month_index ) {
					$min_month_index = $index;
				}
				if ( null === $max_month_index || $index > $max_month_index ) {
					$max_month_index = $index;
				}
			}
		}

		$industries = array_values( array_unique( array_filter( array_map( 'trim', $industries ) ) ) );
		$states     = array_values( array_unique( array_filter( array_map( 'trim', $states ) ) ) );
		sort( $industries );
		sort( $states );

		$month_years = array();
		if ( null !== $min_month_index && null !== $max_month_index ) {
			for ( $index = $max_month_index; $index >= $min_month_index; --$index ) {
				$year        = (int) floor( $index / 12 );
				$month       = (int) ( $index % 12 );
				if ( 0 === $month ) {
					$month = 12;
					--$year;
				}
				$label = gmdate( 'F Y', gmmktime( 0, 0, 0, $month, 1, $year ) );
				$month_years[] = array(
					'value' => sprintf( '%04d-%02d', $year, $month ),
					'label' => $label,
				);
			}
		}

		return array(
			'industries'  => $industries,
			'states'      => $states,
			'month_years' => $month_years,
		);
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
				static fn( string $value ) => '' !== trim( $value )
			)
		);
	}

	/**
	 * Handles AJAX search requests for shortcode results.
	 *
	 * @return void
	 */
	public static function handle_search_ajax(): void {
		check_ajax_referer( 'supershows_search_tradeshows', 'nonce' );

		$name       = isset( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
		$industry   = isset( $_POST['industry'] ) ? sanitize_text_field( wp_unslash( $_POST['industry'] ) ) : '';
		$state      = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
		$month_year = isset( $_POST['month_year'] ) ? sanitize_text_field( wp_unslash( $_POST['month_year'] ) ) : '';

		$page     = isset( $_POST['page'] ) ? max( 1, absint( wp_unslash( $_POST['page'] ) ) ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? max( 1, absint( wp_unslash( $_POST['per_page'] ) ) ) : self::PER_PAGE;
		$results  = self::search_trade_shows( $name, $industry, $state, $month_year, $page, $per_page );
		$total    = self::count_trade_shows( $name, $industry, $state, $month_year );

		wp_send_json_success(
			array(
				'items'       => self::map_items_for_json( $results ),
				'page'        => $page,
				'total_pages' => (int) ceil( $total / $per_page ),
			)
		);
	}

	/**
	 * Search utility for trade shows table.
	 *
	 * @param string $name       Name filter.
	 * @param string $industry   Industry filter.
	 * @param string $state      State filter.
	 * @param string $month_year Month-year (YYYY-MM).
	 *
	 * @return object[]
	 */
	private static function search_trade_shows( string $name, string $industry, string $state, string $month_year, int $page, int $per_page ): array {
		global $wpdb;

		$table_name = SuperShows_TradeShows_Activator::table_name();
		$where      = array( '1=1' );
		$params     = array();

		if ( '' !== $name ) {
			$where[]  = 'name LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $name ) . '%';
		}
		if ( '' !== $industry ) {
			$where[]  = 'industries_search LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $industry ) . '%';
		}
		if ( '' !== $state ) {
			$where[]  = 'address_state = %s';
			$params[] = $state;
		}
		if ( 1 === preg_match( '/^\d{4}-\d{2}$/', $month_year ) ) {
			$parts = explode( '-', $month_year );
			$year  = (int) $parts[0];
			$month = (int) $parts[1];
			if ( $year > 0 && $month >= 1 && $month <= 12 ) {
				$where[]  = 'start_year = %d';
				$where[]  = 'start_month = %d';
				$params[] = $year;
				$params[] = $month;
			}
		}

		$sql = "SELECT * FROM {$table_name} WHERE " . implode( ' AND ', $where ) . ' ORDER BY start_datetime DESC, start_year DESC, start_month DESC, name ASC LIMIT %d OFFSET %d';
		$params[] = $per_page;
		$params[] = max( 0, ( $page - 1 ) * $per_page );
		// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare( $sql, $params );
		}
		$results = $wpdb->get_results( $sql );
		// phpcs:enable

		return is_array( $results ) ? $results : array();
	}

	private static function count_trade_shows( string $name, string $industry, string $state, string $month_year ): int {
		global $wpdb;
		$table_name = SuperShows_TradeShows_Activator::table_name();
		$where      = array( '1=1' );
		$params     = array();
		if ( '' !== $name ) { $where[] = 'name LIKE %s'; $params[] = '%' . $wpdb->esc_like( $name ) . '%'; }
		if ( '' !== $industry ) { $where[] = 'industries_search LIKE %s'; $params[] = '%' . $wpdb->esc_like( $industry ) . '%'; }
		if ( '' !== $state ) { $where[] = 'address_state = %s'; $params[] = $state; }
		if ( 1 === preg_match( '/^\d{4}-\d{2}$/', $month_year ) ) {
			$parts = explode( '-', $month_year ); $year = (int) $parts[0]; $month = (int) $parts[1];
			if ( $year > 0 && $month >= 1 && $month <= 12 ) { $where[] = 'start_year = %d'; $where[] = 'start_month = %d'; $params[] = $year; $params[] = $month; }
		}
		$sql = "SELECT COUNT(*) FROM {$table_name} WHERE " . implode( ' AND ', $where );
		if ( ! empty( $params ) ) { $sql = $wpdb->prepare( $sql, $params ); }
		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Maps rows to JSON-safe card data for JS rendering.
	 *
	 * @param object[] $rows Rows.
	 * @return array<int, array<string, string>>
	 */
	private static function map_items_for_json( array $rows ): array {
		$items = array();
		foreach ( $rows as $row ) {
			$logo_id    = absint( $row->logo_wordpress_image_id ?? 0 );
			$logo_url   = ( $logo_id > 0 ) ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
			$page_link  = '';
			$industries = self::decode_json_list( $row->industries_json ?? '' );
			if ( ! empty( $row->page_id ) ) {
				$page_link = get_permalink( (int) $row->page_id );
			}
			if ( empty( $page_link ) && ! empty( $row->related_url ) ) {
				$page_link = (string) $row->related_url;
			}
			$items[] = array(
				'name'       => (string) ( $row->name ?? '' ),
				'logo'       => (string) $logo_url,
				'meta'       => implode( ' • ', $industries ),
				'permalink'  => (string) $page_link,
				'screenshot' => (string) $logo_url,
			);
		}
		return $items;
	}
}
