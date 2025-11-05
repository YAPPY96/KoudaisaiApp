<?php
/*
Plugin Name: JSON File Editor (Final Corrected Version)
Description: A plugin to edit JSON files for events and announcements via shortcodes.
Version: 1.3
Author: Jules
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function jfe_activate_plugin() {
    $data_dir = WP_CONTENT_DIR . '/dataforapp';
    wp_mkdir_p( $data_dir . '/image' );
}
register_activation_hook( __FILE__, 'jfe_activate_plugin' );

// ======================
// SHORTCODES
// ======================

function jfe_event_editor_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '<em>[event_editor] shortcode is active.</em>';
    }
    ob_start();
    jfe_event_editor_page();
    return ob_get_clean();
}
add_shortcode( 'event_editor', 'jfe_event_editor_shortcode' );

function jfe_webp_image_manager_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '<em>[webp_image_manager] shortcode is active.</em>';
    }
    ob_start();
    jfe_webp_image_manager_page();
    return ob_get_clean();
}
add_shortcode( 'webp_image_manager', 'jfe_webp_image_manager_shortcode' );

// ======================
// FORM SUBMISSION HANDLERS
// ======================

add_action( 'template_redirect', 'jfe_handle_form_submissions' );
function jfe_handle_form_submissions() {
    // Event Save
    if ( isset( $_POST['jfe_event_nonce'] ) && wp_verify_nonce( $_POST['jfe_event_nonce'], 'jfe_event_action' ) ) {
        $selected_date = sanitize_text_field( $_POST['selected_event_date'] ?? '' );
        if ( ! preg_match( '/^\d{4}-\d{2}-1[56]$/', $selected_date ) ) {
            wp_die( 'Invalid date format.' );
        }
        $file_path = WP_CONTENT_DIR . '/dataforapp/events_' . $selected_date . '.json';

        $reservation_statuses = [ 'available' => 'Available', 'few_left' => 'Few Left', 'full' => 'Full' ];
        $original_data = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
        if ( ! is_array( $original_data ) ) $original_data = [];

        $submitted_events = isset( $_POST['events'] ) ? (array) $_POST['events'] : [];
        foreach ( $original_data as $index => &$event ) {
            if ( isset( $submitted_events[ $index ] ) ) {
                $submitted_event = $submitted_events[ $index ];
                $text_fields = [ 'buildingName', 'eventName', 'time', 'date', 'image_L', 'image_B', 'groupName', 'snsLink' ];
                foreach ( $text_fields as $field ) {
                    if ( isset( $submitted_event[ $field ] ) ) {
                        $event[ $field ] = sanitize_text_field( $submitted_event[ $field ] );
                    }
                }
                $textarea_fields = [ 'description', 'caution', 'others' ];
                foreach ( $textarea_fields as $field ) {
                    if ( isset( $submitted_event[ $field ] ) ) {
                        $event[ $field ] = sanitize_textarea_field( $submitted_event[ $field ] );
                    }
                }
                if ( isset( $event['status'] ) ) {
                    $event['status'] = ( isset( $submitted_event['status'] ) && $submitted_event['status'] === 'true' );
                }
                if ( ! empty( $event['reservationSlots'] ) && isset( $submitted_event['reservationSlots'] ) ) {
                    foreach ( $event['reservationSlots'] as $slot_index => &$slot ) {
                        if ( isset( $submitted_event['reservationSlots'][ $slot_index ]['status'] ) ) {
                            $new_status = sanitize_text_field( $submitted_event['reservationSlots'][ $slot_index ]['status'] );
                            if ( array_key_exists( $new_status, $reservation_statuses ) ) {
                                $slot['status'] = $new_status;
                            }
                        }
                    }
                }
            }
        }

        wp_mkdir_p( dirname( $file_path ) );
        file_put_contents( $file_path, json_encode( $original_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        wp_redirect( add_query_arg( [ 'status' => 'events_saved', 'date' => $selected_date ], wp_get_referer() ) );
        exit;
    }

    // WebP Upload
    if ( isset( $_POST['jfe_webp_nonce'] ) && wp_verify_nonce( $_POST['jfe_webp_nonce'], 'jfe_webp_action' ) ) {
        if ( isset( $_FILES['webp_file'] ) && ! empty( $_FILES['webp_file']['name'] ) ) {
            $upload_dir = WP_CONTENT_DIR . '/dataforapp/image';
            wp_mkdir_p( $upload_dir );

            $file = $_FILES['webp_file'];
            $filename = sanitize_file_name( $file['name'] );
            if ( strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) ) !== 'webp' ) {
                wp_redirect( add_query_arg( 'status', 'webp_invalid_type', wp_get_referer() ) );
                exit;
            }

            $upload_path = $upload_dir . '/' . $filename;
            if ( file_exists( $upload_path ) ) {
                wp_redirect( add_query_arg( 'status', 'webp_exists', wp_get_referer() ) );
                exit;
            }

            if ( move_uploaded_file( $file['tmp_name'], $upload_path ) ) {
                chmod( $upload_path, 0644 );
                wp_redirect( add_query_arg( 'status', 'webp_uploaded', wp_get_referer() ) );
            } else {
                wp_redirect( add_query_arg( 'status', 'webp_upload_error', wp_get_referer() ) );
            }
            exit;
        }
    }
}

// ======================
// HELPER FUNCTIONS
// ======================

if ( ! function_exists( 'jfe_create_editor_row' ) ) {
    function jfe_create_editor_row( $label, $name, $value, $type = 'text', $options = [] ) {
        ?>
        <tr valign="top">
            <th scope="row"><label for="<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td>
                <?php if ( $type === 'textarea' ) : ?>
                    <textarea id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="5" class="large-text"><?php echo esc_textarea( $value ); ?></textarea>
                <?php elseif ( $type === 'select' ) : ?>
                    <select id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>">
                        <?php foreach ( $options['choices'] as $val => $text ) : ?>
                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $value, $val ); ?>><?php echo esc_html( $text ); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else : ?>
                    <input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="regular-text">
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }
}

function jfe_get_available_event_dates() {
    $data_dir = WP_CONTENT_DIR . '/dataforapp';
    $dates = [];
    if ( is_dir( $data_dir ) ) {
        $files = scandir( $data_dir );
        foreach ( $files as $file ) {
            if ( preg_match( '/^events_(\d{4}-\d{2}-1[56])\.json$/', $file, $matches ) ) {
                $dates[] = $matches[1];
            }
        }
        rsort( $dates ); // 最新順
    }
    return $dates;
}

// ======================
// PAGES
// ======================

// ======================
// 新規：全イベントから日付（15/16のみ）を抽出
// ======================
function jfe_get_event_dates_from_all_files() {
    $data_dir = WP_CONTENT_DIR . '/dataforapp';
    $dates = [];

    if ( ! is_dir( $data_dir ) ) return $dates;

    $files = glob( $data_dir . '/events_*.json' );
    foreach ( $files as $file ) {
        $data = json_decode( file_get_contents( $file ), true );
        if ( ! is_array( $data ) ) continue;

        foreach ( $data as $event ) {
            if ( isset( $event['date'] ) && is_string( $event['date'] ) ) {
                // 日付が "YYYY-MM-15" or "YYYY-MM-16" 形式かチェック
                if ( preg_match( '/^\d{4}-\d{2}-1[56]$/', $event['date'] ) ) {
                    $dates[] = $event['date'];
                }
            }
        }
    }

    // 重複削除 & 降順ソート（最新が上）
    $dates = array_unique( $dates );
    rsort( $dates );
    return $dates;
}

// ======================
// 新規：指定日付のイベントを全ファイルから収集
// ======================
function jfe_get_events_by_date( $target_date ) {
    $data_dir = WP_CONTENT_DIR . '/dataforapp';
    $events = [];

    if ( ! is_dir( $data_dir ) ) return $events;

    $files = glob( $data_dir . '/events_*.json' );
    foreach ( $files as $file ) {
        $data = json_decode( file_get_contents( $file ), true );
        if ( ! is_array( $data ) ) continue;

        foreach ( $data as $event ) {
            if ( isset( $event['date'] ) && $event['date'] === $target_date ) {
                // ファイルパスをメタ情報として保持（保存時に必要）
                $event['_source_file'] = basename( $file );
                $events[] = $event;
            }
        }
    }

    return $events;
}

// ======================
// 新規：保存時に各イベントを元のファイルに戻す
// ======================
function jfe_save_events_by_date( $target_date, $submitted_events ) {
    $data_dir = WP_CONTENT_DIR . '/dataforapp';
    $files_to_update = [];

    // 全ファイルから該当日付のイベントを一旦削除
    $files = glob( $data_dir . '/events_*.json' );
    foreach ( $files as $file ) {
        $data = json_decode( file_get_contents( $file ), true );
        if ( ! is_array( $data ) ) $data = [];

        // 該当日付のイベントを除外
        $filtered = array_filter( $data, function( $event ) use ( $target_date ) {
            return ( $event['date'] ?? '' ) !== $target_date;
        });

        $files_to_update[ basename( $file ) ] = array_values( $filtered );
    }

    // 提出されたイベントを対応するファイルに追加
    foreach ( $submitted_events as $event ) {
        if ( ( $event['date'] ?? '' ) !== $target_date ) continue;

        $source_file = $event['_source_file'] ?? null;
        unset( $event['_source_file'] ); // 保存時はメタ削除

        if ( $source_file && isset( $files_to_update[ $source_file ] ) ) {
            $files_to_update[ $source_file ][] = $event;
        } else {
            // 新規ファイル？ → 適当なファイル名で作成（例: events_2025-11-15.json）
            $new_file = 'events_' . $target_date . '.json';
            if ( ! isset( $files_to_update[ $new_file ] ) ) {
                $files_to_update[ $new_file ] = [];
            }
            $files_to_update[ $new_file ][] = $event;
        }
    }

    // 各ファイルを保存
    foreach ( $files_to_update as $filename => $data ) {
        $filepath = $data_dir . '/' . $filename;
        file_put_contents( $filepath, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
    }
}

// ======================
// FORM SUBMISSION: イベント保存処理を新方式に変更
// ======================
// （既存のイベント保存処理を以下で置き換え）
if ( isset( $_POST['jfe_event_nonce'] ) && wp_verify_nonce( $_POST['jfe_event_nonce'], 'jfe_event_action' ) ) {
    $selected_date = sanitize_text_field( $_POST['selected_event_date'] ?? '' );
    if ( ! preg_match( '/^\d{4}-\d{2}-1[56]$/', $selected_date ) ) {
        wp_die( 'Invalid date format.' );
    }

    $submitted_events = [];
    if ( isset( $_POST['events'] ) && is_array( $_POST['events'] ) ) {
        foreach ( $_POST['events'] as $index => $event_data ) {
            $event = [];

            // テキストフィールド
            $text_fields = [ 'buildingName', 'eventName', 'time', 'date', 'image_L', 'image_B', 'groupName', 'snsLink', '_source_file' ];
            foreach ( $text_fields as $field ) {
                if ( isset( $event_data[ $field ] ) ) {
                    $event[ $field ] = sanitize_text_field( $event_data[ $field ] );
                }
            }

            // テキストエリア
            $textarea_fields = [ 'description', 'caution', 'others' ];
            foreach ( $textarea_fields as $field ) {
                if ( isset( $event_data[ $field ] ) ) {
                    $event[ $field ] = sanitize_textarea_field( $event_data[ $field ] );
                }
            }

            // ステータス（boolean）
            if ( isset( $event_data['status'] ) ) {
                $event['status'] = ( $event_data['status'] === 'true' );
            }

            // 予約スロット
            if ( ! empty( $event_data['reservationSlots'] ) && is_array( $event_data['reservationSlots'] ) ) {
                $reservation_statuses = [ 'available', 'few_left', 'full' ];
                $event['reservationSlots'] = [];
                foreach ( $event_data['reservationSlots'] as $slot ) {
                    if ( isset( $slot['time'], $slot['status'] ) && in_array( $slot['status'], $reservation_statuses, true ) ) {
                        $event['reservationSlots'][] = [
                            'time' => sanitize_text_field( $slot['time'] ),
                            'status' => $slot['status']
                        ];
                    }
                }
            }

            $submitted_events[] = $event;
        }
    }

    jfe_save_events_by_date( $selected_date, $submitted_events );
    wp_redirect( add_query_arg( [ 'status' => 'events_saved', 'date' => $selected_date ], wp_get_referer() ) );
    exit;
}

// ======================
// EVENT EDITOR PAGE: ドロップダウンを"date"値で表示
// ======================
function jfe_event_editor_page() {
    $available_dates = jfe_get_event_dates_from_all_files();
    $selected_date = '';

    // GETまたはPOSTから選択日付を取得
    if ( ! empty( $_GET['date'] ) && preg_match( '/^\d{4}-\d{2}-1[56]$/', $_GET['date'] ) ) {
        $selected_date = sanitize_text_field( $_GET['date'] );
    } elseif ( ! empty( $_POST['selected_event_date'] ) && preg_match( '/^\d{4}-\d{2}-1[56]$/', $_POST['selected_event_date'] ) ) {
        $selected_date = sanitize_text_field( $_POST['selected_event_date'] );
    } elseif ( ! empty( $available_dates ) ) {
        $selected_date = $available_dates[0];
    }

    if ( isset( $_GET['status'] ) && $_GET['status'] == 'events_saved' ) {
        echo '<div class="notice notice-success is-dismissible" style="margin: 0 0 15px;"><p>Events saved successfully.</p></div>';
    }

    $events = $selected_date ? jfe_get_events_by_date( $selected_date ) : [];

    $reservation_statuses = [ 'available' => 'Available', 'few_left' => 'Few Left', 'full' => 'Full' ];
    ?>
    <div class="wrap jfe-flex-container">
        <style>
            .jfe-flex-container {
                display: flex;
                gap: 30px;
                flex-wrap: wrap;
            }
            .jfe-panel {
                flex: 1;
                min-width: 300px;
            }
            @media (max-width: 768px) {
                .jfe-flex-container {
                    flex-direction: column;
                }
            }
        </style>

        <!-- LEFT: Event Editor -->
        <div class="jfe-panel">
            <h1>Event Editor (by Date)</h1>
            <form method="post" action="">
                <?php wp_nonce_field( 'jfe_event_action', 'jfe_event_nonce' ); ?>

                <p>
                    <label for="event_date_selector">Select Event Date (15th or 16th):</label><br>
                    <select id="event_date_selector" name="selected_event_date" onchange="this.form.submit()">
                        <option value="">-- Select Date --</option>
                        <?php foreach ( $available_dates as $date ) : ?>
                            <option value="<?php echo esc_attr( $date ); ?>" <?php selected( $date, $selected_date ); ?>>
                                <?php echo esc_html( $date ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>

                <?php if ( $selected_date && ! empty( $events ) ) : ?>
                    <?php foreach ( $events as $index => $event ) : ?>
                        <div style="border: 1px solid #ccc; padding: 15px; margin: 15px 0; background: #fff;">
                            <h3><?php echo esc_html( $event['eventName'] ?? 'Event ' . ( $index + 1 ) ); ?></h3>
                            <table class="form-table">
                                <?php
                                // 隠しフィールド：元のファイル名を保持
                                echo '<input type="hidden" name="events[' . $index . '][_source_file]" value="' . esc_attr( $event['_source_file'] ?? '' ) . '">';

                                jfe_create_editor_row( 'Event Name', "events[{$index}][eventName]", $event['eventName'] ?? '' );
                                jfe_create_editor_row( 'Building Name', "events[{$index}][buildingName]", $event['buildingName'] ?? '' );
                                jfe_create_editor_row( 'Time', "events[{$index}][time]", $event['time'] ?? '' );
                                jfe_create_editor_row( 'Date', "events[{$index}][date]", $event['date'] ?? '', 'text' ); // 編集不可にしたい場合は readonly 追加

                                jfe_create_editor_row( 'Description', "events[{$index}][description]", $event['description'] ?? '', 'textarea' );
                                
                                if ( isset( $event['status'] ) ) {
                                    jfe_create_editor_row( 'Status', "events[{$index}][status]", ( $event['status'] ?? false ) ? 'true' : 'false', 'select', [ 
                                        'choices' => [ 'true' => '営業中 (Open)', 'false' => '営業終了 (Closed)' ] 
                                    ] );
                                }
                                
                                jfe_create_editor_row( 'Group Name', "events[{$index}][groupName]", $event['groupName'] ?? '' );
                                jfe_create_editor_row( 'Caution', "events[{$index}][caution]", $event['caution'] ?? '', 'textarea' );
                                jfe_create_editor_row( 'Others', "events[{$index}][others]", $event['others'] ?? '', 'textarea' );
                                jfe_create_editor_row( 'Image L', "events[{$index}][image_L]", $event['image_L'] ?? '' );
                                jfe_create_editor_row( 'Image B', "events[{$index}][image_B]", $event['image_B'] ?? '' );
                                jfe_create_editor_row( 'SNS Link', "events[{$index}][snsLink]", $event['snsLink'] ?? '' );

                                if ( ! empty( $event['reservationSlots'] ) ) {
                                    ?>
                                    <tr valign="top">
                                        <th scope="row">Reservation Slots</th>
                                        <td>
                                            <?php foreach ( $event['reservationSlots'] as $slot_index => $slot ) : ?>
                                                <div style="margin-bottom: 5px;">
                                                    <strong><?php echo esc_html( $slot['time'] ?? '' ); ?>: </strong>
                                                    <select name="events[<?php echo $index; ?>][reservationSlots][<?php echo $slot_index; ?>][status]">
                                                        <?php foreach ( $reservation_statuses as $val => $text ) : ?>
                                                            <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $slot['status'] ?? '', $val ); ?>><?php echo esc_html( $text ); ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </table>
                        </div>
                    <?php endforeach; ?>
                    <p class="submit"><input type="submit" name="submit" class="button button-primary" value="Save All Changes"></p>
                <?php elseif ( $selected_date ) : ?>
                    <p>No events found for <code><?php echo esc_html( $selected_date ); ?></code>.</p>
                <?php else : ?>
                    <p>Please select a date (15th or 16th) from the dropdown above.</p>
                <?php endif; ?>
            </form>
        </div>

        <!-- RIGHT: WebP Manager -->
        <div class="jfe-panel">
            <h1>WebP Image Manager</h1>
            <?php jfe_webp_image_manager_content(); ?>
        </div>
    </div>
    <?php
}
function jfe_webp_image_manager_page() {
    // 単独ページ用（横並び不要）
    echo '<div class="wrap">';
    echo '<h1>WebP Image Manager</h1>';
    jfe_webp_image_manager_content();
    echo '</div>';
}

function jfe_webp_image_manager_content() {
    $upload_dir = WP_CONTENT_DIR . '/dataforapp/image';
    $webp_files = [];

    if ( file_exists( $upload_dir ) ) {
        $files = scandir( $upload_dir );
        foreach ( $files as $file ) {
            if ( pathinfo( $file, PATHINFO_EXTENSION ) === 'webp' ) {
                $webp_files[] = $file;
            }
        }
    }

    if ( isset( $_GET['status'] ) ) {
        $messages = [
            'webp_uploaded'       => '✅ WebP image uploaded successfully.',
            'webp_exists'         => '⚠️ A file with that name already exists.',
            'webp_invalid_type'   => '❌ Only .webp files are allowed.',
            'webp_upload_error'   => '❌ Upload failed. Please try again.'
        ];
        $status = sanitize_key( $_GET['status'] );
        if ( isset( $messages[ $status ] ) ) {
            echo '<div class="notice notice-' . ( in_array( $status, [ 'webp_invalid_type', 'webp_upload_error' ] ) ? 'error' : 'success' ) . ' is-dismissible"><p>' . esc_html( $messages[ $status ] ) . '</p></div>';
        }
    }

    // Existing WebP list
    echo '<h2>Existing WebP Images</h2>';
    if ( ! empty( $webp_files ) ) {
        echo '<div style="display: flex; flex-wrap: wrap; gap: 10px; margin: 15px 0; padding: 10px; background: #f9f9f9; border: 1px solid #ddd;">';
        foreach ( $webp_files as $file ) {
            echo '<div style="padding: 8px; background: #fff; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">' . esc_html( $file ) . '</div>';
        }
        echo '</div>';
    } else {
        echo '<p>No WebP images found.</p>';
    }

    // Upload form
    echo '<h2>Upload New WebP Image</h2>';
    echo '<form method="post" enctype="multipart/form-data" action="">';
    wp_nonce_field( 'jfe_webp_action', 'jfe_webp_nonce' );
    echo '<table class="form-table"><tr valign="top">
            <th scope="row"><label for="webp_file">Choose WebP File</label></th>
            <td>
                <input type="file" id="webp_file" name="webp_file" accept=".webp" required>
                <p class="description">Only <code>.webp</code> files are allowed.</p>
            </td>
        </tr></table>';
    echo '<p class="submit"><input type="submit" name="upload_webp" class="button button-primary" value="Upload WebP Image"></p>';
    echo '</form>';
}