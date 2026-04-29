<?php
/**
 * Plugin Name: Update Logger API
 * Description: Logs WordPress updates (Plugins, Themes, Core, Database, Backups) and exposes them via REST API for external dashboards
 * Version: 1.9.7
 * Author: AUGENKLANG
 * Requires PHP: 8.0
 * Requires at least: 6.0
 * 
 * Changelog 1.9.7:
 * - FIX: Datenbank-Updates zeigen jetzt die aktuelle Plugin-Version an (z.B. "v3.35.1")
 * - Plugin-Version wird über Slug-Mapping und get_plugin_data() ermittelt
 * - WordPress Core DB-Updates zeigen die aktuelle WordPress-Version
 * 
 * Changelog 1.9.6:
 * - FIX: WordPress Core-Updates zeigen jetzt korrekte neue Version (z.B. 6.9 → 6.9.1 statt 6.9 → 6.9)
 * - FIX: Elementor und Elementor Pro Datenbank-Updates werden wieder zuverlässig geloggt
 * - NEU: Filterfunktion nach Update-Typ (Plugin, Theme, Core, etc.) in der Update-Übersicht
 * 
 * Changelog 1.9.5:
 * - NEU: "Wartung" Link in der WordPress Admin-Bar mit Wartungs-Icon
 * - NEU: Kopier-Button für den API-Schlüssel in der API-Konfiguration
 * - UI: Schnellzugriff auf Update Logger direkt aus der Admin-Bar
 * 
 * Changelog 1.9.4:
 * - NEU: DELETE /update-logger/v1/delete-entries REST API Endpunkt zum Löschen von Einträgen nach Titel
 * - NEU: Pagination für "Letzte Updates" mit max 10 Einträgen pro Seite
 * - NEU: AJAX-Aktion für titel-basiertes Löschen (ajax_delete_entries_by_title)
 * - UI: Layout wie Version 1.9.2 wiederhergestellt (Inline-Scripts statt wp_enqueue)
 * 
 * Changelog 1.9.3:
 * - PHP 8.2+ Kompatibilität: #[AllowDynamicProperties] Attribut hinzugefügt
 * - PHP 8.2+ Kompatibilität: Explizite Typdeklarationen für alle Klassen-Properties
 * - WordPress 6.9 Kompatibilität: JavaScript über wp_add_inline_script() statt direktem Output
 * - W3 Total Cache Kompatibilität: Cache-Control Header für REST API Endpunkte
 * - W3 Total Cache Kompatibilität: Nonces über wp_localize_script() bereitgestellt
 * - FIX: Potenzielle Parse-Fehler durch sauberes Script-Handling behoben
 * 
 * Changelog 1.9.2:
 * - FIX: Elementor Pro Datenbank-Updates werden jetzt korrekt geloggt
 * - FIX: Elementor Free Datenbank-Updates - fehlenden Fallback-Hook hinzugefügt
 * - NEU: Hook für elementor_pro/core/upgrade/after hinzugefügt
 * - NEU: Unterstützung für Elementor AJAX-basierte DB-Updates (Admin-Banner)
 * - NEU: Hook für alle Elementor/Pro Options-Änderungen als Fallback
 * 
 * Changelog 1.9.1:
 * - FIX: "Theme-Plugins" und "Plugins" zu einem Typ "plugin" vereinheitlicht
 * - Alle Plugin-Updates werden jetzt einheitlich als "plugin" geloggt
 * - TGMPA/gebündelte Plugins behalten ihre Quelle (tgmpa/bundled) zur Unterscheidung
 * - UI: Statistik zeigt nur noch einen kombinierten Plugin-Zähler
 * 
 * Changelog 1.9.0:
 * - NEU: UpdraftPlus Backup-Logging (automatische und manuelle Backups)
 * - Neuer Log-Typ 'backup' mit Details zu Backup-Typ, Trigger und Speicherziel
 * - UI: Backup-Statistik im Dashboard hinzugefügt
 * - UI: Rahmen um "Theme-Plugins" entfernt
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

#[AllowDynamicProperties]
class Update_Logger_API {
    
    private string $option_key = 'update_logger_logs';
    private string $api_key_option = 'update_logger_api_key';
    private array $pre_update_versions = [];
    private array $tgmpa_plugins = [];
    private array $tgmpa_updates = [];
    private string $versions_transient_key = 'update_logger_pre_versions';
    
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
        
        // TGMPA-spezifische Hooks
        $this->register_tgmpa_hooks();
        
        // Haupt-Hook für Updates NACH Abschluss - niedrigere Priorität damit wir nach TGMPA laufen
        add_action('upgrader_process_complete', [$this, 'log_update'], 20, 2);
        
        // Backup: Auch den TGMPA-spezifischen Hook nutzen
        add_action('upgrader_process_complete', [$this, 'check_tgmpa_upgrader'], 15, 2);
        
        // Alternative Hooks für Theme-Plugin-Updates
        add_action('activated_plugin', [$this, 'check_plugin_activation'], 10, 2);
        
        // UpdraftPlus Backup Hooks
        add_action('updraftplus_backup_complete', [$this, 'log_updraft_backup_complete'], 10, 4);
        add_action('updraft_backup_complete', [$this, 'log_updraft_backup_complete'], 10, 4);
        add_action('updraftplus_backup_finished', [$this, 'log_updraft_backup_finished'], 10, 1);
        
        // Database update hooks for various plugins
        $this->register_database_update_hooks();
        
        // REST API
        add_action('rest_api_init', [$this, 'register_routes']);
        
        // REST API Cache-Control Header für W3 Total Cache Kompatibilität
        add_filter('rest_post_dispatch', [$this, 'add_rest_cache_headers'], 10, 3);
        
        // Admin page
        add_action('admin_menu', [$this, 'add_admin_menu']);
        
        // Admin Bar - NEU in 1.9.5
        add_action('admin_bar_menu', [$this, 'add_admin_bar_menu'], 100);
        
        // Admin AJAX
        add_action('wp_ajax_regenerate_api_key', [$this, 'ajax_regenerate_key']);
        add_action('wp_ajax_add_test_log', [$this, 'ajax_add_test_log']);
        add_action('wp_ajax_delete_log_entry', [$this, 'ajax_delete_log_entry']);
        add_action('wp_ajax_delete_all_logs', [$this, 'ajax_delete_all_logs']);
        add_action('wp_ajax_add_maintenance_entry', [$this, 'ajax_add_maintenance_entry']);
        add_action('wp_ajax_delete_entries_by_title', [$this, 'ajax_delete_entries_by_title']);
    }
    
    /**
     * NEU 1.9.5: Add "Wartung" link to admin bar
     */
    public function add_admin_bar_menu($wp_admin_bar): void {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        // Admin-Bar Icon SVG (Wrench/Tool icon)
        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="16" height="16" fill="currentColor" style="vertical-align: middle; margin-right: 4px;"><path d="M22.7 19l-9.1-9.1c.9-2.3.4-5-1.5-6.9-2-2-5-2.4-7.4-1.3L9 6 6 9 1.6 4.7C.4 7.1.9 10.1 2.9 12.1c1.9 1.9 4.6 2.4 6.9 1.5l9.1 9.1c.4.4 1 .4 1.4 0l2.3-2.3c.5-.4.5-1.1.1-1.4z"/></svg>';
        
        $wp_admin_bar->add_node([
            'id'    => 'update-logger-wartung',
            'title' => $icon_svg . 'Wartung',
            'href'  => admin_url('tools.php?page=update-logger'),
            'meta'  => [
                'title' => 'Update Logger - Wartungsübersicht',
            ],
        ]);
    }
    
    /**
     * Add cache control headers to REST API responses for W3 Total Cache compatibility
     */
    public function add_rest_cache_headers($response, $server, $request): \WP_REST_Response {
        $route = $request->get_route();
        
        // Nur für unsere Endpunkte Cache-Control Header setzen
        if (strpos($route, '/update-logger/') !== false) {
            $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->header('Pragma', 'no-cache');
            $response->header('Expires', '0');
        }
        
        return $response;
    }
    
    /**
     * Debug logging helper
     */
    private function debug_log(string $message, mixed $data = null): void {
        if (!UPDATE_LOGGER_DEBUG) {
            return;
        }
        
        $log_entry = '[Update Logger] ' . $message;
        if ($data !== null) {
            $log_entry .= ': ' . print_r($data, true);
        }
        error_log($log_entry);
    }
    
    /**
     * Register database update hooks for various plugins
     */
    private function register_database_update_hooks(): void {
        // Elementor Free
        add_action('elementor/core/upgrade/after', [$this, 'log_elementor_db_update']);
        
        // Elementor Pro - Haupt-Hook nach DB-Upgrade
        add_action('elementor_pro/core/upgrade/after', [$this, 'log_elementor_pro_db_update']);
        
        // Elementor Pro - Alternative Hooks für Admin-Banner DB-Update
        add_action('elementor/core/files/clear_cache', [$this, 'check_elementor_db_update_via_cache_clear']);
        
        // Elementor AJAX-basierte Updates (wird bei Klick auf "Datenbank aktualisieren" Button aufgerufen)
        add_action('wp_ajax_elementor_finish_version_update', [$this, 'log_elementor_ajax_db_update']);
        add_action('wp_ajax_elementor_pro_finish_version_update', [$this, 'log_elementor_pro_ajax_db_update']);
        
        // NEU 1.9.6: Zusätzliche Elementor AJAX-Hooks für neuere Versionen
        add_action('wp_ajax_elementor_upgrade_elements', [$this, 'log_elementor_ajax_db_update']);
        add_action('wp_ajax_elementor_pro_upgrade_elements', [$this, 'log_elementor_pro_ajax_db_update']);
        
        // Fallback: Elementor Pro Version-Option-Update überwachen
        add_action('update_option_elementor_pro_version', [$this, 'on_elementor_pro_version_change'], 10, 2);
        add_action('add_option_elementor_pro_version', [$this, 'on_elementor_pro_version_added'], 10, 2);
        
        // Fallback: Elementor Free Version-Option-Update überwachen
        add_action('update_option_elementor_version', [$this, 'on_elementor_version_change'], 10, 2);
        add_action('add_option_elementor_version', [$this, 'on_elementor_version_added'], 10, 2);
        
        // NEU 1.9.6: Elementor DB version options (these change when DB upgrades run)
        add_action('update_option_elementor_db_version', [$this, 'on_elementor_db_version_change'], 10, 2);
        add_action('update_option_elementor_pro_db_version', [$this, 'on_elementor_pro_db_version_change'], 10, 2);
        
        // WooCommerce
        add_action('woocommerce_run_update_callback', [$this, 'log_wc_db_update']);
        add_action('woocommerce_updated', [$this, 'log_wc_updated']);
        
        // WPML
        add_action('wpml_after_upgrade', [$this, 'log_wpml_db_update']);
        
        // ACF
        add_action('acf/upgrade_complete', [$this, 'log_acf_db_update']);
        
        // Gravity Forms
        add_action('gform_post_upgrade', [$this, 'log_gf_db_update']);
        
        // General database upgrade hook
        add_action('wp_upgrade', [$this, 'log_wp_db_upgrade']);
    }
    
    /**
     * Log UpdraftPlus backup completion
     */
    public function log_updraft_backup_complete($jobid, $final_message = '', $files = [], $stats = []): void {
        $this->debug_log('UpdraftPlus backup complete', [
            'jobid' => $jobid,
            'message' => $final_message,
            'files' => $files,
        ]);
        
        // Backup-Typ ermitteln
        $backup_type = 'Vollständig';
        if (!empty($files)) {
            $types = [];
            foreach ($files as $file) {
                if (strpos($file, 'db') !== false) $types[] = 'DB';
                if (strpos($file, 'plugins') !== false) $types[] = 'Plugins';
                if (strpos($file, 'themes') !== false) $types[] = 'Themes';
                if (strpos($file, 'uploads') !== false) $types[] = 'Uploads';
                if (strpos($file, 'others') !== false) $types[] = 'Andere';
            }
            if (!empty($types)) {
                $backup_type = implode(', ', array_unique($types));
            }
        }
        
        // Trigger ermitteln (automatisch oder manuell)
        $trigger = 'Automatisch';
        if (isset($_REQUEST['action']) && strpos($_REQUEST['action'], 'updraft') !== false) {
            $trigger = 'Manuell';
        }
        
        // Speicherziel ermitteln
        $storage = 'Lokal';
        $updraft_service = get_option('updraft_service', []);
        if (!empty($updraft_service)) {
            if (is_array($updraft_service)) {
                $storage = implode(', ', array_map([$this, 'get_storage_name'], $updraft_service));
            } else {
                $storage = $this->get_storage_name($updraft_service);
            }
        }
        
        $logs = get_option($this->option_key, []);
        
        // Duplikat-Check
        $recent_log = !empty($logs) ? end($logs) : null;
        if ($recent_log && 
            $recent_log['type'] === 'backup' && 
            isset($recent_log['item_slug']) && $recent_log['item_slug'] === 'updraftplus' &&
            (time() - strtotime($recent_log['timestamp'])) < 60) {
            $this->debug_log('Skipping duplicate backup log');
            return;
        }
        
        // Neuen Log-Eintrag erstellen
        $logs[] = [
            'type' => 'backup',
            'timestamp' => current_time('mysql'),
            'item_name' => 'UpdraftPlus Backup',
            'item_slug' => 'updraftplus',
            'old_version' => '',
            'new_version' => '',
            'source' => 'updraftplus',
            'description' => sprintf('%s Backup (%s) → %s', $backup_type, $trigger, $storage),
            'backup_type' => $backup_type,
            'backup_trigger' => $trigger,
            'backup_storage' => $storage,
            'job_id' => $jobid,
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        $this->debug_log('UpdraftPlus backup logged', [
            'type' => $backup_type,
            'trigger' => $trigger,
            'storage' => $storage,
            'job_id' => $jobid
        ]);
    }
    
    /**
     * Fallback: Log UpdraftPlus backup finished
     */
    public function log_updraft_backup_finished($jobid): void {
        $this->debug_log('UpdraftPlus backup finished hook fired', ['jobid' => $jobid]);
        
        // Prüfen ob wir diesen Job schon geloggt haben
        $logs = get_option($this->option_key, []);
        foreach ($logs as $log) {
            if (isset($log['job_id']) && $log['job_id'] === $jobid) {
                $this->debug_log('Job already logged, skipping', $jobid);
                return;
            }
        }
    }
    
    /**
     * Helper: Get human-readable storage name
     */
    private function get_storage_name($service): string {
        $names = [
            'none' => 'Nur lokal',
            'local' => 'Lokal',
            's3' => 'Amazon S3',
            's3generic' => 'S3-kompatibel',
            'dropbox' => 'Dropbox',
            'googledrive' => 'Google Drive',
            'googlecloud' => 'Google Cloud',
            'onedrive' => 'OneDrive',
            'azure' => 'Microsoft Azure',
            'backblaze' => 'Backblaze B2',
            'ftp' => 'FTP',
            'sftp' => 'SFTP',
            'webdav' => 'WebDAV',
            'email' => 'E-Mail',
            'updraftvault' => 'UpdraftVault',
            'dreamobjects' => 'DreamObjects',
            'openstack' => 'OpenStack',
            'rackspace' => 'Rackspace',
        ];
        
        return $names[$service] ?? ucfirst($service);
    }
    
    /**
     * Register TGM Plugin Activation hooks
     */
    private function register_tgmpa_hooks(): void {
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
     * Spezieller Hook um TGMPA Bulk Installer Updates zu erkennen
     */
    public function check_tgmpa_upgrader($upgrader, $options): void {
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
    public function detect_tgmpa_update_request(): void {
        $this->debug_log('detect_tgmpa_update_request called');
        $this->capture_all_plugin_versions_early();
    }
    
    /**
     * Erfasst alle Plugin-Versionen sehr früh - BEVOR Updates starten
     */
    public function capture_all_plugin_versions_early(): void {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Bereits erfasst?
        if (!empty($this->pre_update_versions)) {
            return;
        }
        
        // Aus Transient laden falls vorhanden
        $cached = get_transient($this->versions_transient_key);
        if ($cached && !empty($cached)) {
            $this->pre_update_versions = $cached;
            $this->debug_log('Loaded versions from transient', count($cached));
            return;
        }
        
        $this->debug_log('Capturing all plugin versions early');
        
        // Plugin-Versionen
        $all_plugins = get_plugins();
        foreach ($all_plugins as $plugin_file => $plugin_data) {
            $this->pre_update_versions['plugin'][$plugin_file] = $plugin_data['Version'];
            
            // Auch nach Slug speichern für einfacheren Zugriff
            $slug = dirname($plugin_file);
            if ($slug === '.') {
                $slug = basename($plugin_file, '.php');
            }
            $this->pre_update_versions['plugin_by_slug'][$slug] = $plugin_data['Version'];
        }
        
        // Theme-Versionen
        $themes = wp_get_themes();
        foreach ($themes as $theme_slug => $theme) {
            $this->pre_update_versions['theme'][$theme_slug] = $theme->get('Version');
        }
        
        // Core-Version
        global $wp_version;
        $this->pre_update_versions['core']['wordpress'] = $wp_version;
        
        // In Transient speichern (5 Minuten)
        set_transient($this->versions_transient_key, $this->pre_update_versions, 300);
        
        $this->debug_log('Captured versions', [
            'plugins' => count($this->pre_update_versions['plugin'] ?? []),
            'themes' => count($this->pre_update_versions['theme'] ?? []),
        ]);
    }
    
    /**
     * Capture version before download starts
     */
    public function capture_version_before_download($reply, $package, $upgrader, $hook_extra) {
        $this->debug_log('capture_version_before_download', $hook_extra);
        
        // Versionen aus Transient laden falls nicht vorhanden
        if (empty($this->pre_update_versions)) {
            $cached = get_transient($this->versions_transient_key);
            if ($cached) {
                $this->pre_update_versions = $cached;
            } else {
                $this->capture_all_plugin_versions_early();
            }
        }
        
        return $reply;
    }
    
    /**
     * Capture pre-update versions just before installation
     */
    public function capture_pre_update_versions($reply, $hook_extra) {
        $this->debug_log('capture_pre_update_versions', $hook_extra);
        
        // Versionen aus Transient laden falls nicht vorhanden
        if (empty($this->pre_update_versions)) {
            $cached = get_transient($this->versions_transient_key);
            if ($cached) {
                $this->pre_update_versions = $cached;
            }
        }
        
        return $reply;
    }
    
    /**
     * Capture TGMPA registered plugins
     */
    public function capture_tgmpa_plugins(): void {
        if (class_exists('TGM_Plugin_Activation') && isset($GLOBALS['tgmpa'])) {
            $tgmpa = $GLOBALS['tgmpa'];
            if (isset($tgmpa->plugins) && is_array($tgmpa->plugins)) {
                $this->tgmpa_plugins = $tgmpa->plugins;
                $this->debug_log('Captured TGMPA plugins', array_keys($this->tgmpa_plugins));
            }
        }
    }
    
    /**
     * Log TGMPA plugin activation
     */
    public function log_tgmpa_plugin_activated($plugin): void {
        $this->debug_log('TGMPA plugin activated', $plugin);
    }
    
    /**
     * Log TGMPA after row
     */
    public function log_tgmpa_after_row($plugin, $status): void {
        $this->debug_log('TGMPA after row', ['plugin' => $plugin, 'status' => $status]);
    }
    
    /**
     * Check plugin activation for theme plugins
     */
    public function check_plugin_activation($plugin, $network_wide): void {
        $this->debug_log('Plugin activated', ['plugin' => $plugin, 'network' => $network_wide]);
    }
    
    /**
     * Log Elementor DB update
     */
    public function log_elementor_db_update(): void {
        $this->log_database_update('Elementor', 'elementor');
    }
    
    /**
     * Log Elementor Pro DB update
     */
    public function log_elementor_pro_db_update(): void {
        $this->log_database_update('Elementor Pro', 'elementor-pro');
    }
    
    /**
     * Check Elementor DB update via cache clear
     */
    public function check_elementor_db_update_via_cache_clear(): void {
        $this->debug_log('Elementor cache cleared - checking for DB update');
    }
    
    /**
     * Log Elementor AJAX DB update
     */
    public function log_elementor_ajax_db_update(): void {
        $this->debug_log('Elementor AJAX DB update triggered');
        $this->log_database_update('Elementor', 'elementor');
    }
    
    /**
     * Log Elementor Pro AJAX DB update
     */
    public function log_elementor_pro_ajax_db_update(): void {
        $this->debug_log('Elementor Pro AJAX DB update triggered');
        $this->log_database_update('Elementor Pro', 'elementor-pro');
    }
    
    /**
     * FIX 1.9.6: On Elementor Pro version change - actually log the database update
     */
    public function on_elementor_pro_version_change($old_value, $new_value): void {
        if ($old_value !== $new_value) {
            $this->debug_log('Elementor Pro version changed', ['old' => $old_value, 'new' => $new_value]);
            $this->log_database_update('Elementor Pro', 'elementor-pro');
        }
    }
    
    /**
     * On Elementor Pro version added
     */
    public function on_elementor_pro_version_added($option, $value): void {
        $this->debug_log('Elementor Pro version added', $value);
    }
    
    /**
     * FIX 1.9.6: On Elementor version change - actually log the database update
     */
    public function on_elementor_version_change($old_value, $new_value): void {
        if ($old_value !== $new_value) {
            $this->debug_log('Elementor version changed', ['old' => $old_value, 'new' => $new_value]);
            $this->log_database_update('Elementor', 'elementor');
        }
    }
    
    /**
     * On Elementor version added
     */
    public function on_elementor_version_added($option, $value): void {
        $this->debug_log('Elementor version added', $value);
    }
    
    /**
     * NEU 1.9.6: On Elementor DB version change
     */
    public function on_elementor_db_version_change($old_value, $new_value): void {
        if ($old_value !== $new_value) {
            $this->debug_log('Elementor DB version changed', ['old' => $old_value, 'new' => $new_value]);
            $this->log_database_update('Elementor', 'elementor');
        }
    }
    
    /**
     * NEU 1.9.6: On Elementor Pro DB version change
     */
    public function on_elementor_pro_db_version_change($old_value, $new_value): void {
        if ($old_value !== $new_value) {
            $this->debug_log('Elementor Pro DB version changed', ['old' => $old_value, 'new' => $new_value]);
            $this->log_database_update('Elementor Pro', 'elementor-pro');
        }
    }
    
    /**
     * Log WooCommerce DB update
     */
    public function log_wc_db_update(): void {
        $this->log_database_update('WooCommerce', 'woocommerce');
    }
    
    /**
     * Log WooCommerce updated
     */
    public function log_wc_updated(): void {
        $this->debug_log('WooCommerce updated hook fired');
    }
    
    /**
     * Log WPML DB update
     */
    public function log_wpml_db_update(): void {
        $this->log_database_update('WPML', 'sitepress-multilingual-cms');
    }
    
    /**
     * Log ACF DB update
     */
    public function log_acf_db_update(): void {
        $this->log_database_update('Advanced Custom Fields', 'advanced-custom-fields');
    }
    
    /**
     * Log Gravity Forms DB update
     */
    public function log_gf_db_update(): void {
        $this->log_database_update('Gravity Forms', 'gravityforms');
    }
    
    /**
     * Log WordPress DB upgrade
     */
    public function log_wp_db_upgrade(): void {
        $this->log_database_update('WordPress', 'wordpress');
    }
    
    /**
     * NEU 1.9.7: Resolve plugin version from slug
     */
    private function resolve_plugin_version(string $slug): string {
        // Mapping von Slug zu Plugin-Hauptdatei
        $slug_to_file = [
            'elementor'                    => 'elementor/elementor.php',
            'elementor-pro'                => 'elementor-pro/elementor-pro.php',
            'woocommerce'                  => 'woocommerce/woocommerce.php',
            'sitepress-multilingual-cms'   => 'sitepress-multilingual-cms/sitepress.php',
            'advanced-custom-fields'       => 'advanced-custom-fields/acf.php',
            'gravityforms'                 => 'gravityforms/gravityforms.php',
            'revslider'                    => 'revslider/revslider.php',
            'js_composer'                  => 'js_composer/js_composer.php',
            'contact-form-7'               => 'contact-form-7/wp-contact-form-7.php',
            'yoast-seo'                    => 'wordpress-seo/wp-seo.php',
            'rank-math'                    => 'seo-by-rank-math/rank-math.php',
        ];
        
        // WordPress Core
        if ($slug === 'wordpress') {
            global $wp_version;
            return $wp_version ?: '';
        }
        
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        // Bekanntes Mapping verwenden
        if (isset($slug_to_file[$slug])) {
            $plugin_file = WP_PLUGIN_DIR . '/' . $slug_to_file[$slug];
            if (file_exists($plugin_file)) {
                $plugin_data = get_plugin_data($plugin_file);
                return $plugin_data['Version'] ?? '';
            }
        }
        
        // Fallback: Slug als Ordnername versuchen
        $possible_files = [
            $slug . '/' . $slug . '.php',
            $slug . '/index.php',
        ];
        
        foreach ($possible_files as $file) {
            $full_path = WP_PLUGIN_DIR . '/' . $file;
            if (file_exists($full_path)) {
                $plugin_data = get_plugin_data($full_path);
                if (!empty($plugin_data['Version'])) {
                    return $plugin_data['Version'];
                }
            }
        }
        
        return '';
    }
    
    /**
     * Generic database update logger - FIX 1.9.7: Now includes plugin version
     */
    private function log_database_update(string $name, string $slug): void {
        $logs = get_option($this->option_key, []);
        
        // Duplikat-Check (innerhalb von 60 Sekunden)
        $recent_log = !empty($logs) ? end($logs) : null;
        if ($recent_log && 
            $recent_log['type'] === 'database' && 
            isset($recent_log['item_slug']) && $recent_log['item_slug'] === $slug &&
            (time() - strtotime($recent_log['timestamp'])) < 60) {
            $this->debug_log('Skipping duplicate database update log', $slug);
            return;
        }
        
        // NEU 1.9.7: Plugin-Version ermitteln
        $plugin_version = $this->resolve_plugin_version($slug);
        $new_version = !empty($plugin_version) ? 'v' . $plugin_version : '';
        
        $logs[] = [
            'type' => 'database',
            'timestamp' => current_time('mysql'),
            'item_name' => $name . ' Datenbank-Update',
            'item_slug' => $slug,
            'old_version' => '',
            'new_version' => $new_version,
            'source' => 'database_update',
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        $this->debug_log('Database update logged', ['name' => $name, 'slug' => $slug, 'version' => $new_version]);
    }
    
    /**
     * Main update logger
     */
    public function log_update($upgrader, $options): void {
        $this->debug_log('log_update called', $options);
        
        if (!isset($options['type'])) {
            return;
        }
        
        $type = $options['type'];
        $action = $options['action'] ?? '';
        
        // Core updates
        if ($type === 'core' && $action === 'update') {
            $this->log_core_update();
            return;
        }
        
        // Plugin updates
        if ($type === 'plugin' && $action === 'update') {
            $plugins = $options['plugins'] ?? [];
            if (!empty($options['plugin'])) {
                $plugins = [$options['plugin']];
            }
            
            foreach ($plugins as $plugin) {
                $this->log_plugin_update($plugin);
            }
            return;
        }
        
        // Theme updates
        if ($type === 'theme' && $action === 'update') {
            $themes = $options['themes'] ?? [];
            if (!empty($options['theme'])) {
                $themes = [$options['theme']];
            }
            
            foreach ($themes as $theme) {
                $this->log_theme_update($theme);
            }
            return;
        }
    }
    
    /**
     * Log core update - FIX 1.9.6: Read new version from version.php file
     */
    private function log_core_update(): void {
        $old_version = $this->pre_update_versions['core']['wordpress'] ?? '';
        
        // FIX 1.9.6: Die globale $wp_version wird beim PHP-Start geladen und ändert sich
        // nicht während des Prozesses. Daher die neue Version direkt aus der aktualisierten
        // version.php Datei lesen.
        $new_version = '';
        $version_file = ABSPATH . 'wp-includes/version.php';
        if (file_exists($version_file)) {
            $version_content = file_get_contents($version_file);
            if (preg_match('/\$wp_version\s*=\s*[\'"]([^\'"]+)[\'"]/', $version_content, $matches)) {
                $new_version = $matches[1];
            }
        }
        
        // Fallback auf globale Variable
        if (empty($new_version)) {
            global $wp_version;
            $new_version = $wp_version;
        }
        
        // Transient löschen damit beim nächsten Laden die neue Version erfasst wird
        delete_transient($this->versions_transient_key);
        
        $logs = get_option($this->option_key, []);
        $logs[] = [
            'type' => 'core',
            'timestamp' => current_time('mysql'),
            'item_name' => 'WordPress',
            'item_slug' => 'wordpress',
            'old_version' => $old_version,
            'new_version' => $new_version,
            'source' => 'core',
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        $this->debug_log('Core update logged', ['old' => $old_version, 'new' => $new_version]);
    }
    
    /**
     * Log plugin update
     */
    private function log_plugin_update(string $plugin_file): void {
        if (!function_exists('get_plugin_data')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        $plugin_path = WP_PLUGIN_DIR . '/' . $plugin_file;
        
        if (!file_exists($plugin_path)) {
            $this->debug_log('Plugin file not found', $plugin_path);
            return;
        }
        
        $plugin_data = get_plugin_data($plugin_path);
        $new_version = $plugin_data['Version'];
        
        // Alte Version ermitteln
        $old_version = '';
        if (isset($this->pre_update_versions['plugin'][$plugin_file])) {
            $old_version = $this->pre_update_versions['plugin'][$plugin_file];
        } else {
            $slug = dirname($plugin_file);
            if ($slug === '.') {
                $slug = basename($plugin_file, '.php');
            }
            if (isset($this->pre_update_versions['plugin_by_slug'][$slug])) {
                $old_version = $this->pre_update_versions['plugin_by_slug'][$slug];
            }
        }
        
        // Skip if versions are the same
        if ($old_version === $new_version) {
            $this->debug_log('Skipping plugin update - same version', ['plugin' => $plugin_file, 'version' => $new_version]);
            return;
        }
        
        // Determine source
        $slug = dirname($plugin_file);
        if ($slug === '.') {
            $slug = basename($plugin_file, '.php');
        }
        
        $source = 'wordpress.org';
        if (isset($this->tgmpa_updates[$slug]) || isset($this->tgmpa_plugins[$slug])) {
            $source = 'tgmpa';
        }
        
        $logs = get_option($this->option_key, []);
        $logs[] = [
            'type' => 'plugin',
            'timestamp' => current_time('mysql'),
            'item_name' => $plugin_data['Name'],
            'item_slug' => $plugin_file,
            'old_version' => $old_version,
            'new_version' => $new_version,
            'source' => $source,
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        $this->debug_log('Plugin update logged', [
            'name' => $plugin_data['Name'],
            'old' => $old_version,
            'new' => $new_version,
            'source' => $source
        ]);
    }
    
    /**
     * Log theme update
     */
    private function log_theme_update(string $theme_slug): void {
        $theme = wp_get_theme($theme_slug);
        
        if (!$theme->exists()) {
            $this->debug_log('Theme not found', $theme_slug);
            return;
        }
        
        $new_version = $theme->get('Version');
        $old_version = $this->pre_update_versions['theme'][$theme_slug] ?? '';
        
        // Skip if versions are the same
        if ($old_version === $new_version) {
            $this->debug_log('Skipping theme update - same version', ['theme' => $theme_slug, 'version' => $new_version]);
            return;
        }
        
        $logs = get_option($this->option_key, []);
        $logs[] = [
            'type' => 'theme',
            'timestamp' => current_time('mysql'),
            'item_name' => $theme->get('Name'),
            'item_slug' => $theme_slug,
            'old_version' => $old_version,
            'new_version' => $new_version,
            'source' => 'wordpress.org',
        ];
        
        $logs = array_slice($logs, -500);
        update_option($this->option_key, $logs);
        
        $this->debug_log('Theme update logged', [
            'name' => $theme->get('Name'),
            'old' => $old_version,
            'new' => $new_version
        ]);
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes(): void {
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
        
        // NEU: DELETE Endpunkt zum Löschen von Einträgen nach Titel
        register_rest_route('update-logger/v1', '/delete-entries', [
            'methods' => 'DELETE',
            'callback' => [$this, 'delete_entries_by_title'],
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
            return new \WP_Error('no_api_key', 'API key not configured', ['status' => 500]);
        }
        
        return hash_equals($stored_key, $provided_key ?? '');
    }
    
    /**
     * Get logs via REST API
     */
    public function get_logs($request): \WP_REST_Response {
        $logs = get_option($this->option_key, []);
        $since = $request->get_param('since');
        $type = $request->get_param('type');
        $source = $request->get_param('source');
        $limit = $request->get_param('limit') ?: 100;
        
        // Sort newest first
        $logs = array_reverse($logs);
        
        if ($since) {
            $since_time = strtotime($since);
            $logs = array_filter($logs, function($log) use ($since_time) {
                return strtotime($log['timestamp']) > $since_time;
            });
        }
        
        if ($type) {
            $logs = array_filter($logs, function($log) use ($type) {
                $log_type = $log['type'] ?? '';
                if ($type === 'plugin') {
                    return $log_type === 'plugin' || $log_type === 'theme_plugin';
                }
                return $log_type === $type;
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
    public function get_debug_info($request): \WP_REST_Response {
        $info = [
            'plugin_version' => '1.9.7',
            'wordpress_version' => get_bloginfo('version'),
            'php_version' => PHP_VERSION,
            'debug_mode' => UPDATE_LOGGER_DEBUG,
            'tgmpa_active' => class_exists('TGM_Plugin_Activation'),
            'updraftplus_active' => class_exists('UpdraftPlus'),
            'elementor_active' => defined('ELEMENTOR_VERSION'),
            'elementor_pro_active' => defined('ELEMENTOR_PRO_VERSION'),
            'current_theme' => get_template(),
            'log_count' => count(get_option($this->option_key, [])),
            'pre_update_versions_cached' => count($this->pre_update_versions),
            'tgmpa_updates_tracked' => count($this->tgmpa_updates),
        ];
        
        if (defined('ELEMENTOR_VERSION')) {
            $info['elementor_version'] = ELEMENTOR_VERSION;
        }
        
        if (defined('ELEMENTOR_PRO_VERSION')) {
            $info['elementor_pro_version'] = ELEMENTOR_PRO_VERSION;
        }
        
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
    public function add_manual_entry($request): \WP_REST_Response {
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
     * NEU: Delete entries by title via REST API
     */
    public function delete_entries_by_title($request): \WP_REST_Response {
        $title = $request->get_param('title');
        
        if (empty($title)) {
            return new \WP_REST_Response([
                'success' => false,
                'error' => 'Title parameter is required'
            ], 400);
        }
        
        $logs = get_option($this->option_key, []);
        $original_count = count($logs);
        
        // Einträge mit passendem Titel filtern
        $logs = array_filter($logs, function($log) use ($title) {
            return ($log['item_name'] ?? '') !== $title;
        });
        
        $deleted_count = $original_count - count($logs);
        
        update_option($this->option_key, array_values($logs));
        
        $this->debug_log('Entries deleted by title', [
            'title' => $title,
            'deleted_count' => $deleted_count,
            'remaining_count' => count($logs)
        ]);
        
        return rest_ensure_response([
            'success' => true,
            'deleted_count' => $deleted_count,
            'remaining_count' => count($logs)
        ]);
    }
    
    /**
     * AJAX: Regenerate API key
     */
    public function ajax_regenerate_key(): void {
        check_ajax_referer('regenerate_api_key', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $new_key = wp_generate_password(32, false);
        update_option($this->api_key_option, $new_key);
        
        wp_send_json_success(['key' => $new_key]);
    }
    
    /**
     * AJAX: Add test log entry
     */
    public function ajax_add_test_log(): void {
        check_ajax_referer('add_test_log', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $logs = get_option($this->option_key, []);
        $logs[] = [
            'type' => 'plugin',
            'timestamp' => current_time('mysql'),
            'item_name' => 'Test Plugin',
            'item_slug' => 'test-plugin/test-plugin.php',
            'old_version' => '1.0.0',
            'new_version' => '1.0.1',
            'source' => 'test',
        ];
        
        update_option($this->option_key, $logs);
        
        wp_send_json_success(['message' => 'Test-Eintrag erstellt']);
    }
    
    /**
     * AJAX: Delete single log entry
     */
    public function ajax_delete_log_entry(): void {
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
    public function ajax_delete_all_logs(): void {
        check_ajax_referer('delete_all_logs', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        update_option($this->option_key, []);
        
        wp_send_json_success(['message' => 'Alle Einträge gelöscht']);
    }
    
    /**
     * AJAX: Add maintenance entry
     */
    public function ajax_add_maintenance_entry(): void {
        check_ajax_referer('add_maintenance_entry', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $title = sanitize_text_field($_POST['title'] ?? '');
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
     * NEU: AJAX: Delete entries by title
     */
    public function ajax_delete_entries_by_title(): void {
        check_ajax_referer('delete_entries_by_title', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Keine Berechtigung']);
        }
        
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        if (empty($title)) {
            wp_send_json_error(['message' => 'Titel ist erforderlich']);
        }
        
        $logs = get_option($this->option_key, []);
        $original_count = count($logs);
        
        $logs = array_filter($logs, function($log) use ($title) {
            return ($log['item_name'] ?? '') !== $title;
        });
        
        $deleted_count = $original_count - count($logs);
        
        update_option($this->option_key, array_values($logs));
        
        wp_send_json_success([
            'message' => sprintf('%d Einträge gelöscht', $deleted_count),
            'deleted_count' => $deleted_count
        ]);
    }
    
    /**
     * Helper function to format time spent
     */
    private function format_time_spent(int $minutes): string {
        if ($minutes <= 0) {
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
    public function add_admin_menu(): void {
        add_management_page(
            'Update Logger API',
            'Update Logger',
            'manage_options',
            'update-logger',
            [$this, 'render_admin_page']
        );
    }
    
    /**
     * Render admin settings page - zweispaltiges Layout wie Version 1.9.2
     */
    public function render_admin_page(): void {
        $api_key = get_option($this->api_key_option);
        $logs = get_option($this->option_key, []);
        $site_url = get_site_url();
        
        // NEU 1.9.6: Filter-Typ aus GET-Parameter
        $filter_type = isset($_GET['filter_type']) ? sanitize_text_field($_GET['filter_type']) : '';
        
        // Count by type - Combine plugin and theme_plugin
        $type_counts = [
            'plugin' => 0,
            'theme' => 0,
            'core' => 0,
            'database' => 0,
            'maintenance' => 0,
            'backup' => 0,
        ];
        $total_maintenance_minutes = 0;
        
        foreach ($logs as $log) {
            $type = $log['type'] ?? 'unknown';
            // Count theme_plugin as plugin
            if ($type === 'theme_plugin') {
                $type = 'plugin';
            }
            if (isset($type_counts[$type])) {
                $type_counts[$type]++;
            }
            if ($type === 'maintenance' && isset($log['time_spent_minutes'])) {
                $total_maintenance_minutes += intval($log['time_spent_minutes']);
            }
        }
        
        // NEU 1.9.6: Logs nach Typ filtern
        $filtered_logs = $logs;
        if (!empty($filter_type)) {
            $filtered_logs = array_filter($logs, function($log) use ($filter_type) {
                $log_type = $log['type'] ?? '';
                if ($filter_type === 'plugin') {
                    return $log_type === 'plugin' || $log_type === 'theme_plugin';
                }
                return $log_type === $filter_type;
            });
            $filtered_logs = array_values($filtered_logs);
        }
        
        // Pagination
        $per_page = 10;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $total_logs_unfiltered = count($logs);
        $total_logs = count($filtered_logs);
        $total_pages = ceil($total_logs / $per_page);
        
        // Get paginated logs (newest first)
        $reversed_logs = array_reverse($filtered_logs);
        $offset = ($current_page - 1) * $per_page;
        $paginated_logs = array_slice($reversed_logs, $offset, $per_page);
        ?>
        <style>
            .ul-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 1400px; margin-top: 20px; }
            .ul-card { background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; }
            .ul-card-full { grid-column: 1 / -1; }
            @media (max-width: 1024px) { .ul-grid { grid-template-columns: 1fr; } }
            .ul-pagination { display: flex; gap: 5px; align-items: center; margin-top: 15px; flex-wrap: wrap; }
            .ul-pagination a, .ul-pagination span { padding: 5px 10px; text-decoration: none; border: 1px solid #ccd0d4; background: #fff; }
            .ul-pagination a:hover { background: #f0f0f1; }
            .ul-pagination .current { background: #2271b1; color: #fff; border-color: #2271b1; }
            .ul-copy-btn { cursor: pointer; padding: 4px 8px; margin-left: 8px; background: #f0f0f1; border: 1px solid #ccd0d4; border-radius: 3px; font-size: 12px; }
            .ul-copy-btn:hover { background: #e0e0e0; }
            .ul-copy-btn.copied { background: #46b450; color: #fff; border-color: #46b450; }
            .ul-filter-select { padding: 4px 8px; border: 1px solid #ccd0d4; border-radius: 3px; font-size: 13px; background: #fff; }
        </style>
        <div class="wrap">
            <h1>📋 Update Logger API <small style="font-size: 12px; color: #666;">v1.9.7</small></h1>
            
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
                        <div style="background: #e1f5fe; padding: 12px 20px; border-radius: 8px; text-align: center;">
                            <div style="font-size: 22px; font-weight: bold; color: #0277bd;"><?php echo $type_counts['backup']; ?></div>
                            <div style="color: #0277bd; font-size: 12px;">💾 Backups</div>
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
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h2 style="margin: 0;">📋 Letzte Updates</h2>
                        <!-- NEU 1.9.6: Filter-Dropdown -->
                        <select id="ul-type-filter" class="ul-filter-select" onchange="window.location.href=this.value;">
                            <option value="<?php echo esc_url(add_query_arg(['filter_type' => '', 'paged' => 1])); ?>" <?php selected($filter_type, ''); ?>>Alle Typen</option>
                            <option value="<?php echo esc_url(add_query_arg(['filter_type' => 'plugin', 'paged' => 1])); ?>" <?php selected($filter_type, 'plugin'); ?>>🔌 Plugin (<?php echo $type_counts['plugin']; ?>)</option>
                            <option value="<?php echo esc_url(add_query_arg(['filter_type' => 'theme', 'paged' => 1])); ?>" <?php selected($filter_type, 'theme'); ?>>🎨 Theme (<?php echo $type_counts['theme']; ?>)</option>
                            <option value="<?php echo esc_url(add_query_arg(['filter_type' => 'core', 'paged' => 1])); ?>" <?php selected($filter_type, 'core'); ?>>⚙️ Core (<?php echo $type_counts['core']; ?>)</option>
                            <option value="<?php echo esc_url(add_query_arg(['filter_type' => 'database', 'paged' => 1])); ?>" <?php selected($filter_type, 'database'); ?>>🗄️ Datenbank (<?php echo $type_counts['database']; ?>)</option>
                            <option value="<?php echo esc_url(add_query_arg(['filter_type' => 'maintenance', 'paged' => 1])); ?>" <?php selected($filter_type, 'maintenance'); ?>>🔧 Wartung (<?php echo $type_counts['maintenance']; ?>)</option>
                            <option value="<?php echo esc_url(add_query_arg(['filter_type' => 'backup', 'paged' => 1])); ?>" <?php selected($filter_type, 'backup'); ?>>💾 Backup (<?php echo $type_counts['backup']; ?>)</option>
                        </select>
                    </div>
                    
                    <?php if (!empty($filter_type)): ?>
                    <p style="color: #666; font-size: 12px; margin: 0 0 10px;">
                        Gefiltert: <?php echo $total_logs; ?> von <?php echo $total_logs_unfiltered; ?> Einträgen
                        — <a href="<?php echo esc_url(add_query_arg(['filter_type' => '', 'paged' => 1])); ?>">Filter zurücksetzen</a>
                    </p>
                    <?php endif; ?>
                    
                    <?php if (empty($paginated_logs)): ?>
                        <p style="color: #666;">Noch keine Updates protokolliert.</p>
                    <?php else: ?>
                        <table class="widefat striped" style="margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th>Typ</th>
                                    <th>Name</th>
                                    <th>Version</th>
                                    <th>Datum</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($paginated_logs as $i => $log): 
                                    // Calculate original index for deletion (from unfiltered logs)
                                    $original_index = array_search($log, $logs);
                                    if ($original_index === false) {
                                        // Fallback: search in reversed unfiltered logs
                                        $original_index = $total_logs_unfiltered - 1 - $offset - $i;
                                    }
                                    $type_icons = [
                                        'plugin' => '🔌',
                                        'theme_plugin' => '🔌',
                                        'theme' => '🎨',
                                        'core' => '⚙️',
                                        'database' => '🗄️',
                                        'maintenance' => '🔧',
                                        'backup' => '💾',
                                    ];
                                    $icon = $type_icons[$log['type']] ?? '📦';
                                    $log_type = $log['type'];
                                    if ($log_type === 'theme_plugin') {
                                        $log_type = 'plugin';
                                    }
                                    $type_names = [
                                        'plugin' => 'Plugin',
                                        'theme' => 'Theme',
                                        'core' => 'Core',
                                        'database' => 'Datenbank',
                                        'maintenance' => 'Wartung',
                                        'backup' => 'Backup',
                                    ];
                                    $type_name = $type_names[$log_type] ?? ucfirst($log_type);
                                ?>
                                <tr>
                                    <td><?php echo $icon; ?><br><small><?php echo esc_html($type_name); ?></small></td>
                                    <td><?php echo esc_html($log['item_name']); ?></td>
                                    <td>
                                        <?php if (!empty($log['old_version']) && !empty($log['new_version'])): ?>
                                            <?php echo esc_html($log['old_version']); ?> → <?php echo esc_html($log['new_version']); ?>
                                        <?php elseif (!empty($log['new_version'])): ?>
                                            <?php echo esc_html($log['new_version']); ?>
                                        <?php elseif (!empty($log['old_version'])): ?>
                                            <?php echo esc_html($log['old_version']); ?>
                                        <?php elseif (!empty($log['description'])): ?>
                                            <small><?php echo esc_html(substr($log['description'], 0, 30)); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><small><?php echo esc_html($log['timestamp']); ?></small></td>
                                    <td>
                                        <button type="button" class="button-link delete-log-entry" data-index="<?php echo $original_index; ?>" data-name="<?php echo esc_attr($log['item_name']); ?>" style="color: #a00;">✕</button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if ($total_pages > 1): ?>
                        <div class="ul-pagination">
                            <?php if ($current_page > 1): ?>
                                <a href="<?php echo add_query_arg('paged', $current_page - 1); ?>">« Zurück</a>
                            <?php endif; ?>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <?php if ($i == $current_page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php elseif ($i <= 3 || $i > $total_pages - 2 || abs($i - $current_page) <= 1): ?>
                                    <a href="<?php echo add_query_arg('paged', $i); ?>"><?php echo $i; ?></a>
                                <?php elseif ($i == 4 && $current_page > 5): ?>
                                    <span>...</span>
                                <?php elseif ($i == $total_pages - 2 && $current_page < $total_pages - 4): ?>
                                    <span>...</span>
                                <?php endif; ?>
                            <?php endfor; ?>
                            
                            <?php if ($current_page < $total_pages): ?>
                                <a href="<?php echo add_query_arg('paged', $current_page + 1); ?>">Weiter »</a>
                            <?php endif; ?>
                            
                            <span style="margin-left: 10px; color: #666;">
                                (<?php echo $total_logs; ?> Einträge)
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <p style="margin-top: 15px;">
                            <button type="button" id="delete-all-logs" class="button" style="color: #a00;">🗑️ Alle Einträge löschen</button>
                        </p>
                    <?php endif; ?>
                </div>
                
                <!-- API-Konfiguration -->
                <div class="ul-card">
                    <h2 style="margin-top: 0;">🔑 API-Konfiguration</h2>
                    
                    <p><strong>API-Schlüssel:</strong></p>
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
                        <code id="api-key-display" style="background: #f0f0f1; padding: 10px; word-break: break-all; font-family: monospace; font-size: 12px; flex: 1;"><?php echo esc_html($api_key ?: 'Nicht konfiguriert'); ?></code>
                        <?php if ($api_key): ?>
                        <button type="button" id="copy-api-key" class="ul-copy-btn" title="API-Schlüssel kopieren">📋 Kopieren</button>
                        <?php endif; ?>
                    </div>
                    <p>
                        <button type="button" id="regenerate-key" class="button button-secondary">🔄 Neuen Schlüssel generieren</button>
                    </p>
                    
                    <hr style="margin: 20px 0; border: none; border-top: 1px solid #e0e0e0;">
                    
                    <p><strong>API-Endpunkt:</strong></p>
                    <p style="background: #f0f0f1; padding: 10px; word-break: break-all; font-family: monospace; font-size: 12px;">
                        <?php echo esc_url($site_url); ?>/wp-json/update-logger/v1/logs
                    </p>
                    
                    <div style="background: #f8f9fa; padding: 12px; margin-top: 15px; border-radius: 4px; font-size: 12px;">
                        <strong>Verwendung:</strong>
                        <pre style="margin: 8px 0 0; white-space: pre-wrap; word-break: break-all;">curl -H "X-API-Key: DEIN_API_KEY" <?php echo esc_url($site_url); ?>/wp-json/update-logger/v1/logs</pre>
                    </div>
                </div>
            </div>
            
            <!-- ZEILE 3: TGMPA Status | Debug -->
            <div class="ul-grid">
                <!-- TGMPA Status -->
                <div class="ul-card">
                    <h2 style="margin-top: 0;">📦 TGMPA Status</h2>
                    
                    <?php if (class_exists('TGM_Plugin_Activation')): ?>
                        <p style="color: #388e3c;">✅ TGM Plugin Activation ist aktiv</p>
                        <?php if (isset($GLOBALS['tgmpa']) && !empty($GLOBALS['tgmpa']->plugins)): ?>
                            <p><strong>Registrierte Plugins:</strong></p>
                            <ul style="margin-left: 20px;">
                                <?php foreach ($GLOBALS['tgmpa']->plugins as $slug => $plugin): ?>
                                    <li><?php echo esc_html($plugin['name'] ?? $slug); ?> <code style="font-size: 11px;">(<?php echo esc_html($slug); ?>)</code></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p style="color: #666;">Keine Plugins registriert.</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p style="color: #666;">❌ TGM Plugin Activation ist nicht aktiv.</p>
                        <p style="font-size: 12px; color: #999;">Theme-gebündelte Plugins werden über andere Mechanismen erkannt.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Debug-Modus -->
                <div class="ul-card">
                    <h2 style="margin-top: 0;">🐛 Debug-Modus</h2>
                    
                    <?php if (UPDATE_LOGGER_DEBUG): ?>
                        <p style="color: #f57c00;">⚠️ Debug-Modus ist <strong>aktiviert</strong></p>
                        <p style="font-size: 12px; color: #666;">Alle Aktionen werden in den PHP-Error-Log geschrieben.</p>
                    <?php else: ?>
                        <p style="color: #666;">Debug-Modus ist deaktiviert.</p>
                        <p style="font-size: 12px; color: #999;">
                            Zum Aktivieren in <code>wp-config.php</code> hinzufügen:<br>
                            <code>define('UPDATE_LOGGER_DEBUG', true);</code>
                        </p>
                    <?php endif; ?>
                    
                    <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
                    
                    <h3 style="font-size: 14px; margin: 0 0 10px;">Elementor Status</h3>
                    <?php if (defined('ELEMENTOR_VERSION')): ?>
                        <p style="color: #388e3c; margin: 5px 0;">✅ Elementor <?php echo ELEMENTOR_VERSION; ?></p>
                    <?php else: ?>
                        <p style="color: #666; margin: 5px 0;">❌ Elementor nicht aktiv</p>
                    <?php endif; ?>
                    
                    <?php if (defined('ELEMENTOR_PRO_VERSION')): ?>
                        <p style="color: #388e3c; margin: 5px 0;">✅ Elementor Pro <?php echo ELEMENTOR_PRO_VERSION; ?></p>
                    <?php else: ?>
                        <p style="color: #666; margin: 5px 0;">❌ Elementor Pro nicht aktiv</p>
                    <?php endif; ?>
                    
                    <hr style="margin: 15px 0; border: none; border-top: 1px solid #e0e0e0;">
                    
                    <p>
                        <button type="button" id="add-test-log" class="button">🧪 Test-Eintrag erstellen</button>
                    </p>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Copy API key - NEU in 1.9.5
            $('#copy-api-key').on('click', function() {
                var apiKey = $('#api-key-display').text();
                var btn = $(this);
                
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(apiKey).then(function() {
                        btn.text('✓ Kopiert!').addClass('copied');
                        setTimeout(function() {
                            btn.text('📋 Kopieren').removeClass('copied');
                        }, 2000);
                    }).catch(function() {
                        fallbackCopy(apiKey, btn);
                    });
                } else {
                    fallbackCopy(apiKey, btn);
                }
            });
            
            function fallbackCopy(text, btn) {
                var temp = $('<textarea>');
                $('body').append(temp);
                temp.val(text).select();
                document.execCommand('copy');
                temp.remove();
                btn.text('✓ Kopiert!').addClass('copied');
                setTimeout(function() {
                    btn.text('📋 Kopieren').removeClass('copied');
                }, 2000);
            }
            
            // Regenerate API key
            $('#regenerate-key').on('click', function() {
                if (!confirm('Sind Sie sicher? Der alte Schlüssel wird ungültig.')) {
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'regenerate_api_key',
                    nonce: '<?php echo wp_create_nonce('regenerate_api_key'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#api-key-display').text(response.data.key);
                        alert('Neuer API-Schlüssel wurde generiert!');
                    }
                });
            });
            
            // Add test log
            $('#add-test-log').on('click', function() {
                $.post(ajaxurl, {
                    action: 'add_test_log',
                    nonce: '<?php echo wp_create_nonce('add_test_log'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    }
                });
            });
            
            // Delete single log entry
            $('.delete-log-entry').on('click', function() {
                var index = $(this).data('index');
                var name = $(this).data('name');
                
                if (!confirm('Eintrag "' + name + '" wirklich löschen?')) {
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'delete_log_entry',
                    nonce: '<?php echo wp_create_nonce('delete_log_entry'); ?>',
                    index: index
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                });
            });
            
            // Delete all logs
            $('#delete-all-logs').on('click', function() {
                if (!confirm('ALLE Einträge wirklich löschen? Diese Aktion kann nicht rückgängig gemacht werden!')) {
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'delete_all_logs',
                    nonce: '<?php echo wp_create_nonce('delete_all_logs'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });
            
            // Add maintenance entry
            $('#add-maintenance-entry').on('click', function() {
                var title = $('#maintenance-title').val().trim();
                var description = $('#maintenance-description').val().trim();
                var hours = parseInt($('#maintenance-hours').val()) || 0;
                var minutes = parseInt($('#maintenance-minutes').val()) || 0;
                var totalMinutes = (hours * 60) + minutes;
                
                if (!title) {
                    alert('Bitte geben Sie einen Titel ein.');
                    return;
                }
                
                $.post(ajaxurl, {
                    action: 'add_maintenance_entry',
                    nonce: '<?php echo wp_create_nonce('add_maintenance_entry'); ?>',
                    title: title,
                    description: description,
                    time_spent_minutes: totalMinutes
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Fehler: ' + response.data.message);
                    }
                });
            });
        });
        </script>
        <?php
    }
}

// Initialize the plugin
new Update_Logger_API();
