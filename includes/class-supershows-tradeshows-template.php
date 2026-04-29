<?php
/**
 * Trade show page template registration and resolution.
 *
 * @package SuperShowsTradeShowsDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SuperShows_TradeShows_Template {
	private const TEMPLATE_SLUG = 'supershows-tradeshow-listing.php';

	public static function init(): void {
		add_filter( 'theme_page_templates', array( __CLASS__, 'register_template' ) );
		add_filter( 'template_include', array( __CLASS__, 'resolve_template' ) );
	}

	public static function template_slug(): string {
		return self::TEMPLATE_SLUG;
	}

	public static function register_template( array $templates ): array {
		$templates[ self::TEMPLATE_SLUG ] = __( 'SuperShows Trade Show Listing', 'supershows-tradeshows-directory' );
		return $templates;
	}

	public static function resolve_template( string $template ): string {
		if ( ! is_singular( 'page' ) ) {
			return $template;
		}

		$post_id = get_queried_object_id();
		if ( $post_id <= 0 ) {
			return $template;
		}

		$page_template = get_post_meta( $post_id, '_wp_page_template', true );
		if ( self::TEMPLATE_SLUG !== $page_template ) {
			return $template;
		}

		$plugin_template = SUPERSHOWS_TRADE_SHOWS_PATH . 'templates/' . self::TEMPLATE_SLUG;
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return $template;
	}
}
