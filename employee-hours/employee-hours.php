<?php
/*
Plugin Name: Employee Hours
Description: Calculate employee hours from Google Calendar and manage payslips.
Version: 1.0.0
Author: Example Corp
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activation hook: adds Dipendente role
 */
function eh_activate() {
    add_role('dipendente', 'Dipendente', array('read' => true));
}
register_activation_hook(__FILE__, 'eh_activate');

/**
 * Fetch events from Google Calendar
 *
 * @param string $calendar_id
 * @param string $start ISO8601 start date
 * @param string $end   ISO8601 end date
 * @param string $api_key Google API key
 * @return array
 */
function eh_fetch_events($calendar_id, $start, $end, $api_key) {
    $url = add_query_arg(array(
        'timeMin' => $start,
        'timeMax' => $end,
        'singleEvents' => 'true',
        'orderBy' => 'startTime',
        'key' => $api_key,
    ), 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendar_id) . '/events');

    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return array();
    }
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    if (!isset($data['items'])) {
        return array();
    }
    return $data['items'];
}

/**
 * Calculate hours from events
 *
 * @param array $events
 * @return array
 */
function eh_calculate_hours($events) {
    $totals = array(
        'lavoro' => 0,
        'ferie' => 0,
        'malattia' => 0,
        'recupero' => 0,
    );
    foreach ($events as $event) {
        if (empty($event['start']['dateTime']) || empty($event['end']['dateTime'])) {
            continue;
        }
        $start = strtotime($event['start']['dateTime']);
        $end   = strtotime($event['end']['dateTime']);
        $hours = ($end - $start) / 3600;
        $summary = strtolower($event['summary'] ?? '');
        if (strpos($summary, 'ferie') !== false) {
            $totals['ferie'] += $hours;
        } elseif (strpos($summary, 'malattia') !== false) {
            $totals['malattia'] += $hours;
        } elseif (strpos($summary, 'recupero') !== false) {
            $totals['recupero'] += $hours;
        } else {
            $totals['lavoro'] += $hours;
        }
    }
    return $totals;
}

/**
 * Save summary as JSON
 */
function eh_save_summary($user_id, $month, $summary) {
    $upload_dir = wp_upload_dir();
    $dir = trailingslashit($upload_dir['basedir']) . 'payslips/' . intval($user_id);
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
    $file = trailingslashit($dir) . 'summary-' . $month . '.json';
    file_put_contents($file, wp_json_encode($summary));
    return $file;
}

/**
 * Shortcode to list documents for the current user
 */
function eh_documents_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Devi eseguire il login.</p>';
    }
    $user_id = get_current_user_id();
    $upload_dir = wp_upload_dir();
    $dir = trailingslashit($upload_dir['basedir']) . 'payslips/' . intval($user_id);
    if (!file_exists($dir)) {
        return '<p>Nessun documento disponibile.</p>';
    }
    $files = array_diff(scandir($dir), array('..', '.'));
    if (empty($files)) {
        return '<p>Nessun documento disponibile.</p>';
    }
    $out = '<ul>';
    foreach ($files as $file) {
        $url = trailingslashit($upload_dir['baseurl']) . 'payslips/' . intval($user_id) . '/' . rawurlencode($file);
        $out .= '<li><a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html($file) . '</a></li>';
    }
    $out .= '</ul>';
    return $out;
}
add_shortcode('employee_documents', 'eh_documents_shortcode');

/**
 * Admin page for uploading payslips
 */
function eh_admin_menu() {
    add_users_page('Payslip Upload', 'Payslip Upload', 'manage_options', 'eh-upload', 'eh_upload_page');
}
add_action('admin_menu', 'eh_admin_menu');

function eh_upload_page() {
    if (!current_user_can('manage_options')) {
        return;
    }
    if (isset($_POST['eh_upload_nonce']) && wp_verify_nonce($_POST['eh_upload_nonce'], 'eh_upload')) {
        $user_id = intval($_POST['user_id']);
        if (!empty($_FILES['payslip']['name'])) {
            $upload_dir = wp_upload_dir();
            $dir = trailingslashit($upload_dir['basedir']) . 'payslips/' . $user_id;
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
            }
            $filename = sanitize_file_name($_FILES['payslip']['name']);
            move_uploaded_file($_FILES['payslip']['tmp_name'], trailingslashit($dir) . $filename);
            $user = get_user_by('id', $user_id);
            if ($user) {
                $url = trailingslashit($upload_dir['baseurl']) . 'payslips/' . $user_id . '/' . rawurlencode($filename);
                wp_mail($user->user_email, 'Nuova busta paga', 'Ciao, puoi scaricare la tua busta paga qui: ' . $url);
            }
            echo '<div class="updated"><p>File caricato con successo.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h1>Carica busta paga</h1>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('eh_upload', 'eh_upload_nonce'); ?>
            <p>
                <label for="user_id">Dipendente</label>
                <?php
                wp_dropdown_users(array('name' => 'user_id', 'role' => 'dipendente'));
                ?>
            </p>
            <p>
                <input type="file" name="payslip" required />
            </p>
            <p>
                <input type="submit" class="button button-primary" value="Carica" />
            </p>
        </form>
    </div>
    <?php
}
