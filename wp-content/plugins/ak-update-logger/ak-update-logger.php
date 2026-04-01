<?php
/**
 * Plugin Name: Update Logger API
 * Description: Logs WordPress updates (Plugins, Themes, Core, Database & Theme-Template-Plugins via TGM/Envato) and exposes them via REST API for external dashboards
 * Version: 1.8.0
 * Author: AUGENKLANG
 * 
 * Changelog 1.8.0:
 * - UI: Zweispaltiges Layout für Desktop/Tablet
 * - Zeile 1: Statistik | Wartungsarbeit
 * - Zeile 2: Letzte Updates | API-Konfiguration
 * - Zeile 3: TGMPA Status | Debug-Modus
 * - FIX: Korrekte Versionsnummern bei TGMPA-Updates (alte Version wird vor Update gespeichert)
 * - Verbesserte Version-Caching vor Plugin-Downloads
 * - Korrektes Tracking der neuen Version nach Update-Abschluss
 * 
 * Changelog 1.7.0:
 * - Erweiterte Bridge/Qode Theme-Plugin Erkennung aus 1.8.0
 * - Verbesserte TGMPA Bulk Installer Unterstützung
 * - Alternative Hooks für Theme-gebundelte Plugins
 * - Frühere Version-Erfassung für TGMPA-Updates
 * - Prioritäts-Optimierung für upgrader_process_complete
 */

if (!defined('ABSPATH')) {
    exit;
}

// Debug-Modus aktivieren (in wp-config.php setzen: define('UPDATE_LOGGER_DEBUG', true);)
if (!defined('UPDATE_LOGGER_DEBUG')) {
    define('UPDATE_LOGGER_DEBUG', false);
}

class Update_Logger_API {
    
    private $option_key = 'update_logger_logs';
    private $api_key_option = 'update_logger_api_key';
    private $pre_update_versions = [];
    private $tgmpa_plugins = [];
    private $tgmpa_updates = []; // Trackt TGMPA-Updates
    private $versions_transient_key = 'update_logger_pre_versions';
    
    public function __construct() {
        // WICHTIG: Versionen sehr früh erfassen, BEVOR irgendein Update startet
        add_action('admin_init', [$this, 'capture_all_plugin_versions_early'], 1);
        
        // Bei TGMPA-Seiten extra früh erfassen
        add_action('load-appearance_page_tgmpa-install-plugins', [$this, 'capture_all_plugin_versions_early'], 1);
        add_action('load-themes_page_tgmpa-install-plugins', [$this, 'capture_all_plugin_versions_early'], 1);
        add_action('load-plugins_page_tgmpa-install-plugins', [$this, 'capture_all_plugin_versions_early'], 1);
        
        // Hooks zum Erfassen der alten Versionen VOR dem Update
        add_filter('upgrader_pre_download', [$this, 'capture_version_before_download'], 1, 4);
        add_filter('upgrader_pre_install', [$this, 'capture_pre_update_versions'], 1, 2);
        add_action('upgrader_package_options', [$this, 'capture_package_versions'], 1, 1);
        
        // Spezieller Hook für TGMPA Bulk Installer (früher als log_update)
        add_action('upgrader_process_complete', [$this, 'check_tgmpa_upgrader'], 4, 2);
        
        // Haupt-Hook für Updates mit niedriger Priorität (5) für frühe Ausführung
        add_action('upgrader_process_complete', [$this, 'log_update'], 5, 2);
        
        // Database update hooks
        $this->register_database_update_hooks();
        
        // TGM Plugin Activation Hooks
        $this->register_tgmpa_hooks();
        
        // Envato Market Hooks
        $this->register_envato_hooks();
        
        // Register REST API endpoint
        add_action('rest_api_init', [$this, 'register_routes']);
        
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Generate API key on activation if not exists
        register_activation_hook(__FILE__, [$this, 'generate_api_key_if_missing']);
        
        // Also check on admin init
        add_action('admin_init', [$this, 'generate_api_key_if_missing']);
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        
        // AJAX handlers
        add_action('wp_ajax_regenerate_update_logger_key', [$this, 'ajax_regenerate_key']);
        add_action('wp_ajax_test_database_log', [$this, 'ajax_test_database_log']);
        add_action('wp_ajax_test_theme_plugin_log', [$this, 'ajax_test_theme_plugin_log']);
        add_action('wp_ajax_delete_update_log_entry', [$this, 'ajax_delete_log_entry']);
        add_action('wp_ajax_delete_all_update_logs', [$this, 'ajax_delete_all_logs']);
        add_action('wp_ajax_add_manual_maintenance_entry', [$this, 'ajax_add_maintenance_entry']);
        
        // Lade gespeicherte Versionen aus dem Transient
        $this->load_saved_versions();
    }
    
    /**
     * Lade zuvor gespeicherte Versionen aus dem Transient
     */
    private function load_saved_versions() {
        $saved = get_transient($this->versions_transient_key);
        if ($saved && is_array($saved)) {
            $this->pre_update_versions = $saved;
            $this->debug_log('Loaded saved versions from transient', count($saved['plugin'] ?? []));
        }
    }
    
    /**
     * Debug Logging
     */
    private function debug_log($message, $data = null) {
        if (!UPDATE_LOGGER_DEBUG) {
            return;
        }
        
        $log_message = '[Update Logger] ' . $message;
        if ($data !== null) {
            $log_message .= ': ' . (is_array($data) || is_object($data) ? print_r($data, true) : $data);
        }
        
        error_log($log_message);
        
        // Also write to a dedicated log file
        $log_file = WP_CONTENT_DIR . '/update-logger-debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] $log_message\n", FILE_APPEND);
    }
    
    /**
     * Register TGM Plugin Activation hooks
     */
    private function register_tgmpa_hooks() {
        // Capture TGMPA registered plugins as early as possible
        add_action('tgmpa_register', [$this, 'capture_tgmpa_plugins'], 999);
        add_action('init', [$this, 'capture_tgmpa_plugins'], 999);
        add_action('admin_init', [$this, 'capture_tgmpa_plugins'], 999);
        
        // Hook into TGMPA plugin activation
        add_action('tgmpa_plugin_activated', [$this, 'log_tgmpa_plugin_activated'], 10, 1);
        
        // TGMPA before plugin update (very early)
        add_action('tgmpa_before_plugin_install', [$this, 'detect_tgmpa_update_request'], 1);
        add_action('tgmpa_before_plugin_update', [$this, 'detect_tgmpa_update_request'], 1);
        
        // TGMPA after row
        add_action('tgmpa_after_plugin_row', [$this, 'log_tgmpa_after_row'], 10, 2);
    }
    
    /**
     * Spezieller Hook um TGMPA Bulk Installer Updates zu erkennen (aus 1.8.0)
     */
    public function check_tgmpa_upgrader($upgrader, $options) {
        $this->debug_log('check_tgmpa_upgrader called', [
            'upgrader_class' => get_class($upgrader),
            'skin_class' => isset($upgrader->skin) ? get_class($upgrader->skin) : 'none',
            'options' => $options
        ]);
        
        // Prüfen ob es ein TGMPA Update ist
        $is_tgmpa = false;
        
        if (isset($upgrader->skin)) {
            $skin_class = get_class($upgrader->skin);
            if (strpos($skin_class, 'TGM') !== false || 
                strpos($skin_class, 'Bulk') !== false ||
                strpos($skin_class, 'TGMPA') !== false) {
                $is_tgmpa = true;
                $this->debug_log("TGMPA upgrader detected via skin class: $skin_class");
            }
        }
        
        // TGMPA über hook_extra erkennen
        if (isset($options['hook_extra']['tgmpa']) && $options['hook_extra']['tgmpa']) {
            $is_tgmpa = true;
            $this->debug_log("TGMPA upgrader detected via hook_extra");
        }
        
        // Über GET-Parameter erkennen
        if (isset($_GET['page']) && (
            strpos($_GET['page'], 'tgmpa') !== false ||
            strpos($_GET['page'], 'install-required') !== false ||
            strpos($_GET['page'], 'theme-plugins') !== false
        )) {
            $is_tgmpa = true;
            $this->debug_log("TGMPA upgrader detected via page parameter");
        }
        
        if ($is_tgmpa && isset($options['type']) && $options['type'] === 'plugin') {
            // Plugin-Slugs aus verschiedenen Quellen ermitteln
            $plugins = [];
            if (isset($options['plugins'])) {
                $plugins = $options['plugins'];
            } elseif (isset($options['plugin'])) {
                $plugins = [$options['plugin']];
            } elseif (isset($upgrader->result['destination_name'])) {
                $plugins = [$upgrader->result['destination_name']];
            }
            
            foreach ($plugins as $plugin) {
                $slug = dirname($plugin);
                if ($slug === '.') {
                    $slug = basename($plugin, '.php');
                }
                $this->tgmpa_updates[$slug] = true;
                $this->debug_log("Marked as TGMPA update", $slug);
            }
        }
    }
    
    /**
     * TGMPA Update-Anfrage erkennen und Versionen speichern
     */
    public function detect_tgmpa_update_request() {
        $this->debug_log('detect_tgmpa_update_request called');
        $this->capture_all_plugin_versions_early();
    }
    
    /**
     * Erfasst alle Plugin-Versionen sehr früh - BEVOR Updates starten
     * Speichert sie persistent in einem Transient
     */
    public function capture_all_plugin_versions_early() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Nur speichern wenn noch keine Versionen vorhanden ODER bei speziellen Seiten
        $force_capture = false;
        if (isset($_GET['page']) && (
            strpos($_GET['page'], 'tgmpa') !== false ||
            strpos($_GET['page'], 'plugin') !== false ||
            strpos($_GET['page'], 'update') !== false
        )) {
            $force_capture = true;
        }
        
        if (isset($_REQUEST['action']) && (
            strpos($_REQUEST['action'], 'update') !== false ||
            strpos($_REQUEST['action'], 'install') !== false ||
            strpos($_REQUEST['action'], 'tgmpa') !== false
        )) {
            $force_capture = true;
        }
        
        // Prüfen ob wir bereits Versionen haben und ob die Daten aktuell sind
        $saved = get_transient($this->versions_transient_key);
        $saved_time = get_transient($this->versions_transient_key . '_time');
        
        // Wenn Daten älter als 30 Sekunden sind oder force_capture, neu erfassen
        if ($force_capture || !$saved || !$saved_time || (time() - $saved_time) > 30) {
            $all_plugins = get_plugins();
            
            if (!isset($this->pre_update_versions['plugin'])) {
                $this->pre_update_versions['plugin'] = [];
            }
            if (!isset($this->pre_update_versions['plugin_by_slug'])) {
                $this->pre_update_versions['plugin_by_slug'] = [];
            }
            
            foreach ($all_plugins as $plugin_file => $plugin_data) {
                $version = $plugin_data['Version'] ?? '';
                $slug = dirname($plugin_file);
                if ($slug === '.') {
                    $slug = basename($plugin_file, '.php');
                }
                
                // NUR setzen wenn nicht bereits vorhanden (verhindert Überschreiben mit neuer Version)
                if (!isset($this->pre_update_versions['plugin'][$plugin_file]) || empty($this->pre_update_versions['plugin'][$plugin_file])) {
                    $this->pre_update_versions['plugin'][$plugin_file] = $version;
                }
                if (!isset($this->pre_update_versions['plugin_by_slug'][$slug]) || empty($this->pre_update_versions['plugin_by_slug'][$slug])) {
                    $this->pre_update_versions['plugin_by_slug'][$slug] = $version;
                }
            }
            
            // Persistent speichern
            set_transient($this->versions_transient_key, $this->pre_update_versions, HOUR_IN_SECONDS);
            set_transient($this->versions_transient_key . '_time', time(), HOUR_IN_SECONDS);
            
            $this->debug_log('Plugin versions captured early and saved', count($all_plugins));
        }
    }
    
    /**
     * Version vor dem Download erfassen (sehr früh im Update-Prozess)
     */
    public function capture_version_before_download($reply, $package, $upgrader, $hook_extra = []) {
        $this->debug_log('capture_version_before_download called', [
            'package' => $package,
            'upgrader_class' => get_class($upgrader),
            'hook_extra' => $hook_extra
        ]);
        
        // Alle Plugin-Versionen speichern
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $all_plugins = get_plugins();
        
        if (!isset($this->pre_update_versions['plugin'])) {
            $this->pre_update_versions['plugin'] = [];
        }
        if (!isset($this->pre_update_versions['plugin_by_slug'])) {
            $this->pre_update_versions['plugin_by_slug'] = [];
        }
        
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $slug = dirname($plugin_file);
            if ($slug === '.') {
                $slug = basename($plugin_file, '.php');
            }
            
            // Speichere NUR wenn noch nicht vorhanden
            if (!isset($this->pre_update_versions['plugin'][$plugin_file]) || empty($this->pre_update_versions['plugin'][$plugin_file])) {
                $this->pre_update_versions['plugin'][$plugin_file] = $plugin_data['Version'];
            }
            if (!isset($this->pre_update_versions['plugin_by_slug'][$slug]) || empty($this->pre_update_versions['plugin_by_slug'][$slug])) {
                $this->pre_update_versions['plugin_by_slug'][$slug] = $plugin_data['Version'];
            }
        }
        
        // Persistent speichern
        set_transient($this->versions_transient_key, $this->pre_update_versions, HOUR_IN_SECONDS);
        
        $this->debug_log('Pre-update versions captured before download', count($this->pre_update_versions['plugin'] ?? []));
        
        return $reply;
    }
    
    /**
     * Register Envato Market hooks
     */
    private function register_envato_hooks() {
        // Envato Market Plugin update hooks
        add_action('upgrader_process_complete', [$this, 'check_envato_update'], 15, 2);
        
        // Direct Envato Market API update hook (if available)
        add_action('envato_market_plugin_updated', [$this, 'log_envato_plugin_update'], 10, 2);
        add_action('envato_market_theme_updated', [$this, 'log_envato_theme_update'], 10, 2);
        
        // Slider Revolution specific hook
        add_action('revslider_update_plugin', [$this, 'log_slider_revolution_update']);
        
        // LayerSlider specific hook
        add_action('layerslider_update', [$this, 'log_layerslider_update']);
    }
    
    /**
     * Capture TGM-registered plugins for later identification
     */
    public function capture_tgmpa_plugins() {
        if (class_exists('TGM_Plugin_Activation') && isset($GLOBALS['tgmpa'])) {
            $tgmpa = $GLOBALS['tgmpa'];
            if (method_exists($tgmpa, 'get_plugins') || isset($tgmpa->plugins)) {
                $plugins = isset($tgmpa->plugins) ? $tgmpa->plugins : [];
                foreach ($plugins as $slug => $plugin) {
                    $this->tgmpa_plugins[$slug] = [
                        'name' => $plugin['name'] ?? $slug,
                        'source' => $plugin['source'] ?? 'bundled',
                        'version' => $plugin['version'] ?? '',
                    ];
                }
            }
        }
        
        // Also store in transient for later use
        if (!empty($this->tgmpa_plugins)) {
            set_transient('update_logger_tgmpa_plugins', $this->tgmpa_plugins, DAY_IN_SECONDS);
            $this->debug_log('TGMPA plugins captured', array_keys($this->tgmpa_plugins));
        }
    }
    
    /**
     * Log TGMPA plugin activation
     */
    public function log_tgmpa_plugin_activated($plugin_slug) {
        $base_slug = explode('/', $plugin_slug)[0];
        
        $this->tgmpa_plugins[$base_slug] = [
            'name' => $plugin_slug,
            'source' => 'tgmpa',
            'detected_by' => 'activation_hook'
        ];
        $this->tgmpa_updates[$base_slug] = true;
        
        set_transient('update_logger_tgmpa_plugins', $this->tgmpa_plugins, DAY_IN_SECONDS);
        $this->debug_log('TGMPA plugin activated', $plugin_slug);
    }
    
    /**
     * Log TGMPA after row (for tracking)
     */
    public function log_tgmpa_after_row($slug, $plugin) {
        $this->tgmpa_plugins[$slug] = [
            'name' => $plugin['name'] ?? $slug,
            'source' => $plugin['source'] ?? 'bundled',
            'version' => $plugin['version'] ?? '',
        ];
        
        set_transient('update_logger_tgmpa_plugins', $this->tgmpa_plugins, DAY_IN_SECONDS);
    }
    
    /**
     * Prüft ob ein Plugin von TGMPA verwaltet wird
     */
    private function is_tgmpa_managed_plugin($plugin_slug) {
        $base_slug = dirname($plugin_slug);
        if ($base_slug === '.') {
            $base_slug = basename($plugin_slug, '.php');
        }
        
        // Prüfen ob wir es als TGMPA-Update markiert haben
        if (isset($this->tgmpa_updates[$base_slug])) {
            $this->debug_log("Plugin is in TGMPA updates list", $base_slug);
            return true;
        }
        
        // Load from transient if not in memory
        if (empty($this->tgmpa_plugins)) {
            $this->tgmpa_plugins = get_transient('update_logger_tgmpa_plugins') ?: [];
        }
        
        // Direkte TGMPA-Registrierung prüfen
        if (isset($this->tgmpa_plugins[$base_slug])) {
            $this->debug_log("Plugin found in TGMPA registry", $base_slug);
            return true;
        }
        
        // Prüfen ob TGMPA Klasse existiert und Plugin dort registriert ist
        if (class_exists('TGM_Plugin_Activation')) {
            $tgmpa = isset($GLOBALS['tgmpa']) ? $GLOBALS['tgmpa'] : TGM_Plugin_Activation::get_instance();
            
            if (isset($tgmpa->plugins[$base_slug])) {
                $this->debug_log("Plugin found in TGMPA global registry", $base_slug);
                return true;
            }
            
            // Auch nach Plugin-Name suchen (partial match)
            foreach ($tgmpa->plugins as $registered_slug => $plugin_data) {
                if (stripos($registered_slug, $base_slug) !== false || 
                    (isset($plugin_data['slug']) && stripos($plugin_data['slug'], $base_slug) !== false)) {
                    $this->debug_log("Plugin matched TGMPA registry by partial slug", [
                        'search' => $base_slug,
                        'found' => $registered_slug
                    ]);
                    return true;
                }
            }
        }
        
        // Prüfen ob wir von der TGMPA-Seite kommen
        if (isset($_GET['page']) && (
            strpos($_GET['page'], 'tgmpa') !== false ||
            strpos($_GET['page'], 'install-required-plugins') !== false ||
            strpos($_GET['page'], 'theme-plugins') !== false
        )) {
            $this->debug_log("Request came from TGMPA page", $_GET['page']);
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if a plugin is bundled with a theme
     */
    private function is_theme_bundled_plugin($plugin_slug, $plugin_data = null) {
        $base_slug = strtolower(dirname($plugin_slug));
        if ($base_slug === '.') {
            $base_slug = strtolower(basename($plugin_slug, '.php'));
        }
        
        // Erweiterte Liste von Theme-gebundelten Plugin-Indikatoren
        $bundled_indicators = [
            // Bridge / Qode Theme Familie (ThemeForest)
            'bridge-core',
            'bridge_core',
            'bridgecore',
            'bridge-',
            'qode-core',
            'qode_core',
            'qode-listing',
            'qode-wishlist',
            'qode-essential-addons',
            'qode-framework',
            'qode-starter',
            'qode-starter-sites',
            'qode-',
            
            // Mikado Themes (ältere Qode-Themes)
            'mikado-core',
            'mikado_core',
            'mikado-',
            
            // Starter/Core generisch
            'starter-core',
            '-starter-sites',
            'theme-core',
            '-core',
            
            // WPBakery / Visual Composer
            'js_composer',
            'js-composer',
            'wpbakery',
            'visual-composer',
            'vc-',
            
            // Revolution Slider
            'revslider',
            'revolution-slider',
            'slider-revolution',
            'rs-plugin',
            
            // LayerSlider
            'layerslider',
            'layer-slider',
            
            // Envato Elements & Market
            'envato-elements',
            'envato-market',
            
            // Weitere häufige Premium-Plugins
            'ultimate-addons',
            'ultimate_vc',
            'convertplug',
            'templatera',
            'essential-grid',
            'master-slider',
            'go-pricing',
            'bdthemes-',
            'theme-starter',
            
            // Allgemeine Premium-Plugin-Indikatoren
            'starter-sites',
            'starter-templates',
            'demo-import',
            'theme-demo',
        ];
        
        foreach ($bundled_indicators as $indicator) {
            if (stripos($base_slug, $indicator) !== false) {
                $this->debug_log("Plugin matched bundled indicator: {$indicator}", $base_slug);
                return true;
            }
        }
        
        // Prüfen ob das Plugin aus dem Theme-Ordner kommt
        $theme_path = get_template_directory();
        $possible_paths = [
            $theme_path . '/plugins/' . $base_slug,
            $theme_path . '/inc/plugins/' . $base_slug,
            $theme_path . '/includes/plugins/' . $base_slug,
            $theme_path . '/lib/plugins/' . $base_slug,
        ];
        
        foreach ($possible_paths as $path) {
            if (is_dir($path)) {
                $this->debug_log("Plugin found in theme directory", $path);
                return true;
            }
        }
        
        // Prüfen ob Plugin-Author auf Theme-Entwickler hinweist
        if ($plugin_data && is_array($plugin_data)) {
            $author = strtolower($plugin_data['Author'] ?? '');
            $author_uri = strtolower($plugin_data['AuthorURI'] ?? '');
            $plugin_uri = strtolower($plugin_data['PluginURI'] ?? '');
            $name = strtolower($plugin_data['Name'] ?? '');
            
            $premium_authors = [
                'qode interactive',
                'qode',
                'themepunch',
                'theme starter',
                'starter sites',
                'starter templates',
                'envato',
                'themeforest',
                'codecanyon',
                'mikado',
                'elated',
            ];
            
            foreach ($premium_authors as $pa) {
                if (stripos($author, $pa) !== false || 
                    stripos($author_uri, $pa) !== false ||
                    stripos($plugin_uri, $pa) !== false) {
                    $this->debug_log("Plugin matched premium author: {$pa}", $plugin_slug);
                    return true;
                }
            }
            
            // Prüfe auf "Core" im Namen mit Theme-Author
            if (strpos($name, 'core') !== false &&
                (strpos($author, 'theme') !== false || 
                 strpos($author, 'qode') !== false ||
                 strpos($author, 'starter') !== false)) {
                $this->debug_log("Plugin is a core plugin from theme author", $plugin_slug);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Prüft den Upgrader-Skin-Typ
     */
    private function get_upgrader_source($upgrader) {
        if (!isset($upgrader->skin)) {
            return 'unknown';
        }
        
        $skin_class = get_class($upgrader->skin);
        
        $this->debug_log("Checking upgrader skin class", $skin_class);
        
        // TGMPA Skins
        if (strpos($skin_class, 'TGM') !== false || 
            strpos($skin_class, 'TGMPA') !== false) {
            return 'tgmpa';
        }
        
        // Bulk Upgrader (oft von TGMPA verwendet)
        if (strpos($skin_class, 'Bulk') !== false) {
            return 'bulk';
        }
        
        // Envato Market
        if (strpos($skin_class, 'Envato') !== false) {
            return 'envato';
        }
        
        // Standard WordPress
        if (strpos($skin_class, 'WP_Ajax') !== false) {
            return 'ajax';
        }
        
        if (strpos($skin_class, 'Plugin_Upgrader_Skin') !== false) {
            return 'standard';
        }
        
        return 'other';
    }
    
    /**
     * Check for Envato Market updates
     */
    public function check_envato_update($upgrader, $options) {
        if (!class_exists('Envato_Market')) {
            return;
        }
        
        if ($options['action'] !== 'update' || $options['type'] !== 'plugin') {
            return;
        }
        
        $plugins = $options['plugins'] ?? [];
        foreach ($plugins as $plugin) {
            if ($this->is_envato_market_plugin($plugin)) {
                $this->log_theme_plugin_update($plugin, 'envato');
            }
        }
    }
    
    /**
     * Check if a plugin is managed by Envato Market
     */
    private function is_envato_market_plugin($plugin_slug) {
        if (!function_exists('envato_market')) {
            return false;
        }
        
        $envato = envato_market();
        if (!$envato || !method_exists($envato, 'plugins')) {
            return false;
        }
        
        $envato_plugins = $envato->plugins()->get_plugins();
        $base_slug = explode('/', $plugin_slug)[0];
        
        foreach ($envato_plugins as $envato_plugin) {
            if (isset($envato_plugin['slug']) && $envato_plugin['slug'] === $base_slug) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log Envato plugin update
     */
    public function log_envato_plugin_update($plugin_slug, $plugin_data = []) {
        $this->log_theme_plugin_update($plugin_slug, 'envato', $plugin_data);
    }
    
    /**
     * Log Envato theme update
     */
    public function log_envato_theme_update($theme_slug, $theme_data = []) {
        $logs = get_option($this->option_key, []);
        $theme = wp_get_theme($theme_slug);
        
        $logs[] = [
            'type' => 'theme',
            'timestamp' => current_time('mysql'),
            'item_name' => $theme->get('Name') ?? $theme_slug,
            'item_slug' => $theme_slug,
            'old_version' => $theme_data['old_version'] ?? '',
            'new_version' => $theme->get('Version') ?? '',
            'source' => 'envato',
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
    }
    
    /**
     * Log Slider Revolution update
     */
    public function log_slider_revolution_update() {
        $version = defined('RS_REVISION') ? RS_REVISION : 'unknown';
        $this->log_theme_plugin_update('revslider/revslider.php', 'slider-revolution', [
            'name' => 'Slider Revolution',
            'new_version' => $version,
        ]);
    }
    
    /**
     * Log LayerSlider update
     */
    public function log_layerslider_update() {
        $version = defined('LS_PLUGIN_VERSION') ? LS_PLUGIN_VERSION : 'unknown';
        $this->log_theme_plugin_update('LayerSlider/layerslider.php', 'layerslider', [
            'name' => 'LayerSlider',
            'new_version' => $version,
        ]);
    }
    
    /**
     * Central function to log theme plugin updates
     */
    private function log_theme_plugin_update($plugin_slug, $source = 'theme', $extra_data = []) {
        $logs = get_option($this->option_key, []);
        
        // Get plugin data
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_slug;
        if (file_exists($plugin_path)) {
            $plugin_data = get_plugin_data($plugin_path);
            $name = $plugin_data['Name'] ?? $plugin_slug;
            $new_version = $plugin_data['Version'] ?? '';
        } else {
            $name = $extra_data['name'] ?? $plugin_slug;
            $new_version = $extra_data['new_version'] ?? '';
        }
        
        // Get old version from pre_update_versions if available
        $old_version = $this->pre_update_versions['plugin'][$plugin_slug] ?? ($extra_data['old_version'] ?? '');
        
        // Prevent duplicate entries within 10 seconds
        $recent_log = end($logs);
        if (
            $recent_log && 
            $recent_log['item_slug'] === $plugin_slug &&
            (time() - strtotime($recent_log['timestamp'])) < 10
        ) {
            return; // Skip duplicate
        }
        
        $logs[] = [
            'type' => 'theme_plugin',
            'timestamp' => current_time('mysql'),
            'item_name' => $name,
            'item_slug' => $plugin_slug,
            'old_version' => $old_version,
            'new_version' => $new_version,
            'source' => $source,
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        $this->debug_log('Theme plugin update logged', ['name' => $name, 'source' => $source]);
    }
    
    /**
     * Capture plugin/theme versions BEFORE update starts
     */
    public function capture_pre_update_versions($response, $hook_extra) {
        // Für Plugins
        if (isset($hook_extra['plugin'])) {
            $plugin = $hook_extra['plugin'];
            $plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
            if (file_exists($plugin_file)) {
                $plugin_data = get_plugin_data($plugin_file);
                // NUR setzen wenn noch nicht vorhanden
                if (!isset($this->pre_update_versions['plugin'][$plugin]) || empty($this->pre_update_versions['plugin'][$plugin])) {
                    $this->pre_update_versions['plugin'][$plugin] = $plugin_data['Version'] ?? '';
                }
            }
        }
        
        // Für Themes
        if (isset($hook_extra['theme'])) {
            $theme_slug = $hook_extra['theme'];
            $theme = wp_get_theme($theme_slug);
            if ($theme->exists()) {
                if (!isset($this->pre_update_versions['theme'][$theme_slug]) || empty($this->pre_update_versions['theme'][$theme_slug])) {
                    $this->pre_update_versions['theme'][$theme_slug] = $theme->get('Version') ?? '';
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Capture versions from package options
     */
    public function capture_package_versions($options) {
        // Für Core-Updates die aktuelle WP-Version speichern
        if (isset($options['hook_extra']['type']) && $options['hook_extra']['type'] === 'core') {
            global $wp_version;
            $this->pre_update_versions['core']['wordpress'] = $wp_version;
        }
        
        // Plugin-Versionen aus dem Package erfassen
        if (isset($options['hook_extra']['plugin'])) {
            $plugin = $options['hook_extra']['plugin'];
            $plugin_file = WP_PLUGIN_DIR . '/' . $plugin;
            if (file_exists($plugin_file) && (!isset($this->pre_update_versions['plugin'][$plugin]) || empty($this->pre_update_versions['plugin'][$plugin]))) {
                $plugin_data = get_plugin_data($plugin_file);
                $this->pre_update_versions['plugin'][$plugin] = $plugin_data['Version'] ?? '';
            }
        }
        
        // Theme-Versionen aus dem Package erfassen
        if (isset($options['hook_extra']['theme'])) {
            $theme_slug = $options['hook_extra']['theme'];
            if (!isset($this->pre_update_versions['theme'][$theme_slug]) || empty($this->pre_update_versions['theme'][$theme_slug])) {
                $theme = wp_get_theme($theme_slug);
                if ($theme->exists()) {
                    $this->pre_update_versions['theme'][$theme_slug] = $theme->get('Version') ?? '';
                }
            }
        }
        
        return $options;
    }
    
    /**
     * Register hooks for database updates from various plugins
     */
    private function register_database_update_hooks() {
        // Elementor Database Upgrades
        add_action('elementor/core/upgrade/after', [$this, 'log_elementor_db_update'], 10, 2);
        
        // WooCommerce Database Updates
        add_action('woocommerce_db_update_routine', [$this, 'log_woocommerce_db_update'], 10, 1);
        add_action('woocommerce_updated', [$this, 'log_woocommerce_updated']);
        
        // WordPress Core Database Update
        add_action('wp_upgrade', [$this, 'log_wp_db_upgrade']);
        
        // WPML Database Updates
        add_action('wpml_after_update', [$this, 'log_wpml_db_update']);
        
        // Yoast SEO Database Updates
        add_action('wpseo_run_upgrade', [$this, 'log_yoast_db_update']);
        
        // ACF Database Updates
        add_action('acf/upgrade_complete', [$this, 'log_acf_db_update']);
        
        // Contact Form 7 Updates
        add_action('wpcf7_upgrade', [$this, 'log_cf7_db_update']);
        
        // Generic database version option updates
        add_action('update_option', [$this, 'detect_db_version_update'], 10, 3);
    }
    
    /**
     * Log Elementor database upgrade
     */
    public function log_elementor_db_update($upgrader = null, $data = null) {
        $version = defined('ELEMENTOR_VERSION') ? ELEMENTOR_VERSION : 'unknown';
        $this->log_database_update('Elementor', 'elementor', $version);
    }
    
    /**
     * Log WooCommerce database update routine
     */
    public function log_woocommerce_db_update($version) {
        $this->log_database_update('WooCommerce', 'woocommerce', $version);
    }
    
    /**
     * Log WooCommerce updated event
     */
    public function log_woocommerce_updated() {
        $version = defined('WC_VERSION') ? WC_VERSION : 'unknown';
        $this->log_database_update('WooCommerce', 'woocommerce', $version);
    }
    
    /**
     * Log WordPress database upgrade
     */
    public function log_wp_db_upgrade() {
        global $wp_db_version;
        $this->log_database_update('WordPress Core', 'wordpress', 'DB v' . $wp_db_version);
    }
    
    /**
     * Log WPML database update
     */
    public function log_wpml_db_update() {
        $version = defined('ICL_SITEPRESS_VERSION') ? ICL_SITEPRESS_VERSION : 'unknown';
        $this->log_database_update('WPML', 'wpml', $version);
    }
    
    /**
     * Log Yoast SEO database update
     */
    public function log_yoast_db_update() {
        $version = defined('WPSEO_VERSION') ? WPSEO_VERSION : 'unknown';
        $this->log_database_update('Yoast SEO', 'yoast-seo', $version);
    }
    
    /**
     * Log ACF database update
     */
    public function log_acf_db_update() {
        $version = defined('ACF_VERSION') ? ACF_VERSION : 'unknown';
        $this->log_database_update('Advanced Custom Fields', 'acf', $version);
    }
    
    /**
     * Log Contact Form 7 database update
     */
    public function log_cf7_db_update() {
        $version = defined('WPCF7_VERSION') ? WPCF7_VERSION : 'unknown';
        $this->log_database_update('Contact Form 7', 'contact-form-7', $version);
    }
    
    /**
     * Detect database version updates via option changes
     */
    public function detect_db_version_update($option, $old_value, $new_value) {
        $db_version_options = [
            'elementor_version' => 'Elementor',
            'woocommerce_db_version' => 'WooCommerce',
            'wpml_db_version' => 'WPML',
            'wpseo_db_version' => 'Yoast SEO',
            'acf_db_version' => 'Advanced Custom Fields',
            'gravityforms_db_version' => 'Gravity Forms',
            'revslider_db_version' => 'Slider Revolution',
            'wc_subscriptions_db_version' => 'WooCommerce Subscriptions',
            'wp_mail_smtp_db_version' => 'WP Mail SMTP',
            'monsterinsights_db_version' => 'MonsterInsights',
            'elementor_pro_version' => 'Elementor Pro',
            'rank_math_db_version' => 'Rank Math SEO',
            'updraftplus_version' => 'UpdraftPlus',
            'wordfence_db_version' => 'Wordfence Security',
        ];
        
        if (isset($db_version_options[$option]) && $old_value !== $new_value) {
            $plugin_name = $db_version_options[$option];
            $slug = sanitize_title($plugin_name);
            $this->log_database_update($plugin_name, $slug, $new_value, $old_value);
        }
        
        // Generic detection for options ending in _db_version
        if (
            (str_ends_with($option, '_db_version') || str_ends_with($option, '_database_version')) 
            && $old_value !== $new_value 
            && !isset($db_version_options[$option])
        ) {
            $plugin_name = str_replace(['_db_version', '_database_version', '_'], ['', '', ' '], $option);
            $plugin_name = ucwords($plugin_name);
            $slug = sanitize_title($plugin_name);
            $this->log_database_update($plugin_name, $slug, $new_value, $old_value);
        }
    }
    
    /**
     * Central function to log database updates
     */
    private function log_database_update($item_name, $item_slug, $new_version, $old_version = '') {
        $logs = get_option($this->option_key, []);
        
        // Prevent duplicate entries within 5 seconds
        $recent_log = end($logs);
        if (
            $recent_log && 
            $recent_log['type'] === 'database' && 
            $recent_log['item_slug'] === $item_slug &&
            (time() - strtotime($recent_log['timestamp'])) < 5
        ) {
            return;
        }
        
        $logs[] = [
            'type' => 'database',
            'timestamp' => current_time('mysql'),
            'item_name' => $item_name,
            'item_slug' => $item_slug,
            'old_version' => $old_version,
            'new_version' => $new_version,
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
    }
    
    /**
     * Generate API key if it doesn't exist
     */
    public function generate_api_key_if_missing() {
        $existing_key = get_option($this->api_key_option);
        if (empty($existing_key)) {
            $this->regenerate_api_key();
        }
    }
    
    /**
     * Generate a new API key
     */
    private function regenerate_api_key() {
        $new_key = 'ulapi_' . bin2hex(random_bytes(24));
        update_option($this->api_key_option, $new_key);
        return $new_key;
    }
    
    /**
     * Enqueue admin scripts for copy functionality
     */
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'tools_page_update-logger') {
            return;
        }
        
        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                $("#copy-api-key").on("click", function() {
                    var apiKey = $("#api-key-display").text();
                    navigator.clipboard.writeText(apiKey).then(function() {
                        var btn = $("#copy-api-key");
                        var originalText = btn.text();
                        btn.text("✓ Kopiert!");
                        btn.prop("disabled", true);
                        setTimeout(function() {
                            btn.text(originalText);
                            btn.prop("disabled", false);
                        }, 2000);
                    });
                });
                
                $("#regenerate-api-key").on("click", function() {
                    if (confirm("Möchten Sie wirklich einen neuen API-Key generieren? Der alte Key wird ungültig.")) {
                        $(this).prop("disabled", true).text("Wird generiert...");
                        $.post(ajaxurl, {
                            action: "regenerate_update_logger_key",
                            nonce: "' . wp_create_nonce('regenerate_api_key') . '"
                        }, function(response) {
                            if (response.success) {
                                $("#api-key-display").text(response.data.key);
                                $("#regenerate-api-key").prop("disabled", false).text("Neuen Key generieren");
                            }
                        });
                    }
                });
                
                $("#test-db-log").on("click", function() {
                    $(this).prop("disabled", true).text("Erstelle Test-Eintrag...");
                    $.post(ajaxurl, {
                        action: "test_database_log",
                        nonce: "' . wp_create_nonce('test_database_log') . '"
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("Fehler: " + response.data.message);
                        }
                        $("#test-db-log").prop("disabled", false).text("🧪 Test DB-Eintrag erstellen");
                    });
                });
                
                $("#test-theme-plugin-log").on("click", function() {
                    $(this).prop("disabled", true).text("Erstelle Test-Eintrag...");
                    $.post(ajaxurl, {
                        action: "test_theme_plugin_log",
                        nonce: "' . wp_create_nonce('test_theme_plugin_log') . '"
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("Fehler: " + response.data.message);
                        }
                        $("#test-theme-plugin-log").prop("disabled", false).text("🧪 Test Theme-Plugin-Eintrag");
                    });
                });
                
                $(".delete-log-entry").on("click", function() {
                    var index = $(this).data("index");
                    var itemName = $(this).data("name");
                    
                    if (confirm("Möchten Sie den Eintrag \"" + itemName + "\" wirklich löschen?")) {
                        var btn = $(this);
                        btn.prop("disabled", true).text("...");
                        $.post(ajaxurl, {
                            action: "delete_update_log_entry",
                            nonce: "' . wp_create_nonce('delete_log_entry') . '",
                            index: index
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert("Fehler: " + response.data.message);
                                btn.prop("disabled", false).text("🗑️");
                            }
                        });
                    }
                });
                
                $("#delete-all-logs").on("click", function() {
                    if (confirm("Möchten Sie wirklich ALLE Update-Einträge löschen? Diese Aktion kann nicht rückgängig gemacht werden!")) {
                        var btn = $(this);
                        btn.prop("disabled", true).text("Wird gelöscht...");
                        $.post(ajaxurl, {
                            action: "delete_all_update_logs",
                            nonce: "' . wp_create_nonce('delete_all_logs') . '"
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert("Fehler: " + response.data.message);
                                btn.prop("disabled", false).text("🗑️ Alle Einträge löschen");
                            }
                        });
                    }
                });
                
                $("#add-maintenance-entry").on("click", function() {
                    var title = $("#maintenance-title").val().trim();
                    var description = $("#maintenance-description").val().trim();
                    var hours = parseInt($("#maintenance-hours").val()) || 0;
                    var minutes = parseInt($("#maintenance-minutes").val()) || 0;
                    
                    if (!title) {
                        alert("Bitte geben Sie einen Titel ein.");
                        return;
                    }
                    
                    var totalMinutes = (hours * 60) + minutes;
                    
                    $(this).prop("disabled", true).text("Wird gespeichert...");
                    $.post(ajaxurl, {
                        action: "add_manual_maintenance_entry",
                        nonce: "' . wp_create_nonce('add_maintenance_entry') . '",
                        title: title,
                        description: description,
                        time_spent_minutes: totalMinutes
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert("Fehler: " + response.data.message);
                            $("#add-maintenance-entry").prop("disabled", false).text("💾 Eintrag speichern");
                        }
                    });
                });
            });
        ');
    }
    
    /**
     * Log update events - HAUPTFUNKTION mit verbesserter Theme-Plugin-Erkennung und Version-Tracking
     */
    public function log_update($upgrader, $options) {
        $this->debug_log('log_update called', [
            'options' => $options,
            'upgrader_class' => get_class($upgrader),
            'skin_class' => isset($upgrader->skin) ? get_class($upgrader->skin) : 'none'
        ]);
        
        if (!isset($options['action']) || $options['action'] !== 'update') {
            $this->debug_log('Skipping: not an update action');
            return;
        }
        
        $logs = get_option($this->option_key, []);
        $timestamp = current_time('mysql');
        $upgrader_source = $this->get_upgrader_source($upgrader);
        
        $this->debug_log("Update type: {$options['type']}, source: {$upgrader_source}");
        
        // Lade gespeicherte Versionen aus dem Transient (falls noch nicht geladen)
        $saved_versions = get_transient($this->versions_transient_key);
        if ($saved_versions && is_array($saved_versions)) {
            // Nur übernehmen wenn wir sie noch nicht haben
            foreach ($saved_versions as $type => $versions) {
                if (!isset($this->pre_update_versions[$type])) {
                    $this->pre_update_versions[$type] = [];
                }
                foreach ($versions as $key => $version) {
                    if (!isset($this->pre_update_versions[$type][$key]) || empty($this->pre_update_versions[$type][$key])) {
                        $this->pre_update_versions[$type][$key] = $version;
                    }
                }
            }
        }
        
        $this->debug_log('Pre-update versions available', $this->pre_update_versions);
        
        switch ($options['type']) {
            case 'plugin':
                if (!function_exists('get_plugins')) {
                    require_once ABSPATH . 'wp-admin/includes/plugin.php';
                }
                
                $plugins = [];
                
                // Plugins aus verschiedenen Quellen ermitteln
                if (isset($options['plugins']) && is_array($options['plugins'])) {
                    $plugins = $options['plugins'];
                } elseif (isset($options['plugin'])) {
                    $plugins = [$options['plugin']];
                } elseif (isset($upgrader->result) && isset($upgrader->result['destination_name'])) {
                    $plugins = [$upgrader->result['destination_name']];
                }
                
                $this->debug_log("Processing plugin updates", $plugins);
                
                if (empty($plugins)) {
                    $this->debug_log("No plugins found to process");
                    break;
                }
                
                // WICHTIG: Plugin-Daten NACH dem Update laden (für neue Version)
                // WordPress hat die Dateien bereits aktualisiert wenn dieser Hook ausgeführt wird
                wp_cache_flush();
                $all_plugins = get_plugins();
                
                foreach ($plugins as $plugin) {
                    $plugin_file = $plugin;
                    $plugin_slug = dirname($plugin);
                    
                    if ($plugin_slug === '.') {
                        $plugin_slug = basename($plugin, '.php');
                    }
                    
                    $this->debug_log("Processing plugin", [
                        'file' => $plugin_file,
                        'slug' => $plugin_slug
                    ]);
                    
                    // Plugin-Daten finden (NEUE Version nach Update)
                    $plugin_data = null;
                    $found_plugin_file = null;
                    foreach ($all_plugins as $file => $data) {
                        if (strpos($file, $plugin_slug) !== false || $file === $plugin_file) {
                            $plugin_data = $data;
                            $found_plugin_file = $file;
                            break;
                        }
                    }
                    
                    if (!$plugin_data) {
                        // Fallback: Direkt aus Datei lesen
                        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin;
                        if (file_exists($plugin_path)) {
                            $plugin_data = get_plugin_data($plugin_path);
                            $found_plugin_file = $plugin;
                        }
                    }
                    
                    if (!$plugin_data) {
                        $this->debug_log("Plugin data not found for", $plugin_slug);
                        continue;
                    }
                    
                    // Quelle bestimmen
                    $is_tgmpa = $this->is_tgmpa_managed_plugin($plugin) || 
                                $upgrader_source === 'tgmpa' || 
                                $upgrader_source === 'bulk';
                    $is_theme_plugin = $this->is_theme_bundled_plugin($plugin, $plugin_data);
                    
                    if ($is_tgmpa) {
                        $final_source = 'tgmpa';
                        $type = 'theme_plugin';
                    } elseif ($is_theme_plugin) {
                        $final_source = 'bundled';
                        $type = 'theme_plugin';
                    } else {
                        $final_source = 'wordpress';
                        $type = 'plugin';
                    }
                    
                    // ALTE Version aus gespeicherten Daten ermitteln
                    $old_version = '';
                    
                    // Verschiedene Schlüssel probieren
                    $keys_to_try = [
                        $plugin_file,
                        $plugin,
                        $found_plugin_file,
                        $plugin_slug . '/' . $plugin_slug . '.php',
                    ];
                    
                    foreach ($keys_to_try as $key) {
                        if (!empty($this->pre_update_versions['plugin'][$key])) {
                            $old_version = $this->pre_update_versions['plugin'][$key];
                            $this->debug_log("Found old version via key: $key = $old_version");
                            break;
                        }
                    }
                    
                    // Auch nach Slug suchen
                    if (empty($old_version) && !empty($this->pre_update_versions['plugin_by_slug'][$plugin_slug])) {
                        $old_version = $this->pre_update_versions['plugin_by_slug'][$plugin_slug];
                        $this->debug_log("Found old version via slug: $plugin_slug = $old_version");
                    }
                    
                    // NEUE Version aus den aktuellen Plugin-Daten
                    $new_version = $plugin_data['Version'] ?? '';
                    
                    // Wenn alte und neue Version gleich sind, haben wir ein Problem
                    // Dies sollte nicht passieren wenn wir die Versionen früh genug gespeichert haben
                    if ($old_version === $new_version && !empty($old_version)) {
                        $this->debug_log("WARNING: Old and new version are the same!", [
                            'plugin' => $plugin_slug,
                            'version' => $old_version
                        ]);
                    }
                    
                    $log_entry = [
                        'type' => $type,
                        'timestamp' => $timestamp,
                        'item_name' => $plugin_data['Name'] ?? $plugin,
                        'item_slug' => $found_plugin_file ?? $plugin_file,
                        'old_version' => $old_version,
                        'new_version' => $new_version,
                        'source' => $final_source,
                    ];
                    
                    $logs[] = $log_entry;
                    
                    $this->debug_log("Plugin update logged", $log_entry);
                }
                break;
                
            case 'theme':
                $themes = [];
                
                if (isset($options['themes']) && is_array($options['themes'])) {
                    $themes = $options['themes'];
                } elseif (isset($options['theme'])) {
                    $themes = [$options['theme']];
                }
                
                foreach ($themes as $theme_slug) {
                    $theme = wp_get_theme($theme_slug);
                    $old_version = $this->pre_update_versions['theme'][$theme_slug] ?? '';
                    
                    $logs[] = [
                        'type' => 'theme',
                        'timestamp' => $timestamp,
                        'item_name' => $theme->get('Name') ?? $theme_slug,
                        'item_slug' => $theme_slug,
                        'old_version' => $old_version,
                        'new_version' => $theme->get('Version') ?? '',
                    ];
                }
                break;
                
            case 'core':
                global $wp_version;
                $old_version = $this->pre_update_versions['core']['wordpress'] ?? '';
                
                $logs[] = [
                    'type' => 'core',
                    'timestamp' => $timestamp,
                    'item_name' => 'WordPress Core',
                    'item_slug' => 'wordpress',
                    'old_version' => $old_version,
                    'new_version' => $wp_version,
                ];
                break;
        }
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        // Pre-update versions zurücksetzen und Transient löschen nach dem Logging
        $this->pre_update_versions = [];
        delete_transient($this->versions_transient_key);
        delete_transient($this->versions_transient_key . '_time');
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        register_rest_route('update-logger/v1', '/logs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_logs'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);
        
        register_rest_route('update-logger/v1', '/manual-entry', [
            'methods' => 'POST',
            'callback' => [$this, 'add_manual_entry'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);
        
        register_rest_route('update-logger/v1', '/debug', [
            'methods' => 'GET',
            'callback' => [$this, 'get_debug_info'],
            'permission_callback' => [$this, 'check_api_key'],
        ]);
    }
    
    /**
     * Check API key from header
     */
    public function check_api_key($request) {
        $provided_key = $request->get_header('X-API-Key');
        if (!$provided_key) {
            $provided_key = $request->get_param('api_key');
        }
        $stored_key = get_option($this->api_key_option);
        
        if (empty($stored_key)) {
            return new WP_Error('no_api_key', 'API key not configured', ['status' => 500]);
        }
        
        return hash_equals($stored_key, $provided_key ?? '');
    }
    
    /**
     * Get logs via REST API
     */
    public function get_logs($request) {
        $logs = get_option($this->option_key, []);
        $since = $request->get_param('since');
        $type = $request->get_param('type');
        $source = $request->get_param('source');
        $limit = $request->get_param('limit') ?: 100;
        
        if ($since) {
            $since_time = strtotime($since);
            $logs = array_filter($logs, function($log) use ($since_time) {
                return strtotime($log['timestamp']) > $since_time;
            });
        }
        
        if ($type) {
            $logs = array_filter($logs, function($log) use ($type) {
                return ($log['type'] ?? '') === $type;
            });
        }
        
        if ($source) {
            $logs = array_filter($logs, function($log) use ($source) {
                return ($log['source'] ?? '') === $source;
            });
        }
        
        $logs = array_slice(array_values($logs), 0, (int)$limit);
        
        return rest_ensure_response([
            'success' => true,
            'logs' => array_values($logs),
            'total' => count($logs),
        ]);
    }
    
    /**
     * Get debug info via REST API
     */
    public function get_debug_info($request) {
        $info = [
            'plugin_version' => '1.8.0',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'debug_mode' => UPDATE_LOGGER_DEBUG,
            'tgmpa_active' => class_exists('TGM_Plugin_Activation'),
            'current_theme' => get_template(),
            'log_count' => count(get_option($this->option_key, [])),
            'pre_update_versions_cached' => count($this->pre_update_versions),
            'tgmpa_updates_tracked' => count($this->tgmpa_updates),
        ];
        
        if (class_exists('TGM_Plugin_Activation') && isset($GLOBALS['tgmpa'])) {
            $tgmpa = $GLOBALS['tgmpa'];
            $info['tgmpa_plugins'] = array_keys($tgmpa->plugins ?? []);
        }
        
        return rest_ensure_response([
            'success' => true,
            'debug' => $info,
        ]);
    }
    
    /**
     * Add a manual maintenance entry from external dashboard (REST API)
     */
    public function add_manual_entry($request) {
        $params = $request->get_json_params();
        
        $logs = get_option($this->option_key, []);
        $logs[] = [
            'type' => 'maintenance',
            'timestamp' => current_time('mysql'),
            'item_name' => sanitize_text_field($params['title'] ?? 'Wartungsarbeit'),
            'item_slug' => 'manual-entry-' . time(),
            'old_version' => '',
            'new_version' => '',
            'description' => sanitize_textarea_field($params['description'] ?? ''),
            'time_spent_minutes' => intval($params['time_spent_minutes'] ?? 0),
            'created_from_dashboard' => true,
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        return rest_ensure_response([
            'success' => true,
            'message' => 'Manual entry added successfully'
        ]);
    }
    
    /**
     * AJAX: Regenerate API key
     */
    public function ajax_regenerate_key() {
        check_ajax_referer('regenerate_api_key', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $new_key = $this->regenerate_api_key();
        wp_send_json_success(['key' => $new_key]);
    }
    
    /**
     * AJAX: Test database log
     */
    public function ajax_test_database_log() {
        check_ajax_referer('test_database_log', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $this->log_database_update('Test Plugin', 'test-plugin', '2.0.0', '1.0.0');
        wp_send_json_success(['message' => 'Test-Eintrag erstellt']);
    }
    
    /**
     * AJAX: Test theme plugin log
     */
    public function ajax_test_theme_plugin_log() {
        check_ajax_referer('test_theme_plugin_log', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $logs = get_option($this->option_key, []);
        $logs[] = [
            'type' => 'theme_plugin',
            'timestamp' => current_time('mysql'),
            'item_name' => 'Test Theme Plugin',
            'item_slug' => 'test-theme-plugin/test.php',
            'old_version' => '1.0.0',
            'new_version' => '2.0.0',
            'source' => 'tgmpa',
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        wp_send_json_success(['message' => 'Test Theme-Plugin-Eintrag erstellt']);
    }
    
    /**
     * AJAX: Delete single log entry
     */
    public function ajax_delete_log_entry() {
        check_ajax_referer('delete_log_entry', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $index = intval($_POST['index']);
        $logs = get_option($this->option_key, []);
        
        if (isset($logs[$index])) {
            array_splice($logs, $index, 1);
            update_option($this->option_key, $logs);
            wp_send_json_success(['message' => 'Eintrag gelöscht']);
        } else {
            wp_send_json_error(['message' => 'Eintrag nicht gefunden']);
        }
    }
    
    /**
     * AJAX: Delete all logs
     */
    public function ajax_delete_all_logs() {
        check_ajax_referer('delete_all_logs', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        update_option($this->option_key, []);
        wp_send_json_success(['message' => 'Alle Einträge gelöscht']);
    }
    
    /**
     * AJAX: Add manual maintenance entry
     */
    public function ajax_add_maintenance_entry() {
        check_ajax_referer('add_maintenance_entry', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description'] ?? '');
        $time_spent = intval($_POST['time_spent_minutes'] ?? 0);
        
        if (empty($title)) {
            wp_send_json_error(['message' => 'Titel ist erforderlich']);
        }
        
        $logs = get_option($this->option_key, []);
        $logs[] = [
            'type' => 'maintenance',
            'timestamp' => current_time('mysql'),
            'item_name' => $title,
            'item_slug' => 'maintenance-' . time(),
            'old_version' => '',
            'new_version' => '',
            'description' => $description,
            'time_spent_minutes' => $time_spent,
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        wp_send_json_success(['message' => 'Wartungseintrag erstellt']);
    }
    
    /**
     * Helper function to format time spent
     */
    private function format_time_spent($minutes) {
        if (!$minutes || $minutes <= 0) {
            return '';
        }
        
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        
        if ($hours > 0 && $mins > 0) {
            return sprintf('%dh %dmin', $hours, $mins);
        } elseif ($hours > 0) {
            return sprintf('%dh', $hours);
        } else {
            return sprintf('%dmin', $mins);
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Update Logger API',
            'Update Logger',
            'manage_options',
            'update-logger',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin settings page - zweispaltiges Layout
     */
    public function render_admin_page() {
        $api_key = get_option($this->api_key_option);
        $logs = get_option($this->option_key, []);
        $site_url = get_site_url();
        
        // Count by type including maintenance and theme_plugin
        $type_counts = [
            'plugin' => 0,
            'theme' => 0,
            'core' => 0,
            'database' => 0,
            'maintenance' => 0,
            'theme_plugin' => 0,
        ];
        $total_maintenance_minutes = 0;
        
        foreach ($logs as $log) {
            $type = $log['type'] ?? 'unknown';
            if (isset($type_counts[$type])) {
                $type_counts[$type]++;
            }
            if ($type === 'maintenance' && isset($log['time_spent_minutes'])) {
                $total_maintenance_minutes += intval($log['time_spent_minutes']);
            }
        }
        ?>
        <style>
            .ul-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1400px; margin-top: 20px; }
            .ul-card { background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; }
            .ul-card-full { grid-column: 1 / -1; }
            @media (max-width: 1024px) { .ul-grid { grid-template-columns: 1fr; } }
        </style>
        <div class="wrap">
            <h1>🔧 Update Logger API <small style="font-size: 12px; color: #666;">v1.8.0</small></h1>
            
            <!-- ZEILE 1: Statistik | Wartungsarbeit -->
            <div class="ul-grid">
                <!-- Statistik -->
                <div class="ul-card">
                    <h2 style="margin-top: 0;">📊 Statistik</h2>
                    
                    <div style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 15px;">
                        <div style="background: #e3f2fd; padding: 12px 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 22px; font-weight: bold; color: #1976d2;"><?php echo $type_counts['plugin']; ?></div>
                            <div style="color: #1976d2; font-size: 12px;">🔌 Plugins</div>
                        </div>
                        <div style="background: #fce4ec; padding: 12px 20px; border-radius: 8px; text-align: center; border: 2px solid #e91e63;">
                            <div style="font-size: 22px; font-weight: bold; color: #c2185b;"><?php echo $type_counts['theme_plugin']; ?></div>
                            <div style="color: #c2185b; font-size: 12px;">🎨 Theme-Plugins</div>
                        </div>
                        <div style="background: #e8f5e9; padding: 12px 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 22px; font-weight: bold; color: #388e3c;"><?php echo $type_counts['theme']; ?></div>
                            <div style="color: #388e3c; font-size: 12px;">🎨 Themes</div>
                        </div>
                        <div style="background: #fff3e0; padding: 12px 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 22px; font-weight: bold; color: #f57c00;"><?php echo $type_counts['core']; ?></div>
                            <div style="color: #f57c00; font-size: 12px;">⚙️ Core</div>
                        </div>
                        <div style="background: #f3e5f5; padding: 12px 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 22px; font-weight: bold; color: #7b1fa2;"><?php echo $type_counts['database']; ?></div>
                            <div style="color: #7b1fa2; font-size: 12px;">🗄️ Datenbank</div>
                        </div>
                        <div style="background: #e0f2f1; padding: 12px 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 22px; font-weight: bold; color: #00695c;"><?php echo $type_counts['maintenance']; ?></div>
                            <div style="color: #00695c; font-size: 12px;">🔧 Wartung</div>
                        </div>
                    </div>
                    
                    <?php if ($total_maintenance_minutes > 0): ?>
                    <p style="color: #666; margin-bottom: 5px;"><strong>Gesamt Wartungszeit:</strong> <?php echo $this->format_time_spent($total_maintenance_minutes); ?></p>
                    <?php endif; ?>
                    <p style="color: #666; margin: 0;"><strong>Gesamt:</strong> <?php echo count($logs); ?> Einträge</p>
                </div>
                
                <!-- Manuelle Wartungsarbeit eintragen -->
                <div class="ul-card" style="background: #fff9e6; border-left: 4px solid #f0c14b;">
                    <h2 style="margin-top: 0;">🔧 Manuelle Wartungsarbeit eintragen</h2>
                    <p style="color: #666; font-size: 13px;">Individuelle Wartungsarbeiten dokumentieren.</p>
                    
                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th scope="row" style="padding: 8px 10px 8px 0;"><label for="maintenance-title">Titel *</label></th>
                            <td style="padding: 8px 0;">
                                <input type="text" id="maintenance-title" class="regular-text" placeholder="z.B. Kontaktformular repariert" style="width: 100%;">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 8px 10px 8px 0;"><label for="maintenance-description">Beschreibung</label></th>
                            <td style="padding: 8px 0;">
                                <textarea id="maintenance-description" class="large-text" rows="2" placeholder="Optionale Details..."></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 8px 10px 8px 0;"><label>Zeit</label></th>
                            <td style="padding: 8px 0;">
                                <div style="display: flex; gap: 10px; align-items: center;">
                                    <input type="number" id="maintenance-hours" min="0" max="99" value="0" style="width: 60px;"> h
                                    <input type="number" id="maintenance-minutes" min="0" max="59" value="0" style="width: 60px;"> min
                                </div>
                            </td>
                        </tr>
                    </table>
                    
                    <button type="button" id="add-maintenance-entry" class="button button-primary" style="margin-top: 10px;">💾 Eintrag speichern</button>
                </div>
            </div>
            
            <!-- ZEILE 2: Letzte Updates | API-Konfiguration -->
            <div class="ul-grid">
                <!-- Letzte Updates -->
                <div class="ul-card">
                    <h2 style="margin-top: 0;">📋 Letzte Updates</h2>
                
                <?php if (empty($logs)): ?>
                    <p style="color: #666;">Noch keine Updates protokolliert.</p>
                <?php else: ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th>Typ</th>
                                <th>Name</th>
                                <th>Version</th>
                                <th>Quelle</th>
                                <th>Datum</th>
                                <th>Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $reversed_logs = array_reverse($logs, true);
                            $count = 0;
                            foreach ($reversed_logs as $index => $log): 
                                if ($count >= 50) break;
                                $count++;
                                
                                $type_labels = [
                                    'plugin' => '🔌 Plugin',
                                    'theme' => '🎨 Theme',
                                    'core' => '⚙️ Core',
                                    'database' => '🗄️ Datenbank',
                                    'maintenance' => '🔧 Wartung',
                                    'theme_plugin' => '🎨 Theme-Plugin',
                                ];
                                $type_label = $type_labels[$log['type']] ?? $log['type'];
                            ?>
                            <tr>
                                <td>
                                    <?php echo esc_html($type_label); ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html($log['item_name']); ?></strong>
                                    <?php if ($log['type'] === 'maintenance' && !empty($log['description'])): ?>
                                        <br><small style="color: #666;"><?php echo esc_html($log['description']); ?></small>
                                    <?php endif; ?>
                                    <?php if ($log['type'] === 'maintenance' && !empty($log['time_spent_minutes'])): ?>
                                        <br><small style="color: #1976d2;">⏱️ <?php echo $this->format_time_spent($log['time_spent_minutes']); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['old_version']) && !empty($log['new_version'])): ?>
                                        <?php echo esc_html($log['old_version']); ?> → <?php echo esc_html($log['new_version']); ?>
                                    <?php elseif (!empty($log['new_version'])): ?>
                                        → <?php echo esc_html($log['new_version']); ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($log['source'])): ?>
                                        <span style="background: #e0e0e0; padding: 2px 8px; border-radius: 4px; font-size: 11px;">
                                            <?php echo esc_html($log['source']); ?>
                                        </span>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td style="white-space: nowrap;">
                                    <?php echo esc_html(date('d.m.Y H:i', strtotime($log['timestamp']))); ?>
                                </td>
                                <td>
                                    <button type="button" class="button delete-log-entry" 
                                            data-index="<?php echo $index; ?>"
                                            data-name="<?php echo esc_attr($log['item_name']); ?>"
                                            style="color: #d63638; padding: 0 8px;">
                                        🗑️
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                </div>
                
                <!-- API-Konfiguration -->
                <div class="ul-card">
                    <h2 style="margin-top: 0;">🔑 API-Konfiguration</h2>
                    
                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th scope="row" style="padding: 8px 10px 8px 0;">API-Key</th>
                            <td style="padding: 8px 0;">
                                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                    <code id="api-key-display" style="padding: 6px 10px; background: #f0f0f0; border-radius: 4px; font-size: 12px; user-select: all; word-break: break-all;">
                                        <?php echo esc_html($api_key); ?>
                                    </code>
                                    <button type="button" id="copy-api-key" class="button button-primary button-small">📋 Kopieren</button>
                                    <button type="button" id="regenerate-api-key" class="button button-small">🔄 Neu</button>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 8px 10px 8px 0;">API-Endpunkt</th>
                            <td style="padding: 8px 0;">
                                <code style="padding: 6px 10px; background: #f0f0f0; border-radius: 4px; font-size: 11px; word-break: break-all;">
                                    <?php echo esc_url($site_url); ?>/wp-json/update-logger/v1/logs
                                </code>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <!-- ZEILE 3: TGMPA Status | Debug-Modus -->
            <div class="ul-grid">
                <!-- TGMPA Status -->
                <div class="ul-card">
                    <h2 style="margin-top: 0;">🔌 TGMPA Status</h2>
                    <p>
                        <strong>TGMPA aktiv:</strong> 
                        <?php echo class_exists('TGM_Plugin_Activation') ? '<span style="color:green">Ja</span>' : '<span style="color:gray">Nein</span>'; ?>
                    </p>
                    <?php if (class_exists('TGM_Plugin_Activation') && isset($GLOBALS['tgmpa'])): 
                        $tgmpa = $GLOBALS['tgmpa'];
                    ?>
                    <p><strong>Registrierte Plugins:</strong></p>
                    <ul style="list-style: disc; margin-left: 20px; font-size: 13px;">
                        <?php foreach ($tgmpa->plugins as $slug => $plugin): ?>
                        <li><?php echo esc_html($slug); ?> (<?php echo esc_html($plugin['name'] ?? $slug); ?>)</li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                    
                    <h4 style="margin-bottom: 5px;">Erkannte Theme-Plugin Patterns</h4>
                    <ul style="list-style: disc; margin-left: 20px; font-size: 11px; color: #666;">
                        <li>Bridge/Qode: bridge-core, qode-core, qode-listing</li>
                        <li>WPBakery: js_composer, wpbakery</li>
                        <li>Slider: revslider, layerslider</li>
                        <li>Generisch: *-core, starter-sites</li>
                    </ul>
                </div>
                
                <!-- Debug-Modus -->
                <div class="ul-card">
                    <h2 style="margin-top: 0;">🐛 Debug-Modus</h2>
                    <p>
                        <strong>Status:</strong> 
                        <?php echo UPDATE_LOGGER_DEBUG ? '<span style="color:green">Aktiviert</span>' : '<span style="color:gray">Deaktiviert</span>'; ?>
                    </p>
                    <p style="margin-bottom: 5px;">Zum Aktivieren in <code>wp-config.php</code> einfügen:</p>
                    <code style="display: block; padding: 10px; background: #f0f0f0; border-radius: 4px;">define('UPDATE_LOGGER_DEBUG', true);</code>
                    
                    <h4 style="margin: 15px 0 5px;">Test-Funktionen</h4>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <button type="button" id="test-db-log" class="button button-small">🧪 Test DB-Eintrag</button>
                        <button type="button" id="test-theme-plugin-log" class="button button-small">🧪 Test Theme-Plugin</button>
                        <?php if (count($logs) > 0): ?>
                        <button type="button" id="delete-all-logs" class="button button-small" style="color: #d63638;">🗑️ Alle löschen</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

// Initialize the plugin
new Update_Logger_API();
