<?php
/*
Plugin Name: Buste Paga Dipendenti
Description: Gestione ore e buste paga per dipendenti integrato con Google Calendar.
Version: 0.1
Author: gattipc
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Bustepaga_Plugin {
    const OPTION_NAME = 'bustepaga_settings';
    const CRON_HOOK   = 'bustepaga_hourly_sync';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        add_action('admin_menu', array($this, 'admin_menu'));
        add_action(self::CRON_HOOK, array($this, 'sync_calendar'));
        add_shortcode('bustepaga_payslips', array($this, 'render_payslips'));
    }

    public function activate() {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'hourly', self::CRON_HOOK);
        }
    }

    public function deactivate() {
        wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    public function admin_menu() {
        add_menu_page('Buste Paga', 'Buste Paga', 'manage_options', 'bustepaga', array($this, 'settings_page'));
    }

    public function settings_page() {
        if (!current_user_can('manage_options')) {
            return;
        }
        if (isset($_POST['bustepaga_settings'])) {
            check_admin_referer('bustepaga_save_settings');
            update_option(self::OPTION_NAME, wp_unslash($_POST['bustepaga_settings']));
            echo '<div class="updated"><p>Impostazioni salvate.</p></div>';
        }
        $settings = get_option(self::OPTION_NAME, array());
        ?>
        <div class="wrap">
            <h1>Buste Paga - Impostazioni</h1>
            <form method="post">
                <?php wp_nonce_field('bustepaga_save_settings'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row">JSON Config (API Google)</th>
                        <td>
                            <textarea name="bustepaga_settings[json]" rows="10" cols="50" class="large-text code"><?php echo esc_textarea(isset($settings['json']) ? $settings['json'] : ''); ?></textarea>
                            <p class="description">Inserisci il file JSON delle credenziali per Google API.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Email Commercialista</th>
                        <td><input type="email" name="bustepaga_settings[accountant_email]" value="<?php echo esc_attr(isset($settings['accountant_email']) ? $settings['accountant_email'] : ''); ?>" class="regular-text" /></td>
                    </tr>
                </table>
                <?php submit_button('Salva Impostazioni'); ?>
            </form>
        </div>
        <?php
    }

    public function sync_calendar() {
        $settings = get_option(self::OPTION_NAME, array());
        // Qui si dovrebbe implementare l'integrazione con Google Calendar.
        // Questo è solo un esempio semplificato che non effettua realmente la chiamata.
        // In produzione occorrerebbe utilizzare le Google API PHP Client.

        $summary = array(
            'lavoro'   => 0,
            'ferie'    => 0,
            'malattia' => 0,
            'recupero' => 0,
        );

        // TODO: recuperare eventi dal calendario e popolare $summary

        // Invio mail al commercialista in formato JSON
        if (!empty($settings['accountant_email'])) {
            $subject = 'Riepilogo ore dipendenti';
            $message = json_encode($summary);
            wp_mail($settings['accountant_email'], $subject, $message);
        }
    }

    public function render_payslips($atts) {
        if (!is_user_logged_in()) {
            return '<p>Effettua il login per accedere alle tue buste paga.</p>';
        }
        $current_user = wp_get_current_user();
        $uploads = wp_upload_dir();
        $user_dir = trailingslashit($uploads['basedir']) . 'bustepaga/' . $current_user->user_login;
        $output = '<h2>Buste paga</h2><ul>';
        if (is_dir($user_dir)) {
            foreach (glob($user_dir . '/*.pdf') as $file) {
                $filename = basename($file);
                $url = trailingslashit($uploads['baseurl']) . 'bustepaga/' . $current_user->user_login . '/' . $filename;
                $output .= '<li><a href="' . esc_url($url) . '" target="_blank">' . esc_html($filename) . '</a></li>';
            }
        } else {
            $output .= '<li>Nessuna busta paga presente.</li>';
        }
        $output .= '</ul>';
        return $output;
    }
}

new Bustepaga_Plugin();
