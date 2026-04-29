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
}
?>
<style>
article.sd-directory-entry{background-image:url(/wp-content/uploads/2025/06/contact-page-logo-mark-bkg.svg?id=3781)!important;background-repeat:no-repeat!important;background-size:cover!important;background-attachment:fixed;}
.sd-entry-wrap{max-width:1100px;margin:0 auto;padding:65px 16px;}
.sd-entry__path{letter-spacing:2px;text-transform:uppercase;margin-bottom:10px}
.sd-entry__path_header_holder{text-align:center;}
.sd-entry__path p{font-size:15px;color:#c73e1d;font-weight:600;}
.sd-entry__title{color:#0f172a;margin:0 0 16px;font-size:clamp(2rem,4vw,3rem);}
.sd-entry__logo{margin:0 auto 35px;max-width:240px;text-align:center;}
.sd-entry__logo-image{width:100%;height:auto;display:block;margin:0 auto;object-fit:contain;}
.sd-entry__grid{display:grid;grid-template-columns:300px 1fr;gap:28px;align-items:start}
.sd-entry__main{display:flex;flex-direction:column;gap:30px}
@media (max-width:980px){.sd-entry__grid{grid-template-columns:1fr;gap:18px}}
.sd-card{background:#000F3A!important;border-radius:16px;padding:40px 20px 50px 20px;}
.sd-card h3{margin:0 0 12px;color:#ebeae3;font-size:26px;border-bottom:1px solid #c73e1d;}
.sd-meta{margin:0;padding:0}
.sd-meta .sd-meta__row{display:flex;align-items:flex-start;gap:8px;padding:4px 0;}
.sd-meta dt{font-weight:bold;color:#6485ff;font-size:18px;}
.sd-meta dd{margin:0;color:#6485ff;text-align:left;font-size:14px;}
.sd-address{margin:0;color:#6485ff;font-weight:bold;font-size:18px;}
.sd-section{margin:0 0 26px}
.sd-section h2{margin:0 0 10px;color:#ebeae3;}
.sd-section .sd-section__body{color:#ebeae3;}
.sd-section-background{background-image:url(/wp-content/uploads/2025/05/hero-gradient-shape-background3.png)!important;background-position:center!important;background-repeat:no-repeat!important;background-size:cover!important;border-radius:30px!important;padding:40px 20px 50px 20px;}
.sd-gallery__grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:18px;}
.sd-gallery__item{border:1px solid rgba(255,255,255,.2);border-radius:20px;overflow:hidden;padding:0;background:rgba(15,23,42,.6);cursor:pointer;transition:transform .2s ease,box-shadow .2s ease;}
.sd-gallery__item:hover,.sd-gallery__item:focus-visible{transform:translateY(-2px);box-shadow:0 15px 25px rgba(0,0,0,.35);}
.sd-gallery__image{display:block;width:100%;height:100%;object-fit:cover;}
</style>
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
