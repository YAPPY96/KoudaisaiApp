<?php
/**
 * Plugin Name: Koudaisai JSON Manager
 * Description: 工大祭アプリ用JSONファイル管理プラグイン
 * Version: 1.0.0
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

class Koudaisai_JSON_Manager {
    private $json_dir;
    
    public function __construct() {
        $this->json_dir = WP_CONTENT_DIR . '/dataforapp/';
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_kjm_load_json', [$this, 'ajax_load_json']);
        add_action('wp_ajax_kjm_save_json', [$this, 'ajax_save_json']);
        add_action('wp_ajax_kjm_toggle_5253_status', [$this, 'ajax_toggle_5253_status']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            '工大祭JSON管理',
            '工大祭JSON',
            'manage_options',
            'koudaisai-json-manager',
            [$this, 'render_admin_page'],
            'dashicons-database',
            30
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_koudaisai-json-manager') return;
        
        wp_enqueue_style('kjm-admin-style', plugin_dir_url(__FILE__) . 'admin-style.css');
        wp_enqueue_script('kjm-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', ['jquery'], '1.0', true);
        wp_localize_script('kjm-admin-script', 'kjmAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('kjm_nonce')
        ]);
    }
    
    public function render_admin_page() {
        ?>
        <div class="wrap kjm-admin">
            <h1>工大祭 JSON ファイル管理</h1>
            
            <div class="kjm-container">
                <div class="kjm-sidebar">
                    <h2>ファイル選択</h2>
                    <div class="kjm-file-list">
                        <button class="kjm-file-btn" data-file="events.json">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            イベント情報 (events.json)
                        </button>
                        <button class="kjm-file-btn" data-file="stage.json">
                            <span class="dashicons dashicons-megaphone"></span>
                            ステージ企画 (stage.json)
                        </button>
                        <button class="kjm-file-btn" data-file="5253.json">
                            <span class="dashicons dashicons-admin-home"></span>
                            52・53号館 (5253.json)
                        </button>
                        <button class="kjm-file-btn" data-file="others.json">
                            <span class="dashicons dashicons-admin-links"></span>
                            その他 (others.json)
                        </button>
                        <button class="kjm-file-btn" data-file="announcements.json">
                            <span class="dashicons dashicons-megaphone"></span>
                            お知らせ (announcements.json)
                        </button>
                    </div>
                </div>
                
                <div class="kjm-main">
                    <div id="kjm-loading" class="kjm-loading" style="display:none;">
                        <span class="spinner is-active"></span>
                        読み込み中...
                    </div>
                    
                    <div id="kjm-content" class="kjm-content" style="display:none;">
                        <div class="kjm-header">
                            <h2 id="kjm-file-title"></h2>
                            <button id="kjm-save-btn" class="button button-primary button-large">
                                <span class="dashicons dashicons-yes"></span>
                                保存
                            </button>
                        </div>
                        
                        <div id="kjm-item-selector" class="kjm-item-selector" style="display:none;">
                            <div style="display:flex;justify-content:space-between;align-items:center;">
                                <div style="flex:1;">
                                    <label for="kjm-item-select">編集する項目を選択:</label>
                                    <select id="kjm-item-select" class="kjm-select">
                                        <option value="">-- 選択してください --</option>
                                    </select>
                                </div>
                                <button id="kjm-add-item-btn" class="button button-secondary" style="margin-left:15px;">
                                    <span class="dashicons dashicons-plus-alt"></span>
                                    新規項目追加
                                </button>
                            </div>
                        </div>
                        
                        <div id="kjm-editor"></div>
                    </div>
                    
                    <div id="kjm-empty" class="kjm-empty">
                        <span class="dashicons dashicons-admin-page"></span>
                        <p>左側からファイルを選択してください</p>
                    </div>
                </div>
            </div>
        </div>
        
        <style>
        .kjm-admin {
            margin: 20px;
        }
        .kjm-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }
        .kjm-sidebar {
            width: 300px;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        .kjm-sidebar h2 {
            margin-top: 0;
            font-size: 16px;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        .kjm-file-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 15px;
        }
        .kjm-file-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 15px;
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            cursor: pointer;
            text-align: left;
            font-size: 14px;
            transition: all 0.2s;
        }
        .kjm-file-btn:hover {
            background: #2271b1;
            color: #fff;
            border-color: #2271b1;
        }
        .kjm-file-btn.active {
            background: #2271b1;
            color: #fff;
            border-color: #2271b1;
        }
        .kjm-file-btn .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        .kjm-main {
            flex: 1;
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
            min-height: 600px;
        }
        .kjm-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #2271b1;
        }
        .kjm-header h2 {
            margin: 0;
            font-size: 20px;
        }
        .kjm-loading {
            text-align: center;
            padding: 50px;
            font-size: 16px;
        }
        .kjm-empty {
            text-align: center;
            padding: 100px 20px;
            color: #787c82;
        }
        .kjm-empty .dashicons {
            font-size: 80px;
            width: 80px;
            height: 80px;
            opacity: 0.3;
        }
        .kjm-empty p {
            font-size: 18px;
            margin-top: 20px;
        }
        .kjm-item {
            background: #f6f7f7;
            border: 1px solid #dcdcde;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .kjm-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dcdcde;
        }
        .kjm-item-title {
            font-weight: 600;
            font-size: 15px;
        }
        .kjm-item-actions {
            display: flex;
            gap: 10px;
        }
        .kjm-field {
            margin-bottom: 15px;
        }
        .kjm-field label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .kjm-field input[type="text"],
        .kjm-field input[type="url"],
        .kjm-field textarea {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
        }
        .kjm-field textarea {
            min-height: 100px;
            font-family: monospace;
        }
        .kjm-field input[type="checkbox"] {
            margin-right: 5px;
        }
        .kjm-toggle {
            display: inline-flex;
            align-items: center;
            gap: 10px;
        }
        .kjm-toggle-label {
            font-size: 13px;
            color: #50575e;
            min-width: 40px;
        }
        .kjm-toggle-switch {
            position: relative;
            width: 50px;
            height: 26px;
            background: #8c8f94;
            border-radius: 13px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .kjm-toggle-switch.active {
            background: #2271b1;
        }
        .kjm-toggle-switch::after {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 20px;
            height: 20px;
            background: #fff;
            border-radius: 50%;
            transition: left 0.3s;
        }
        .kjm-toggle-switch.active::after {
            left: 27px;
        }
        .kjm-5253-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        .kjm-5253-room {
            background: #fff;
            border: 1px solid #dcdcde;
            padding: 15px;
            border-radius: 4px;
        }
        .kjm-5253-room-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .kjm-5253-room-name {
            font-weight: 600;
            font-size: 14px;
        }
        .kjm-5253-event {
            font-size: 13px;
            color: #50575e;
            margin-bottom: 8px;
        }
        .kjm-item-selector {
            background: #f0f0f1;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border: 1px solid #dcdcde;
        }
        .kjm-item-selector label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .kjm-select {
            width: 100%;
            max-width: 500px;
            padding: 8px 12px;
            font-size: 14px;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            background: #fff;
        }
        #kjm-add-item-btn {
            padding: 8px 16px;
            height: auto;
            white-space: nowrap;
        }
        #kjm-add-item-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            margin-top: 2px;
        }
        .kjm-delete-item-btn {
            background: #d63638;
            color: #fff;
            border: 1px solid #d63638;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .kjm-delete-item-btn:hover {
            background: #b32d2e;
            border-color: #b32d2e;
        }
        .kjm-delete-item-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
        }
        .kjm-success {
            background: #d7f1e3;
            border-left: 4px solid #00a32a;
            padding: 12px;
            margin: 15px 0;
        }
        .kjm-error {
            background: #fcf0f1;
            border-left: 4px solid #d63638;
            padding: 12px;
            margin: 15px 0;
        }
        </style>
        <?php
    }
    
    public function ajax_load_json() {
        check_ajax_referer('kjm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $file = sanitize_file_name($_POST['file']);
        $filepath = $this->json_dir . $file;
        
        if (!file_exists($filepath)) {
            wp_send_json_error('ファイルが見つかりません');
        }
        
        $content = file_get_contents($filepath);
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('JSONの解析に失敗しました');
        }
        
        wp_send_json_success([
            'file' => $file,
            'data' => $data
        ]);
    }
    
    public function ajax_save_json() {
        check_ajax_referer('kjm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $file = sanitize_file_name($_POST['file']);
        $data = stripslashes($_POST['data']);
        
        // JSONの妥当性チェック
        $decoded = json_decode($data);
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error('無効なJSON形式です: ' . json_last_error_msg());
        }
        
        $filepath = $this->json_dir . $file;
        
        // ディレクトリが存在するか確認
        if (!is_dir($this->json_dir)) {
            wp_send_json_error('dataforappディレクトリが存在しません');
        }
        
        // バックアップ作成
        if (file_exists($filepath)) {
            $backup = $filepath . '.backup.' . time();
            if (!copy($filepath, $backup)) {
                wp_send_json_error('バックアップの作成に失敗しました');
            }
        }
        
        // ファイル保存（整形されたJSON）
        $formatted_json = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents($filepath, $formatted_json) === false) {
            wp_send_json_error('ファイルの保存に失敗しました。ファイルのパーミッションを確認してください。');
        }
        
        // 保存されたか確認
        if (!file_exists($filepath)) {
            wp_send_json_error('保存後のファイル確認に失敗しました');
        }
        
        wp_send_json_success([
            'message' => '保存しました',
            'filepath' => $filepath,
            'filesize' => filesize($filepath)
        ]);
    }
    
    public function ajax_toggle_5253_status() {
        check_ajax_referer('kjm_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('権限がありません');
        }
        
        $room = sanitize_text_field($_POST['room']);
        $filepath = $this->json_dir . '5253.json';
        
        if (!file_exists($filepath)) {
            wp_send_json_error('ファイルが見つかりません');
        }
        
        $content = file_get_contents($filepath);
        $data = json_decode($content, true);
        
        if (!isset($data[$room])) {
            wp_send_json_error('部屋が見つかりません');
        }
        
        $data[$room]['status'] = !$data[$room]['status'];
        
        if (file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) === false) {
            wp_send_json_error('保存に失敗しました');
        }
        
        wp_send_json_success([
            'status' => $data[$room]['status']
        ]);
    }
}

new Koudaisai_JSON_Manager();