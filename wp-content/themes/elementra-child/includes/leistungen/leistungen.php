<?php
/**
 * Interactive Leistungen (Services) Section
 *
 * Registers the [ste_leistungen] shortcode for use in Elementor's Shortcode widget.
 * Renders an interactive locomotive graphic with 8 service hotspots and a detail panel.
 *
 * @package Elementra-Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service data for the 8 Leistungen.
 *
 * Each entry: id, title, description, tools (optional), references (optional),
 * icon (per-service SVG file name in /assets), hotspot_x/hotspot_y
 * (percentage coordinates on the locomotive graphic).
 */
function ste_leistungen_get_services() {
	return array(
		array(
			'id'          => 'anforderungsmanagement',
			'title'       => 'Anforderungs&shy;management',
			/* U+00AD soft hyphen: browser only renders it when a break is needed. */
			'title_plain' => "Anforderungs\u{00AD}management",
			'description' => 'Wir erfassen, strukturieren und priorisieren technische und funktionale Anforderungen – als Grundlage für erfolgreiche Entwicklungsprojekte.',
			'tools'       => array( 'IBM® Engineering Requirements Management DOORS', 'Jama Software', 'Redmine' ),
			'references'  => array( 'Lok', 'LRV' ),
			'icon'        => '1-1--Anforderungsmanagement.svg',
			/* Coordinates from Zugspitze_hb_p_ss_o.svg (manually mirrored master), viewBox 386.53×205.17 */
			/* hotspot-1: cx=72.69 cy=17.45 → 72.69/386.53, 17.45/205.17 */
			'hotspot_x'   => 18.8058,
			'hotspot_y'   => 8.5051,
		),
		array(
			'id'          => 'fahrzeugkonzepte',
			'title'       => 'Entwicklung von Fahrzeug&shy;konzepten',
			'title_plain' => 'Entwicklung von Fahrzeugkonzepten',
			'description' => 'Wir entwickeln innovative Fahrzeugkonzepte auf Basis der Kundenanforderungen mit Fokus auf Funktionalität, Sicherheit und Integration – von der Idee bis zur Umsetzung.',
			'tools'       => array(),
			'references'  => array(),
			'icon'        => '2-4--Fahrzeugkonzepte.svg',
			/* hotspot-2: cx=170.71 cy=34.98 → 170.71/386.53, 34.98/205.17 */
			'hotspot_x'   => 44.1647,
			'hotspot_y'   => 17.0493,
		),
		array(
			'id'          => 'risikoanalyse',
			'title'       => 'Risikoanalyse',
			'title_plain' => 'Risikoanalyse',
			'description' => 'Wir führen fundierte Risikoanalysen zur frühzeitigen Identifikation und Bewertung potenzieller Risiken durch, egal ob nach MIL-STD, SIRF oder einem anderen Verfahren – Strukturiert, nachvollziehbar und sauber dokumentiert.',
			'tools'       => array(),
			'references'  => array( 'HRV', 'LRV' ),
			'icon'        => '3-7--Risikoanalyse.svg',
			/* hotspot-3: cx=252.72 cy=76.14 → 252.72/386.53, 76.14/205.17 */
			'hotspot_x'   => 65.3817,
			'hotspot_y'   => 37.1107,
		),
		array(
			'id'          => 'systemkonzepte',
			/* U+2011 non-breaking hyphen keeps "‑integration" together so the
			   line breaks at the regular space before it instead of after the dash. */
			'title'       => "Systemkonzepte & \u{2011}integration",
			'title_plain' => "Systemkonzepte & \u{2011}integration",
			'description' => 'Wir leiten ganzheitliche Systemkonzepte auf Basis des Fahrzeugkonzepts ab, bei denen besonders der Fokus auf die funktionale Integration von Zulieferteilen in das Gesamtfahrzeug liegt.',
			'tools'       => array(),
			'references'  => array( 'Lok', 'HRV', 'LRV' ),
			'icon'        => '4-2--Schnittstellenanalyse.svg',
			/* hotspot-4: cx=319.81 cy=129.39 → 319.81/386.53, 129.39/205.17 */
			'hotspot_x'   => 82.7387,
			'hotspot_y'   => 63.0648,
		),
		array(
			'id'          => 'schnittstellen',
			'title'       => 'Analyse & Spezifikation der Schnittstellen',
			'title_plain' => 'Analyse & Spezifikation der Schnittstellen',
			'description' => 'Wir erstellen Anforderungsspezifikationen für Komponenten, elektrische Verschaltungen und Softwarefunktionalitäten, sodass Klarheit im Engineering herrscht und mögliche Projektrisiken minimiert werden können.',
			'tools'       => array( 'IBM® Engineering Requirements Management DOORS', 'Jama Software', 'Redmine' ),
			'references'  => array( 'Lok', 'HGV' ),
			'icon'        => '6-7--SpezifikationenKomponenten.svg',
			/* Lower line (4 icons): hotspot-5: cx=335.16 cy=188.32 → 335.16/386.53, 188.32/205.17 */
			'hotspot_x'   => 86.7100,
			'hotspot_y'   => 91.7873,
		),
		array(
			'id'          => 'schaltplanerstellung',
			'title'       => 'Schaltplan&shy;erstellung & Kabelsatz&shy;design',
			'title_plain' => 'Schaltplanerstellung & Kabelsatzdesign',
			'description' => 'Wir setzen die Anforderungen an die elektrische Verschaltung fachgerecht in Schaltplänen mit Zuken E3 oder Engineering Base um – inklusive Kabelsatz- und Schaltschrankplanung und leiten die für die Produktion erforderliche Dokumentation daraus ab.',
			'tools'       => array( 'ZUKEN E3 (Schematic, Formboard, Panel)', 'AUCOTEC Engineering Base' ),
			'references'  => array( 'HRV', 'LRV', 'Einzelkomponenten' ),
			'icon'        => '7-2--Schaltplanerstellung.svg',
			/* hotspot-6: cx=239.87 cy=188.32 → 239.87/386.53, 188.32/205.17 */
			'hotspot_x'   => 62.0573,
			'hotspot_y'   => 91.7873,
		),
		array(
			'id'          => 'sicherheitsnachweis',
			'title'       => 'Sicherheits&shy;nachweis gemäß CSM',
			'title_plain' => 'Sicherheitsnachweis gemäß CSM',
			'description' => 'Wir dokumentieren gemäß den Vorgaben der CSM-Verordnung, sodass das am Ende eine schlüssige Nachweisführung aller sicherheitsrelevanter Anforderungen existiert, welche die Basis für das Vertrauen in die Produktsicherheit ist.',
			'tools'       => array( 'Office', 'Isograph Reliability Workbench' ),
			'references'  => array( 'HRV', 'LRV' ),
			'icon'        => '8-3--SicherheitsnachweisCSM.svg',
			/* hotspot-7: cx=145.55 cy=188.32 → 145.55/386.53, 188.32/205.17 */
			'hotspot_x'   => 37.6556,
			'hotspot_y'   => 91.7873,
		),
		array(
			'id'          => 'aenderungsmanagement',
			'title'       => 'Änderungs&shy;management & Baubetreuung',
			'title_plain' => "Änderungs\u{00AD}management & Baubetreuung",
			'description' => 'Wir begleiten die Produktionsphase als Ansprechpartner aus dem Engineering und unterstützen somit effizient im Fehler- und Änderungsmanagement – für eine reibungslose Umsetzung der Planung in die Realität.',
			'tools'       => array(),
			'references'  => array( 'HRV' ),
			'icon'        => '9-5--Änderungsmanagement.svg',
			/* hotspot-8: cx=46.56 cy=188.32 → 46.56/386.53, 188.32/205.17 */
			'hotspot_x'   => 12.0456,
			'hotspot_y'   => 91.7873,
		),
	);
}

/**
 * Reference name → icon file map (rolling stock icons).
 */
function ste_leistungen_get_reference_icon_map() {
	return array(
		'Lok' => 'Fahrzeuge-1--Lokomotive.svg',
		'HRV'      => 'Fahrzeuge-2--Doppelstock.svg',
		'HGV'                  => 'Fahrzeuge-3--Highspeed.svg',
		'LRV'    => 'Fahrzeuge-4--LRV.svg',
		'Einzelkomponenten'         => '6-5--SpezifikationenKomponenten.svg',
	);
}

/**
 * Canonical 4 reference vehicles that are always rendered in the
 * Referenzen row. Order is the visual order. Per-service references
 * outside this set (e.g. "Einzelkomponenten") are not represented here.
 */
function ste_leistungen_get_canonical_references() {
	return array(
		'Lok',
		'HRV',
		'LRV',
		'HGV',
	);
}

/**
 * Build the canonical 4 references for a given service, each with name,
 * icon URL and an active flag (true if the service's data lists it).
 */
function ste_leistungen_build_canonical_refs_for( $service_refs ) {
	$canonical = ste_leistungen_get_canonical_references();
	$icons     = ste_leistungen_get_reference_icon_map();
	$service_refs = is_array( $service_refs ) ? $service_refs : array();
	$out = array();
	foreach ( $canonical as $name ) {
		$icon_file = isset( $icons[ $name ] ) ? $icons[ $name ] : '';
		$out[] = array(
			'name'   => $name,
			'icon'   => ste_leistungen_resolve_icon_url( $icon_file ),
			'active' => in_array( $name, $service_refs, true ),
		);
	}
	return $out;
}

/**
 * Resolve an icon filename to a public URL. Falls back to icon_service.svg
 * when the file doesn't exist (so a typo or missing asset never breaks the
 * markup with a broken image).
 */
function ste_leistungen_resolve_icon_url( $filename ) {
	static $base_dir = null;
	static $base_url = null;
	if ( null === $base_dir ) {
		$base_dir = trailingslashit( get_stylesheet_directory() ) . 'includes/leistungen/assets/';
		$base_url = trailingslashit( get_stylesheet_directory_uri() ) . 'includes/leistungen/assets/';
	}
	if ( $filename && file_exists( $base_dir . $filename ) ) {
		return $base_url . $filename;
	}
	return $base_url . 'icon_service.svg';
}

/**
 * Register assets (styles and scripts) so they can be enqueued on demand.
 */
function ste_leistungen_register_assets() {
	$base_url = get_stylesheet_directory_uri() . '/includes/leistungen';
	$version  = '1.22.1';

	wp_register_style(
		'ste-leistungen',
		$base_url . '/leistungen.css',
		array(),
		$version
	);

	wp_register_style(
		'ste-leistungen-responsive',
		$base_url . '/leistungen-responsive.css',
		array( 'ste-leistungen' ),
		$version,
		'(max-width: 1440px)'
	);

	wp_register_script(
		'ste-leistungen',
		$base_url . '/leistungen.js',
		array( 'jquery' ),
		$version,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'ste_leistungen_register_assets', 1600 );

/**
 * Shortcode render callback for [ste_leistungen].
 */
function ste_leistungen_render( $atts ) {
	// Enqueue assets
	wp_enqueue_style( 'ste-leistungen' );
	wp_enqueue_style( 'ste-leistungen-responsive' );
	wp_enqueue_script( 'ste-leistungen' );

	$services = ste_leistungen_get_services();

	// Resolve per-service icon URL and the canonical 4 references (with active flag)
	$services_for_js = array_map( function( $s ) {
		return array(
			'id'          => $s['id'],
			'title'       => $s['title_plain'],
			'description' => $s['description'],
			'tools'       => $s['tools'],
			'icon'        => ste_leistungen_resolve_icon_url( $s['icon'] ),
			'references'  => ste_leistungen_build_canonical_refs_for( $s['references'] ),
		);
	}, $services );

	// Pass data to JS
	wp_localize_script( 'ste-leistungen', 'steLeistungenData', array(
		'services' => $services_for_js,
	) );

	$loco_url = get_stylesheet_directory_uri() . '/includes/leistungen/assets/Zugspitze_hb_p_ss_b2.svg';

	ob_start();
	?>
	<div class="ste-leistungen" id="ste-leistungen">

		<!-- Left column: service list only -->
		<div class="ste-leistungen__sidebar">
			<ul class="ste-leistungen__list">
				<?php foreach ( $services as $index => $service ) : ?>
					<li class="ste-leistungen__item<?php echo 0 === $index ? ' is-active' : ''; ?>"
					    data-index="<?php echo esc_attr( $index ); ?>"
					    data-service="<?php echo esc_attr( $service['id'] ); ?>"
					    role="button"
					    tabindex="0">
						<span class="ste-leistungen__item-number"><?php echo esc_html( $index + 1 ); ?>.</span>
						<span class="ste-leistungen__item-title"><?php echo wp_kses( $service['title'], array( 'shy' => array() ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>

		<!-- Composition area: content zones + locomotive + hotspots -->
		<div class="ste-leistungen__graphic">

			<!-- Content column: title, description, tools, references -->
			<?php
				$detail_classes = array( 'ste-leistungen__detail' );
				if ( empty( $services[0]['tools'] ) ) {
					$detail_classes[] = 'no-tools';
				}
				$initial_canonical_refs = ste_leistungen_build_canonical_refs_for( $services[0]['references'] );
			?>
			<div class="<?php echo esc_attr( implode( ' ', $detail_classes ) ); ?>" aria-live="polite">
				<div class="ste-leistungen__detail-main">
					<?php $active_icon_url = ste_leistungen_resolve_icon_url( $services[0]['icon'] ); ?>
					<img class="ste-leistungen__detail-active-icon" src="<?php echo esc_url( $active_icon_url ); ?>" alt="" />
					<h3 class="ste-leistungen__detail-title"><?php echo esc_html( $services[0]['title_plain'] ); ?></h3>
					<p class="ste-leistungen__detail-desc"><?php echo esc_html( $services[0]['description'] ); ?></p>
				</div>
				<?php if ( ! empty( $services[0]['tools'] ) ) : ?>
					<div class="ste-leistungen__detail-tools">
						<strong>Tools</strong>
						<span><?php echo esc_html( implode( ', ', $services[0]['tools'] ) ); ?></span>
					</div>
				<?php endif; ?>
				<div class="ste-leistungen__detail-refs">
					<span class="ste-leistungen__detail-refs-label">Referenzen</span>
					<?php foreach ( $initial_canonical_refs as $ref ) : ?>
						<span class="ste-leistungen__ref <?php echo $ref['active'] ? 'is-active' : 'is-inactive'; ?>">
							<span class="ste-leistungen__ref-icon-wrap">
								<img src="<?php echo esc_url( $ref['icon'] ); ?>" alt="" class="ste-leistungen__ref-icon" width="24" height="24" />
							</span>
							<span class="ste-leistungen__ref-label"><?php echo esc_html( $ref['name'] ); ?></span>
						</span>
					<?php endforeach; ?>
				</div>
			</div>

			<!-- Stage: shared aspect-ratio container for locomotive + icons -->
			<div class="ste-leistungen__stage">
				<!-- Locomotive background: SVG only -->
				<img src="<?php echo esc_url( $loco_url ); ?>"
				     alt=""
				     class="ste-leistungen__loco-img"
				     loading="lazy" />

				<!-- Icon layer: always above locomotive AND text panels -->
				<div class="ste-leistungen__icon-layer">
					<?php foreach ( $services as $index => $service ) : ?>
						<?php $hotspot_icon_url = ste_leistungen_resolve_icon_url( $service['icon'] ); ?>
						<button class="ste-leistungen__hotspot<?php echo 0 === $index ? ' is-active' : ''; ?>"
						        data-index="<?php echo esc_attr( $index ); ?>"
						        data-service="<?php echo esc_attr( $service['id'] ); ?>"
						        style="left: <?php echo esc_attr( $service['hotspot_x'] ); ?>%; top: <?php echo esc_attr( $service['hotspot_y'] ); ?>%;"
						        aria-label="<?php echo esc_attr( $service['title_plain'] ); ?>"
						        type="button">
							<span class="ste-leistungen__hotspot-bg"></span>
							<img src="<?php echo esc_url( $hotspot_icon_url ); ?>"
							     alt=""
							     class="ste-leistungen__hotspot-icon"
							     width="60"
							     height="60" />
						</button>
					<?php endforeach; ?>
				</div>
			</div>

		</div>

	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'ste_leistungen', 'ste_leistungen_render' );
