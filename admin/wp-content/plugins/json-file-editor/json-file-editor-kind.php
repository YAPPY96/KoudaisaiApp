<?php
/*
Plugin Name: JSON File Editor Kind
Description: A kinder, more beautiful plugin to edit JSON files for events and announcements via shortcodes.
Version: 1.0
Author: Jules
*/

// To prevent direct access to the plugin file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Create data directory on plugin activation
function jfek_activate_plugin() {
    $data_dir = WP_CONTENT_DIR . '/dataforapp';
    if ( ! file_exists( $data_dir ) ) {
        wp_mkdir_p( $data_dir );
    }
}
register_activation_hook( __FILE__, 'jfek_activate_plugin' );

function jfek_event_editor_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '<em>[event_editor_kind] shortcode is active. Form will be displayed on the public page.</em>';
    }
    ob_start();
    jfek_event_editor_page();
    return ob_get_clean();
}
add_shortcode( 'event_editor_kind', 'jfek_event_editor_shortcode' );

function jfek_announcement_editor_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '<em>[announcement_editor_kind] shortcode is active. Form will be displayed on the public page.</em>';
    }
    ob_start();
    jfek_announcement_editor_page();
    return ob_get_clean();
}
add_shortcode( 'announcement_editor_kind', 'jfek_announcement_editor_shortcode' );

function jfek_handle_form_submissions() {
    // Handle Event Form Submission
    if ( isset( $_POST['jfek_event_nonce'] ) && wp_verify_nonce( $_POST['jfek_event_nonce'], 'jfek_event_action' ) ) {
        $file_path            = WP_CONTENT_DIR . '/dataforapp/events.json';
        $original_data        = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
        if ( ! is_array( $original_data ) ) $original_data = [];
        $submitted_events = isset( $_POST['events'] ) ? (array) $_POST['events'] : [];

        foreach ( $original_data as $index => &$event ) {
            if ( isset( $submitted_events[ $index ] ) ) {
                $submitted_event = $submitted_events[ $index ];
                if ( isset( $event['status'] ) && isset($submitted_event['status']) ) {
                    $event['status'] = ($submitted_event['status'] === 'true' );
                }
                if ( ! empty( $event['reservationSlots'] ) && isset( $submitted_event['reservationSlots'] ) ) {
                    foreach ( $event['reservationSlots'] as $slot_index => &$slot ) {
                        if ( isset( $submitted_event['reservationSlots'][ $slot_index ]['status'] ) ) {
                            $slot['status'] = sanitize_text_field( $submitted_event['reservationSlots'][ $slot_index ]['status'] );
                        }
                    }
                }
            }
        }
        
        $data_dir = WP_CONTENT_DIR . '/dataforapp';
        if ( ! file_exists( $data_dir ) ) wp_mkdir_p( $data_dir );
        file_put_contents( $file_path, json_encode( $original_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        
        wp_redirect( add_query_arg( 'status', 'events_saved', wp_get_referer() ) );
        exit;
    }

    // Handle Announcement Form Submission
    if ( isset( $_POST['jfek_announcement_nonce'] ) && wp_verify_nonce( $_POST['jfek_announcement_nonce'], 'jfek_announcement_action' ) ) {
        $file_path = WP_CONTENT_DIR . '/dataforapp/announcements.json';
        $data      = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
        if ( ! is_array( $data ) ) $data = [];

        if ( isset( $_POST['add_announcement'] ) ) {
            $new_id = 1;
            if ( ! empty( $data ) ) {
                $ids = wp_list_pluck( $data, 'id' );
                if ( ! empty( $ids ) ) {
                    $new_id = max( $ids ) + 1;
                }
            }
            $data[] = [
                'id'      => (string) $new_id,
                'message' => sanitize_textarea_field( $_POST['new_message'] ),
                'enabled' => $_POST['new_enabled'] === 'true',
            ];
        }

        if ( isset( $_POST['update_announcements'] ) && isset( $_POST['announcements'] ) ) {
            $updated_announcements = [];
            foreach ( (array) $_POST['announcements'] as $id => $details ) {
                if ( isset( $details['delete'] ) && $details['delete'] == '1' ) continue;
                $updated_announcements[] = [
                    'id'      => sanitize_text_field( $id ),
                    'message' => sanitize_textarea_field( $details['message'] ),
                    'enabled' => $details['enabled'] === 'true',
                ];
            }
            $data = $updated_announcements;
        }

        $data_dir = WP_CONTENT_DIR . '/dataforapp';
        if ( ! file_exists( $data_dir ) ) wp_mkdir_p( $data_dir );
        file_put_contents( $file_path, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        
        wp_redirect( add_query_arg( 'status', 'announcements_saved', wp_get_referer() ) );
        exit;
    }
}
add_action( 'template_redirect', 'jfek_handle_form_submissions' );

function jfek_event_editor_page() {
    if ( isset( $_GET['status'] ) && $_GET['status'] == 'events_saved' ) {
        echo '<div class="notice notice-success" style="margin: 15px 0; padding: 12px; border-left: 4px solid #46b450;"><p><strong>イベントの変更を保存しました。</strong></p></div>';
    }
    $file_path            = WP_CONTENT_DIR . '/dataforapp/events.json';
    $reservation_statuses = [ 'available' => '空きあり', 'few_left' => '残りわずか', 'full' => '満席' ];
    $events               = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
    if ( ! is_array( $events ) ) {
        $events = [];
    }
    ?>
    <div class="wrap" style="max-width: 900px;">
        <h1>イベント編集（ステータスと予約枠のみ）</h1>
        <p>各イベントの「営業状況」と「予約枠の状態」のみ編集できます。他の情報は参照用です。</p>

        <form method="post" action="" style="margin-top: 20px;">
            <?php wp_nonce_field( 'jfek_event_action', 'jfek_event_nonce' ); ?>

            <?php foreach ( $events as $index => $event ) : ?>
                <div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                    <h2 style="margin-top: 0; color: #222;"><?php echo esc_html( $event['eventName'] ?? 'イベント' ); ?></h2>
                    
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; margin-bottom: 20px; font-size: 14px; color: #555;">
                        <div><strong>日付:</strong> <?php echo esc_html( $event['date'] ?? '—' ); ?></div>
                        <div><strong>時間:</strong> <?php echo esc_html( $event['time'] ?? '—' ); ?></div>
                        <div><strong>会場:</strong> <?php echo esc_html( $event['buildingName'] ?? '—' ); ?></div>
                        <div><strong>グループ:</strong> <?php echo esc_html( $event['groupName'] ?? '—' ); ?></div>
                    </div>

                    <?php if ( isset( $event['status'] ) ) : ?>
                        <div style="margin-bottom: 20px; padding: 12px; background: #fff; border-radius: 6px;">
                            <label style="display: block; margin-bottom: 8px; font-weight: 600;">営業状況</label>
                            <select name="events[<?php echo $index; ?>][status]" style="width: 100%; padding: 8px; font-size: 15px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="true" <?php selected( ($event['status'] ?? false), true ); ?>>営業中（表示）</option>
                                <option value="false" <?php selected( ($event['status'] ?? false), false ); ?>>営業終了（非表示）</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <?php if ( ! empty( $event['reservationSlots'] ) ) : ?>
                        <div style="margin-bottom: 0; padding: 12px; background: #fff; border-radius: 6px;">
                            <label style="display: block; margin-bottom: 12px; font-weight: 600;">予約枠の状態</label>
                            <?php foreach ( $event['reservationSlots'] as $slot_index => $slot ) : ?>
                                <div style="display: flex; align-items: center; margin-bottom: 10px;">
                                    <span style="min-width: 100px; font-weight: 500;"><?php echo esc_html( $slot['time'] ?? '枠' ); ?>:</span>
                                    <select name="events[<?php echo $index; ?>][reservationSlots][<?php echo $slot_index; ?>][status]" style="flex: 1; padding: 6px; font-size: 14px; border: 1px solid #ccc; border-radius: 4px;">
                                        <?php foreach ( $reservation_statuses as $val => $text ) : ?>
                                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $slot['status'] ?? '', $val ); ?>><?php echo esc_html( $text ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

            <p class="submit">
                <input type="submit" name="submit" class="button button-primary" value="変更を保存" style="padding: 10px 24px; font-size: 16px;">
            </p>
        </form>
    </div>
    <?php
}

function jfek_announcement_editor_page() {
    if ( isset( $_GET['status'] ) && $_GET['status'] == 'announcements_saved' ) {
        echo '<div class="notice notice-success" style="margin: 15px 0; padding: 12px; border-left: 4px solid #46b450;"><p><strong>お知らせの変更を保存しました。</strong></p></div>';
    }
    $file_path     = WP_CONTENT_DIR . '/dataforapp/announcements.json';
    $announcements = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
    if ( ! is_array( $announcements ) ) {
        $announcements = [];
    }
    ?>
    <div class="wrap" style="max-width: 800px;">
        <h1>お知らせ管理</h1>

        <div style="background: #f0f8ff; border: 1px solid #b3d9ff; border-radius: 8px; padding: 20px; margin-bottom: 30px;">
            <h2 style="margin-top: 0; color: #0073aa;">＋ 新しいお知らせを追加</h2>
            <form method="post" action="">
                <?php wp_nonce_field( 'jfek_announcement_action', 'jfek_announcement_nonce' ); ?>
                <div style="margin-bottom: 15px;">
                    <label for="new_message" style="display: block; margin-bottom: 6px; font-weight: 600;">お知らせ内容</label>
                    <textarea id="new_message" name="new_message" rows="3" style="width: 100%; padding: 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 4px;" placeholder="例：本日は臨時休業いたします。"></textarea>
                </div>
                <div style="margin-bottom: 20px;">
                    <label for="new_enabled" style="display: block; margin-bottom: 6px; font-weight: 600;">表示設定</label>
                    <select id="new_enabled" name="new_enabled" style="width: 100%; padding: 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 4px;">
                        <option value="true">✅ 表示する</option>
                        <option value="false">❌ 非表示にする</option>
                    </select>
                </div>
                <input type="submit" name="add_announcement" class="button button-primary" value="このお知らせを追加" style="padding: 10px 20px; font-size: 15px;">
            </form>
        </div>

        <hr style="margin: 30px 0; border: 0; border-top: 1px solid #eee;">

        <h2>既存のお知らせ</h2>
        <?php if ( empty( $announcements ) ) : ?>
            <p style="color: #777; font-style: italic;">現在、登録されているお知らせはありません。</p>
        <?php else : ?>
            <form method="post" action="" style="margin-top: 20px;">
                <?php wp_nonce_field( 'jfek_announcement_action', 'jfek_announcement_nonce' ); ?>

                <?php foreach ( $announcements as $announcement ) : 
                    $id = esc_attr( $announcement['id'] ?? '' );
                ?>
                    <div style="background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                            <strong style="font-size: 16px; color: #222;">ID: <?php echo esc_html( $announcement['id'] ?? '—' ); ?></strong>
                            <label style="display: flex; align-items: center; gap: 6px; color: #d63638; cursor: pointer; font-weight: normal;">
                                <input type="checkbox" name="announcements[<?php echo $id; ?>][delete]" value="1" style="transform: scale(1.2);">
                                🗑️ 削除
                            </label>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <label for="message_<?php echo $id; ?>" style="display: block; margin-bottom: 6px; font-weight: 600;">お知らせ内容</label>
                            <textarea id="message_<?php echo $id; ?>" name="announcements[<?php echo $id; ?>][message]" rows="3" style="width: 100%; padding: 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 4px;"><?php echo esc_textarea( $announcement['message'] ?? '' ); ?></textarea>
                        </div>
                        <div>
                            <label for="enabled_<?php echo $id; ?>" style="display: block; margin-bottom: 6px; font-weight: 600;">表示設定</label>
                            <select id="enabled_<?php echo $id; ?>" name="announcements[<?php echo $id; ?>][enabled]" style="width: 100%; padding: 10px; font-size: 15px; border: 1px solid #ccc; border-radius: 4px;">
                                <option value="true" <?php selected( $announcement['enabled'] ?? false, true ); ?>>✅ 表示する</option>
                                <option value="false" <?php selected( $announcement['enabled'] ?? false, false ); ?>>❌ 非表示にする</option>
                            </select>
                        </div>
                    </div>
                <?php endforeach; ?>

                <p class="submit">
                    <input type="submit" name="update_announcements" class="button button-primary" value="すべての変更を保存" style="padding: 10px 24px; font-size: 16px;">
                </p>
            </form>
        <?php endif; ?>
    </div>
    <?php
}