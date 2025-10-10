<?php
// 子テーマの functions.php

// --- 変更点 1: ファイルの保存場所を定義 ---
// 安全なuploadsディレクトリ内に専用フォルダを作る設定
define('EVENT_DATA_DIR_PATH', wp_upload_dir()['basedir'] . '/dataforapp/');
define('EVENT_DATA_FILE_PATH', EVENT_DATA_DIR_PATH . 'eventdata.json');

// 1. JSONデータを「ファイルに」保存・取得する関数
function save_events($data) {
    // ディレクトリがなければ作成する
    if (!file_exists(EVENT_DATA_DIR_PATH)) {
        wp_mkdir_p(EVENT_DATA_DIR_PATH);
    }
    // データをJSON形式に変換してファイルに書き込む
    $json_data = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    file_put_contents(EVENT_DATA_FILE_PATH, $json_data);
}

function get_events() {
    // デフォルトのデータ構造
    $default = [
        "buildingName" => "",
        "eventName" => "",
        "time" => "",
        "description" => "",
        "date" => "",
        "image" => "",
        "groupName" => "",
        "snsLink" => "",
        "reservation" => false,
        "others" => "",
        "caution" => ""
    ];

    // ファイルが存在すれば、その内容を読み込んで返す
    if (file_exists(EVENT_DATA_FILE_PATH)) {
        $json_data = file_get_contents(EVENT_DATA_FILE_PATH);
        // json_decodeの第二引数をtrueにして連想配列として返す
        return json_decode($json_data, true);
    }

    // ファイルがなければデフォルト値を返す
    return $default;
}

// 2. 編集フォームを表示するショートコード (変更なし)
function event_editor_form() {
    if (!post_password_required()) {
        $data = get_events();
        ob_start();
        ?>
        <form method="post" action="">
            <input type="hidden" name="event_editor_nonce" value="<?php echo wp_create_nonce('event_editor_save'); ?>">

            <label>建物名: <input type="text" name="buildingName" value="<?php echo esc_attr($data['buildingName'] ?? ''); ?>"></label><br>
            <label>イベント名: <input type="text" name="eventName" value="<?php echo esc_attr($data['eventName'] ?? ''); ?>"></label><br>
            <label>時間: <input type="text" name="time" value="<?php echo esc_attr($data['time'] ?? ''); ?>"></label><br>
            <label>説明: <textarea name="description"><?php echo esc_textarea($data['description'] ?? ''); ?></textarea></label><br>
            <label>日付: <input type="date" name="date" value="<?php echo esc_attr($data['date'] ?? ''); ?>"></label><br>
            <label>画像URL: <input type="url" name="image" value="<?php echo esc_url($data['image'] ?? ''); ?>"></label><br>
            <label>団体名: <input type="text" name="groupName" value="<?php echo esc_attr($data['groupName'] ?? ''); ?>"></label><br>
            <label>SNSリンク: <input type="url" name="snsLink" value="<?php echo esc_url($data['snsLink'] ?? ''); ?>"></label><br>
            
            <label>予約:
                <select name="reservation">
                    <option value="0" <?php selected($data['reservation'] ?? false, false); ?>>いいえ</option>
                    <option value="1" <?php selected($data['reservation'] ?? false, true); ?>>はい</option>
                </select>
            </label><br>

            <label>その他: <textarea name="others"><?php echo esc_textarea($data['others'] ?? ''); ?></textarea></label><br>
            <label>注意事項: <textarea name="caution"><?php echo esc_textarea($data['caution'] ?? ''); ?></textarea></label><br>

            <button type="submit" name="save_events">保存</button>
        </form>
        <?php
        return ob_get_clean();
    }
    return '';
}
add_shortcode('event_editor', 'event_editor_form');

// 3. 保存処理 (変更なし)
function handle_event_save() {
    if (isset($_POST['save_events']) && isset($_POST['event_editor_nonce'])) {
        if (!wp_verify_nonce($_POST['event_editor_nonce'], 'event_editor_save')) {
            wp_die('セキュリティチェックに失敗しました。');
        }

        $data = [
            "buildingName" => sanitize_text_field($_POST['buildingName'] ?? ''),
            "eventName" => sanitize_text_field($_POST['eventName'] ?? ''),
            "time" => sanitize_text_field($_POST['time'] ?? ''),
            "description" => sanitize_textarea_field($_POST['description'] ?? ''),
            "date" => sanitize_text_field($_POST['date'] ?? ''),
            "image" => esc_url_raw($_POST['image'] ?? ''),
            "groupName" => sanitize_text_field($_POST['groupName'] ?? ''),
            "snsLink" => esc_url_raw($_POST['snsLink'] ?? ''),
            "reservation" => !empty($_POST['reservation']) && $_POST['reservation'] === '1',
            "others" => sanitize_textarea_field($_POST['others'] ?? ''),
            "caution" => sanitize_textarea_field($_POST['caution'] ?? '')
        ];

        save_events($data);
        wp_safe_redirect($_SERVER['REQUEST_URI']);
        exit;
    }
}
add_action('init', 'handle_event_save');