<?php
/*
Plugin Name: JSON File Editor Kind
Description: A kinder, more beautiful plugin to edit JSON files for events and announcements via shortcodes.
Version: 1.2
Author: Jules
*/

if (!defined('ABSPATH')) exit;

// Create data directory on plugin activation
function jfek_activate_plugin() {
    $data_dir = WP_CONTENT_DIR . '/dataforapp';
    if (!file_exists($data_dir)) {
        wp_mkdir_p($data_dir);
    }
}
register_activation_hook(__FILE__, 'jfek_activate_plugin');

// --- Overhauled Event Editor ---

function jfek_event_editor_shortcode() {
    if (is_admin() && !wp_doing_ajax()) {
        return '<em>[event_editor_kind] shortcode is active. The new editor will be displayed on the public page.</em>';
    }

    // Enqueue the new script for the event editor
    wp_enqueue_script('jfek-event-editor-script', plugin_dir_url(__FILE__) . 'event-editor-script.js', ['jquery'], '1.0', true);
    wp_localize_script('jfek-event-editor-script', 'jfekAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('jfek_nonce')
    ]);

    ob_start();
    ?>
    <style>
        /* General Styles */
        .jfek-editor-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; max-width: 1200px; margin: 20px auto; }
        .jfek-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #0073aa; }
        .jfek-header h1 { margin: 0; font-size: 22px; }
        #jfek-save-btn { padding: 10px 20px; font-size: 16px; background-color: #0085ba; border: 1px solid #0073aa; color: #fff; cursor: pointer; border-radius: 4px; }
        #jfek-save-btn:hover { background-color: #0073aa; }
        #jfek-save-btn:disabled { background-color: #ccc; border-color: #bbb; cursor: not-allowed; }
        .jfek-event-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        
        /* Card Styles */
        .jfek-event-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); }
        .jfek-event-card h2 { margin-top: 0; font-size: 18px; color: #222; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px; }
        
        /* Field Styles */
        .jfek-field { margin-bottom: 18px; }
        .jfek-field label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; color: #333; }
        .jfek-field select { width: 100%; padding: 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 4px; background-color: #f9f9f9; }
        
        /* Read-only Info Styles */
        .jfek-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px; font-size: 14px; color: #555; }
        .jfek-info-grid strong { color: #111; }

        /* Toggle Switch */
        .jfek-toggle { display: flex; align-items: center; gap: 10px; }
        .jfek-toggle-switch { position: relative; width: 50px; height: 26px; background: #ccc; border-radius: 13px; cursor: pointer; transition: background 0.3s; }
        .jfek-toggle-switch.active { background: #0073aa; }
        .jfek-toggle-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: left 0.3s; }
        .jfek-toggle-switch.active::after { left: 27px; }
        .jfek-toggle-label { font-size: 14px; }
    </style>
    <div id="jfek-event-editor" class="jfek-editor-wrap">
        <div class="jfek-header">
            <h1>イベント編集</h1>
            <button id="jfek-save-btn" disabled>変更を保存</button>
        </div>
        <div id="jfek-event-grid" class="jfek-event-grid">
            <p>読み込み中...</p>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('event_editor_kind', 'jfek_event_editor_shortcode');

// --- Dashboard Shortcode ---

function jfek_dashboard_shortcode() {
    ob_start();
    ?>
    <style>
        .jfek-dashboard-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; max-width: 960px; margin: 40px auto; text-align: center; }
        .jfek-dashboard-wrap h1 { font-size: 32px; font-weight: 600; color: #2c3e50; margin-bottom: 40px; }
        .jfek-dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 30px; }
        .jfek-dashboard-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 30px; text-decoration: none; color: #333; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: all 0.3s ease; }
        .jfek-dashboard-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px rgba(0,0,0,0.1); }
        .jfek-dashboard-card h2 { font-size: 20px; font-weight: 600; margin-top: 0; margin-bottom: 15px; color: #0073aa; }
        .jfek-dashboard-card p { font-size: 15px; color: #5a6570; line-height: 1.6; }
    </style>
    <div class="jfek-dashboard-wrap">
        <h1>委員会専用アプリ表示編集ホーム</h1>
        <div class="jfek-dashboard-grid">
            <a href="URL_TO_5253_EDITOR_PAGE" class="jfek-dashboard-card">
                <h2>52・53号館</h2>
                <p>52・53号館の企画のステータスを編集します。</p>
            </a>
            <a href="URL_TO_ANNOUNCEMENTS_EDITOR_PAGE" class="jfek-dashboard-card">
                <h2>お知らせ</h2>
                <p>アプリ内に表示されるお知らせを追加・編集します。</p>
            </a>
            <a href="URL_TO_EVENTS_EDITOR_PAGE" class="jfek-dashboard-card">
                <h2>イベント情報</h2>
                <p>イベントのステータスや予約状況を編集します。</p>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('json_editor_dashboard', 'jfek_dashboard_shortcode');


// --- Dedicated, Standalone Editor ---

function jfek_restricted_editor_shortcode($atts) {
    // Default to 'announcements.json' for backward compatibility if no file is specified.
    $atts = shortcode_atts(['file' => 'announcements.json'], $atts, 'announcement_editor_kind');
    $file_to_edit = sanitize_file_name($atts['file']);

<<<<<<< Updated upstream
=======
    // Whitelist the files that can be edited.
    $allowed_files = ['5253.json', 'events.json', 'announcements.json'];
    if (!in_array($file_to_edit, $allowed_files)) {
        return '<div class="jfek-error">エラー: このファイルの編集は許可されていません。</div>';
    }

>>>>>>> Stashed changes
    // Enqueue scripts and styles
    wp_enqueue_script('jfek-public-script', plugin_dir_url(__FILE__) . 'public-script.js', ['jquery'], '1.1', true);
    wp_localize_script('jfek-public-script', 'jfekAjax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('jfek_nonce'),
        'file_to_edit' => $file_to_edit // Pass the file to JS
    ]);
    
    ob_start();
    ?>
    <style>
        .kjm-admin { margin: 20px auto; max-width: 1000px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; }
        .kjm-main { width: 100%; background: #fff; padding: 25px; border: 1px solid #ccd0d4; box-shadow: 0 1px 4px rgba(0,0,0,.07); border-radius: 8px; }
        .kjm-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 20px; border-bottom: 2px solid #0073aa; }
        .kjm-header h1 { margin: 0; font-size: 24px; }
        #kjm-save-btn { padding: 10px 20px; font-size: 16px; background-color: #0085ba; border-color: #0073aa; color: #fff; cursor: pointer; border-radius: 4px; }
        #kjm-save-btn:disabled { background-color: #ccc; border-color: #bbb; cursor: not-allowed; }
        .kjm-item-selector { background-color: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px; }
        .kjm-item-selector label { font-weight: 600; }
        .kjm-item-selector select { flex-grow: 1; padding: 8px; border-radius: 4px; border: 1px solid #ddd; }
        .kjm-item { background: #f8f9fa; border: 1px solid #e2e8f0; padding: 20px; margin-bottom: 20px; border-radius: 8px; }
        .kjm-field { margin-bottom: 18px; }
        .kjm-field label { display: block; font-weight: 600; margin-bottom: 8px; font-size: 14px; }
        .kjm-field input[type="text"], .kjm-field input[type="url"], .kjm-field textarea, .kjm-field select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .kjm-field input[readonly] { background-color: #e9ecef; }
        .kjm-toggle { display: flex; align-items: center; gap: 10px; }
        .kjm-toggle-switch { position: relative; width: 50px; height: 26px; background: #ccc; border-radius: 13px; cursor: pointer; transition: background 0.3s; }
        .kjm-toggle-switch.active { background: #0073aa; }
        .kjm-toggle-switch::after { content: ''; position: absolute; top: 3px; left: 3px; width: 20px; height: 20px; background: #fff; border-radius: 50%; transition: left 0.3s; }
        .kjm-toggle-switch.active::after { left: 27px; }
    </style>
    <div class="wrap kjm-admin">
        <div class="kjm-main">
            <div id="kjm-content">
                <div class="kjm-header">
                    <h1 id="kjm-file-title">読み込み中...</h1>
                    <button id="kjm-save-btn" disabled>変更を保存</button>
                </div>
                <div id="kjm-item-selector" class="kjm-item-selector" style="display:none;">
                    <label for="kjm-item-select">編集項目:</label>
                    <select id="kjm-item-select"></select>
                </div>
                <div id="kjm-editor"><p>データを読み込んでいます...</p></div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('announcement_editor_kind', 'jfek_restricted_editor_shortcode');

// AJAX handlers for both editors
class JFEK_Restricted_Editor_AJAX {
    private $json_dir;

    public function __construct() {
        $this->json_dir = WP_CONTENT_DIR . '/dataforapp/';
        add_action('wp_ajax_jfek_load_json', [$this, 'ajax_load_json']);
<<<<<<< Updated upstream
        add_action('wp_ajax_jfek_save_json', [$this, 'ajax_save_json']);
=======
        add_action('wp_ajax_nopriv_jfek_load_json', [$this, 'ajax_load_json']);
        add_action('wp_ajax_jfek_save_json', [$this, 'ajax_save_json']);
        add_action('wp_ajax_nopriv_jfek_save_json', [$this, 'ajax_save_json']);
>>>>>>> Stashed changes
    }

    public function ajax_load_json() {
        check_ajax_referer('jfek_nonce', 'nonce');
        
        $file = sanitize_file_name($_POST['file']);
        $allowed_files = ['5253.json', 'events.json', 'announcements.json'];
        if (!in_array($file, $allowed_files)) {
            wp_send_json_error('許可されていないファイルです');
        }

        $filepath = $this->json_dir . $file;
        if (!file_exists($filepath)) {
            wp_send_json_error('ファイルが見つかりません');
        }
        
        $content = file_get_contents($filepath);
        $data = json_decode($content, true);
        
        wp_send_json_success(['file' => $file, 'data' => $data]);
    }

    public function ajax_save_json() {
        check_ajax_referer('jfek_nonce', 'nonce');

        $file = sanitize_file_name($_POST['file']);
        $data = stripslashes($_POST['data']);
        $new_data = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('無効なJSON形式です');
        }

        $filepath = $this->json_dir . $file;
        if (!file_exists($filepath)) {
            wp_send_json_error('ファイルが見つかりません');
        }
        
        $original_data = json_decode(file_get_contents($filepath), true);

        if ($file === 'events.json') {
<<<<<<< Updated upstream
            $allowed_fields = ['status', 'reservation', 'reservationSlots', 'waitinglist', 'waitingSumSelect'];
            foreach ($new_data as $index => &$event) {
                // Also need to allow the fields that are not editable, but are part of the event object
                $all_fields = array_merge($allowed_fields, ['eventName', 'time', 'description', 'date', 'image_L', 'image_B', 'groupName', 'snsLink', 'caution', 'others']);
                foreach ($event as $key => $value) {
                    if (!in_array($key, $allowed_fields) && isset($original_data[$index][$key]) && $original_data[$index][$key] !== $value) {
                        wp_send_json_error("`{$key}` a field that cannot be edited.");
=======
            $allowed_statuses = ['available', 'few_left', 'closed', 'full'];
            $processed_data = [];

            foreach ($original_data as $index => $original_event) {
                if (!isset($new_data[$index])) continue;

                $updated_event = $original_event;
                $submitted_event = $new_data[$index];

                if (isset($submitted_event['status'])) {
                    $updated_event['status'] = ($submitted_event['status'] === true || $submitted_event['status'] === 'true');
                }
                if (isset($submitted_event['waitingSumSelect'])) {
                    $updated_event['waitingSumSelect'] = absint($submitted_event['waitingSumSelect']);
                }
                if (isset($submitted_event['reservationSlots']) && is_array($submitted_event['reservationSlots'])) {
                    $validated_slots = [];
                    foreach ($submitted_event['reservationSlots'] as $slot) {
                        if (isset($slot['time']) && isset($slot['status']) && in_array($slot['status'], $allowed_statuses)) {
                            $validated_slots[] = [
                                'time' => sanitize_text_field($slot['time']),
                                'status' => sanitize_text_field($slot['status']),
                            ];
                        }
>>>>>>> Stashed changes
                    }
                    $updated_event['reservationSlots'] = $validated_slots;
                }
                
                $processed_data[] = $updated_event;
            }

            if (count($processed_data) !== count($original_data)) {
                wp_send_json_error('イベントの削除は許可されていません。');
            }
            $new_data = $processed_data;
        } elseif ($file === 'announcements.json') {
            $processed_data = [];
            $existing_ids = array_column($original_data, 'id');
            $max_id = count($existing_ids) > 0 ? max($existing_ids) : 0;

            foreach ($new_data as $index => $submitted_item) {
                // Check if it's a new item or an existing one
                if (isset($submitted_item['id']) && in_array($submitted_item['id'], $existing_ids)) {
                    // Existing item
                    $original_item = $original_data[array_search($submitted_item['id'], $existing_ids)];
                    $updated_item = $original_item;
                    $updated_item['message'] = sanitize_textarea_field($submitted_item['message']);
                    $updated_item['enabled'] = ($submitted_item['enabled'] === true || $submitted_item['enabled'] === 'true');
                    $processed_data[] = $updated_item;
                } else {
                    // New item
                    $max_id++;
                    $new_item = [
                        'id' => $max_id,
                        'message' => sanitize_textarea_field($submitted_item['message']),
                        'date' => current_time('Y-m-d'),
                        'important' => false,
                        'new' => true,
                        'enabled' => ($submitted_item['enabled'] === true || $submitted_item['enabled'] === 'true')
                    ];
                    $processed_data[] = $new_item;
                }
            }
<<<<<<< Updated upstream
        } elseif ($file === 'announcements.json') {
            if (count($new_data) < count($original_data)) {
                wp_send_json_error('Announcements cannot be deleted.');
            }
        }

        if (file_put_contents($filepath, json_encode($new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
=======
            if (count($new_data) < count($original_data)) {
                wp_send_json_error('お知らせの削除は許可されていません。');
            }
            $new_data = $processed_data;
        } elseif ($file === '5253.json') {
            $processed_data = [];
            foreach ($original_data as $key => $original_item) {
                if (isset($new_data[$key])) {
                    $updated_item = $original_item;
                    $updated_item['status'] = ($new_data[$key]['status'] === true || $new_data[$key]['status'] === 'true');
                    $processed_data[$key] = $updated_item;
                }
            }
            $new_data = $processed_data;
        }

        if (file_put_contents($filepath, json_encode($new_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false) {
>>>>>>> Stashed changes
            wp_send_json_error('ファイルの保存に失敗しました。');
        }

        wp_send_json_success(['message' => '保存しました']);
    }
}
new JFEK_Restricted_Editor_AJAX();