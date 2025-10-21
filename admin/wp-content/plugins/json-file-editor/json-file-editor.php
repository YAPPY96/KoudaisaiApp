<?php
/*
Plugin Name: JSON File Editor (Final Corrected Version)
Description: A plugin to edit JSON files for events and announcements via shortcodes.
Version: 1.2
Author: Jules
*/

// To prevent direct access to the plugin file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Create data directory on plugin activation
function jfe_activate_plugin() {
    $data_dir = WP_CONTENT_DIR . '/dataforapp';
    if ( ! file_exists( $data_dir ) ) {
        wp_mkdir_p( $data_dir );
    }
}
register_activation_hook( __FILE__, 'jfe_activate_plugin' );


function jfe_event_editor_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '<em>[event_editor] shortcode is active. Form will be displayed on the public page.</em>';
    }
    ob_start();
    jfe_event_editor_page();
    return ob_get_clean();
}
add_shortcode( 'event_editor', 'jfe_event_editor_shortcode' );

function jfe_announcement_editor_shortcode() {
    if ( is_admin() && ! wp_doing_ajax() ) {
        return '<em>[announcement_editor] shortcode is active. Form will be displayed on the public page.</em>';
    }
    ob_start();
    jfe_announcement_editor_page();
    return ob_get_clean();
}
add_shortcode( 'announcement_editor', 'jfe_announcement_editor_shortcode' );

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

function jfe_handle_form_submissions() {
    // This hook runs on the front-end. No need for is_admin() checks here.
    
    // Handle Event Form Submission
    if ( isset( $_POST['jfe_event_nonce'] ) && wp_verify_nonce( $_POST['jfe_event_nonce'], 'jfe_event_action' ) ) {
        $file_path            = WP_CONTENT_DIR . '/dataforapp/events.json';
        $reservation_statuses = [ 'available' => 'Available', 'few_left' => 'Few Left', 'full' => 'Full' ];
        $original_data        = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
        if ( ! is_array( $original_data ) ) {
            $original_data = [];
        }
        $submitted_events = isset( $_POST['events'] ) ? (array) $_POST['events'] : [];

        foreach ( $original_data as $index => &$event ) {
            if ( isset( $submitted_events[ $index ] ) ) {
                $submitted_event = $submitted_events[ $index ];
                $text_fields     = [ 'buildingName', 'eventName', 'time', 'date', 'image_L', 'image_B', 'groupName', 'snsLink' ];
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
        
        $data_dir = WP_CONTENT_DIR . '/dataforapp';
        if ( ! file_exists( $data_dir ) ) {
            wp_mkdir_p( $data_dir );
        }
        file_put_contents( $file_path, json_encode( $original_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        
        wp_redirect( add_query_arg( 'status', 'events_saved', wp_get_referer() ) );
        exit;
    }

    // Handle Announcement Form Submission
    if ( isset( $_POST['jfe_announcement_nonce'] ) && wp_verify_nonce( $_POST['jfe_announcement_nonce'], 'jfe_announcement_action' ) ) {
        $file_path = WP_CONTENT_DIR . '/dataforapp/announcements.json';
        $data      = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
        if ( ! is_array( $data ) ) {
            $data = [];
        }

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
                if ( isset( $details['delete'] ) && $details['delete'] == '1' ) {
                    continue;
                }
                $updated_announcements[] = [
                    'id'      => sanitize_text_field( $id ),
                    'message' => sanitize_textarea_field( $details['message'] ),
                    'enabled' => $details['enabled'] === 'true',
                ];
            }
            $data = $updated_announcements;
        }

        $data_dir = WP_CONTENT_DIR . '/dataforapp';
        if ( ! file_exists( $data_dir ) ) {
            wp_mkdir_p( $data_dir );
        }
        file_put_contents( $file_path, json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) );
        
        wp_redirect( add_query_arg( 'status', 'announcements_saved', wp_get_referer() ) );
        exit;
    }
}
add_action( 'template_redirect', 'jfe_handle_form_submissions' );


function jfe_event_editor_page() {
    if ( isset( $_GET['status'] ) && $_GET['status'] == 'events_saved' ) {
        echo '<div class="updated" style="margin-bottom: 15px; border-left: 4px solid #46b450; padding: 10px;"><p>Events saved successfully.</p></div>';
    }
    $file_path            = WP_CONTENT_DIR . '/dataforapp/events.json';
    $reservation_statuses = [ 'available' => 'Available', 'few_left' => 'Few Left', 'full' => 'Full' ];
    $events               = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
    if ( ! is_array( $events ) ) {
        $events = [];
    }
    ?>
    <div class="wrap">
        <h1>Event Edit</h1>
        <p>Edit the details for each event below. Click "Save All Changes" at the bottom of the page to save your edits.</p>
        <form method="post" action="">
            <?php wp_nonce_field( 'jfe_event_action', 'jfe_event_nonce' ); ?>
            
            <?php foreach ( $events as $index => $event ) : ?>
                <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; background: #fff;">
                    <h2><?php echo esc_html( $event['eventName'] ?? 'New Event ' . ( $index + 1 ) ); ?></h2>
                    <table class="form-table">
                        <?php
                        jfe_create_editor_row( 'Event Name', "events[{$index}][eventName]", $event['eventName'] ?? '' );
                        jfe_create_editor_row( 'Building Name', "events[{$index}][buildingName]", $event['buildingName'] ?? '' );
                        jfe_create_editor_row( 'Time', "events[{$index}][time]", $event['time'] ?? '' );
                        jfe_create_editor_row( 'Date', "events[{$index}][date]", $event['date'] ?? '' );
                        jfe_create_editor_row( 'Description', "events[{$index}][description]", $event['description'] ?? '', 'textarea' );
                        
                        if ( isset( $event['status'] ) ) {
                            jfe_create_editor_row( 'Status', "events[{$index}][status]", ( $event['status'] ?? false ) ? 'true' : 'false', 'select', [ 'choices' => [ 'true' => '営業中 (Open)', 'false' => '営業終了 (Closed)' ] ] );
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
            <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save All Changes"></p>
        </form>
    </div>
    <?php
}

function jfe_announcement_editor_page() {
    if ( isset( $_GET['status'] ) && $_GET['status'] == 'announcements_saved' ) {
        echo '<div class="updated" style="margin-bottom: 15px; border-left: 4px solid #46b450; padding: 10px;"><p>Changes saved.</p></div>';
    }
    $file_path     = WP_CONTENT_DIR . '/dataforapp/announcements.json';
    $announcements = file_exists( $file_path ) ? json_decode( file_get_contents( $file_path ), true ) : [];
    if ( ! is_array( $announcements ) ) {
        $announcements = [];
    }
    ?>
    <div class="wrap">
        <h1>Announcement Edit</h1>
        <h2>Add New Announcement</h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'jfe_announcement_action', 'jfe_announcement_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><label for="new_message">Message</label></th>
                    <td><textarea id="new_message" name="new_message" rows="3" class="large-text"></textarea></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="new_enabled">Status</label></th>
                    <td>
                        <select id="new_enabled" name="new_enabled">
                            <option value="true">Show</option>
                            <option value="false">Hide</option>
                        </select>
                    </td>
                </tr>
            </table>
            <p class="submit"><input type="submit" name="add_announcement" id="add_announcement" class="button button-primary" value="Add Announcement"></p>
        </form>

        <hr>

        <h2>Edit Existing Announcements</h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'jfe_announcement_action', 'jfe_announcement_nonce' ); ?>

            <?php foreach ( $announcements as $announcement ) : 
                $id = esc_attr( $announcement['id'] ?? '' );
            ?>
                <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                    <h3>Announcement ID: <?php echo esc_html( $announcement['id'] ?? '' ); ?></h3>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><label for="message_<?php echo $id; ?>">Message</label></th>
                            <td><textarea id="message_<?php echo $id; ?>" name="announcements[<?php echo $id; ?>][message]" rows="3" class="large-text"><?php echo esc_textarea( $announcement['message'] ?? '' ); ?></textarea></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><label for="enabled_<?php echo $id; ?>">Status</label></th>
                            <td>
                                <select id="enabled_<?php echo $id; ?>" name="announcements[<?php echo $id; ?>][enabled]">
                                    <option value="true" <?php selected( $announcement['enabled'] ?? false, true ); ?>>Show</option>
                                    <option value="false" <?php selected( $announcement['enabled'] ?? false, false ); ?>>Hide</option>
                                </select>
                            </td>
                        </tr>
                         <tr valign="top">
                            <th scope="row"><label for="delete_<?php echo $id; ?>">Delete</label></th>
                            <td>
                                <input type="checkbox" id="delete_<?php echo $id; ?>" name="announcements[<?php echo $id; ?>][delete]" value="1">
                                <span class="description">Check to delete this announcement on save.</span>
                            </td>
                        </tr>
                    </table>
                </div>
            <?php endforeach; ?>

            <p class="submit"><input type="submit" name="update_announcements" id="update_announcements" class="button button-primary" value="Save All Changes"></p>
        </form>
    </div>
    <?php
}