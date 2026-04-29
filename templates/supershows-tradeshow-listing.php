<?php
/**
 * Template Name: SuperShows Trade Show Listing
 *
 * @package SuperShowsTradeShowsDirectory
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

global $wpdb;
$table_name = SuperShows_TradeShows_Activator::table_name();
$post_id    = get_the_ID();
$trade_show = null;
if ( $post_id ) {
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$trade_show = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE page_id = %d LIMIT 1", $post_id ) );
	// phpcs:enable
}

$address   = array();
$imagery   = array();
$industry  = array();
$gallery   = array();
$logo_id   = 0;
$socials   = array();
if ( $trade_show ) {
	$address  = json_decode( (string) $trade_show->address_json, true );
	$imagery  = json_decode( (string) $trade_show->imagery_json, true );
	$industry = json_decode( (string) $trade_show->industries_json, true );
	$logo_id  = absint( $trade_show->logo_wordpress_image_id );
	if ( ! $logo_id && is_array( $imagery ) && ! empty( $imagery['logo_image_id'] ) ) {
		$logo_id = absint( $imagery['logo_image_id'] );
	}
	if ( is_array( $imagery ) && ! empty( $imagery['gallery_image_ids'] ) && is_array( $imagery['gallery_image_ids'] ) ) {
		$gallery = array_filter( array_map( 'absint', $imagery['gallery_image_ids'] ) );
	}
	$socials = array(
		'facebook' => (string) ( $trade_show->facebook_url ?? '' ),
		'instagram' => (string) ( $trade_show->instagram_url ?? '' ),
		'linkedin' => (string) ( $trade_show->linkedin_url ?? '' ),
		'youtube' => (string) ( $trade_show->youtube_url ?? '' ),
	);
}
?>
<main id="primary" class="site-main sd-directory-template">
	<article <?php post_class( 'sd-directory-entry' ); ?>>
		<div class="sd-entry-wrap">
			<div class="sd-entry__path_header_holder">
				<div class="sd-entry__path"><p>/ <?php esc_html_e( 'Trade Shows', 'supershows-tradeshows-directory' ); ?> /</p></div>
				<header class="entry-header">
					<h1 class="sd-entry__title"><?php the_title(); ?></h1>
					<?php if ( $logo_id ) : ?>
						<div class="sd-entry__logo"><?php echo wp_kses_post( wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'sd-entry__logo-image' ) ) ); ?></div>
					<?php endif; ?>
				</header>
			</div>
			<div class="sd-entry__grid">
				<aside class="sd-card">
					<h3><?php esc_html_e( 'Overview', 'supershows-tradeshows-directory' ); ?></h3>
					<dl class="sd-meta">
						<div class="sd-meta__row"><div class="sd-meta__text"><dt><?php esc_html_e( 'Industries', 'supershows-tradeshows-directory' ); ?></dt><dd><?php echo esc_html( implode( ', ', is_array( $industry ) ? $industry : array() ) ); ?></dd></div></div>
						<div class="sd-meta__row"><div class="sd-meta__text"><dt><?php esc_html_e( 'Start Date', 'supershows-tradeshows-directory' ); ?></dt><dd><?php echo esc_html( (string) ( $trade_show->start_datetime ?? '' ) ); ?></dd></div></div>
					</dl>
					<h3 style="margin-top:16px;"><?php esc_html_e( 'Location', 'supershows-tradeshows-directory' ); ?></h3>
					<address class="sd-address"><?php echo esc_html( implode( ', ', array_filter( array( $address['street'] ?? '', $address['city'] ?? '', $address['state'] ?? '', $address['zip'] ?? '', $address['country'] ?? '' ) ) ) ); ?></address>
					<?php if ( ! empty( $trade_show->contact_phone ) || ! empty( $trade_show->contact_email ) ) : ?>
						<h3 style="margin-top:16px;"><?php esc_html_e( 'Contact', 'supershows-tradeshows-directory' ); ?></h3>
						<div class="sd-contact">
							<?php if ( ! empty( $trade_show->contact_phone ) ) : ?>
								<div class="sd-contact__row"><a class="sd-contact__link" href="<?php echo esc_url( 'tel:' . preg_replace( '/[^0-9+]/', '', (string) $trade_show->contact_phone ) ); ?>"><?php echo esc_html( (string) $trade_show->contact_phone ); ?></a></div>
							<?php endif; ?>
							<?php if ( ! empty( $trade_show->contact_email ) ) : ?>
								<div class="sd-contact__row"><a class="sd-contact__link" href="<?php echo esc_url( 'mailto:' . (string) $trade_show->contact_email ); ?>"><?php echo esc_html( (string) $trade_show->contact_email ); ?></a></div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( array_filter( $socials ) || ! empty( $trade_show->related_url ) ) : ?>
						<h3 style="margin-top:16px;"><?php esc_html_e( 'Connect', 'supershows-tradeshows-directory' ); ?></h3>
						<ul class="sd-connect">
							<?php foreach ( $socials as $network => $url ) : ?>
								<?php if ( ! empty( $url ) ) : ?>
									<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( strtoupper( substr( $network, 0, 1 ) ) ); ?></a></li>
								<?php endif; ?>
							<?php endforeach; ?>
						</ul>
						<?php if ( ! empty( $trade_show->related_url ) ) : ?>
							<ul class="sd-connect-text"><li><a href="<?php echo esc_url( (string) $trade_show->related_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Website', 'supershows-tradeshows-directory' ); ?></a></li></ul>
						<?php endif; ?>
					<?php endif; ?>
				</aside>
				<div class="sd-entry__main">
					<div class="sd-section-background">
						<section class="sd-section">
							<h2><?php esc_html_e( 'Trade Show Details', 'supershows-tradeshows-directory' ); ?></h2>
							<div class="sd-section__body"><?php echo wp_kses_post( wpautop( (string) ( $trade_show->description ?? '' ) ) ); ?></div>
						</section>
					</div>
					<?php if ( ! empty( $gallery ) ) : ?>
						<div class="sd-gallery sd-section-background">
							<section class="sd-section">
								<h2><?php esc_html_e( 'Gallery', 'supershows-tradeshows-directory' ); ?></h2>
								<div class="sd-gallery__grid">
									<?php foreach ( $gallery as $gallery_id ) : ?>
										<button type="button" class="sd-gallery__item" data-full-image="<?php echo esc_url( (string) wp_get_attachment_image_url( $gallery_id, 'full' ) ); ?>" data-alt="<?php echo esc_attr( get_the_title() ); ?>">
											<?php echo wp_kses_post( wp_get_attachment_image( $gallery_id, 'large', false, array( 'class' => 'sd-gallery__image' ) ) ); ?>
										</button>
									<?php endforeach; ?>
								</div>
							</section>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</article>
</main>
<script>
document.addEventListener('DOMContentLoaded', function () {
	var galleryButtons = document.querySelectorAll('.sd-gallery__item');
	var lightbox = document.querySelector('.sd-gallery-lightbox');
	if (!galleryButtons.length || !lightbox) return;
});
</script>
<?php get_footer();
