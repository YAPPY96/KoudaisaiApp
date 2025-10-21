<?php
// 子テーマの functions.php

// 1. JSONデータを保存・取得する関数
function save_events($data) {
    // デフォルト構造とマージして保存（フィールド漏れ防止）
    $default = get_events_default();
    $data = array_merge($default, $data);
    update_option('eventdata_json', $data, false); // autoload = false（軽量化）
}

function get_events_default() {
    return [
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
}

function get_events() {
    $saved = get_option('eventdata_json');
    if (false === $saved) {
        return get_events_default();
    }
    // 保存済みデータとデフォルトをマージ（新フィールド追加時対応）
    return array_merge(get_events_default(), (array) $saved);
}

// 2. 編集フォームショートコード（管理者のみ表示）
function event_editor_form() {
    // 管理者以外は表示しない（より安全）
    if (!current_user_can('manage_options')) {
        return ''; 
    }

    $data = get_events();
    ob_start();
    ?>
    <form method="post" action="">
        <input type="hidden" name="event_editor_nonce" value="<?php echo esc_attr(wp_create_nonce('event_editor_save')); ?>">

        <label>建物名: <input type="text" name="buildingName" value="<?php echo esc_attr($data['buildingName']); ?>"></label><br>
        <label>イベント名: <input type="text" name="eventName" value="<?php echo esc_attr($data['eventName']); ?>"></label><br>
        <label>時間: <input type="text" name="time" value="<?php echo esc_attr($data['time']); ?>"></label><br>
        <label>説明: <textarea name="description"><?php echo esc_textarea($data['description']); ?></textarea></label><br>
        <label>日付: <input type="date" name="date" value="<?php echo esc_attr($data['date']); ?>"></label><br>
        <label>画像URL: <input type="url" name="image" value="<?php echo esc_url($data['image']); ?>"></label><br>
        <label>団体名: <input type="text" name="groupName" value="<?php echo esc_attr($data['groupName']); ?>"></label><br>
        <label>SNSリンク: <input type="url" name="snsLink" value="<?php echo esc_url($data['snsLink']); ?>"></label><br>
        
        <label>予約:
            <select name="reservation">
                <option value="0" <?php selected($data['reservation'], false); ?>>いいえ</option>
                <option value="1" <?php selected($data['reservation'], true); ?>>はい</option>
            </select>
        </label><br>

        <label>その他: <textarea name="others"><?php echo esc_textarea($data['others']); ?></textarea></label><br>
        <label>注意事項: <textarea name="caution"><?php echo esc_textarea($data['caution']); ?></textarea></label><br>

        <button type="submit" name="save_events">保存</button>
    </form>
    <?php
    return ob_get_clean();
}
add_shortcode('event_editor', 'event_editor_form');

// 3. 保存処理（管理者のみ許可）
function handle_event_save() {
    if (!is_admin() && !current_user_can('manage_options')) {
        return;
    }

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
            "reservation" => (!empty($_POST['reservation']) && $_POST['reservation'] === '1'),
            "others" => sanitize_textarea_field($_POST['others'] ?? ''),
            "caution" => sanitize_textarea_field($_POST['caution'] ?? '')
        ];

        save_events($data);
        wp_safe_redirect(remove_query_arg('save_events'));
        exit;
    }
}
add_action('admin_init', 'handle_event_save'); // 管理画面でのみ処理（フロントエンドPOSTを遮断）

// 4. JSONエンドポイント（REST API方式がベストだが、簡易版）
function serve_event_json() {
    // リクエストURIを正しく解析
    $request_uri = $_SERVER['REQUEST_URI'];
    // クエリ文字列を除去
    $path = parse_url($request_uri, PHP_URL_PATH);
    
    if ($path === '/dataforapp/eventdata.json') {
        $data = get_events();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
add_action('init', 'serve_event_json');