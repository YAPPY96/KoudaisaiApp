<?php
/*
Plugin Name: JSON File Editor
Description: A plugin to edit JSON files for events and announcements.
Version: 1.0
Author: Your Name
*/

// To prevent direct access to the plugin file
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Add menu items to the admin screen
add_action('admin_menu', 'jfe_add_admin_menu');

function jfe_add_admin_menu() {
    add_menu_page(
        'JSON Editor',          // Page title
        'JSON Editor',          // Menu title
        'manage_options',       // Capability
        'jfe_main_menu',        // Menu slug
        '',                     // Function (empty for parent menu)
        'dashicons-edit',       // Icon
        6                       // Position
    );

    add_submenu_page(
        'jfe_main_menu',        // Parent slug
        'Event Edit',           // Page title
        'Event Edit',           // Menu title
        'manage_options',       // Capability
        'jfe_event_editor',     // Menu slug
        'jfe_event_editor_page' // Function to display the page
    );

    add_submenu_page(
        'jfe_main_menu',        // Parent slug
        'Announcement Edit',    // Page title
        'Announcement Edit',    // Menu title
        'manage_options',       // Capability
        'jfe_announcement_editor', // Menu slug
        'jfe_announcement_editor_page' // Function to display the page
    );
}

function jfe_event_editor_page() {
    $file_path = WP_CONTENT_DIR . '/dataforapp/events.json';
    $reservation_statuses = ['available' => 'Available', 'few_left' => 'Few Left', 'full' => 'Full'];

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jfe_event_nonce']) && wp_verify_nonce($_POST['jfe_event_nonce'], 'jfe_event_action')) {
        if (current_user_can('manage_options')) {
            $original_data = json_decode(file_get_contents($file_path), true);
            $submitted_events = $_POST['events'];

            foreach ($original_data as $index => &$event) {
                if (isset($submitted_events[$index])) {
                    $submitted_event = $submitted_events[$index];
                    
                    // Update text and textarea fields
                    $text_fields = ['buildingName', 'eventName', 'time', 'date', 'image_L', 'image_B', 'groupName', 'snsLink'];
                    foreach($text_fields as $field){
                        if(isset($submitted_event[$field])){
                            $event[$field] = sanitize_text_field($submitted_event[$field]);
                        }
                    }
                    $textarea_fields = ['description', 'caution', 'others'];
                     foreach($textarea_fields as $field){
                        if(isset($submitted_event[$field])){
                            $event[$field] = sanitize_textarea_field($submitted_event[$field]);
                        }
                    }

                    // Update boolean status
                    if (isset($event['status'])) {
                        $event['status'] = (isset($submitted_event['status']) && $submitted_event['status'] === 'true');
                    }

                    // Update reservation slots status
                    if (!empty($event['reservationSlots']) && isset($submitted_event['reservationSlots'])) {
                        foreach ($event['reservationSlots'] as $slot_index => &$slot) {
                            if (isset($submitted_event['reservationSlots'][$slot_index]['status'])) {
                                $new_status = sanitize_text_field($submitted_event['reservationSlots'][$slot_index]['status']);
                                if (array_key_exists($new_status, $reservation_statuses)) {
                                    $slot['status'] = $new_status;
                                }
                            }
                        }
                    }
                }
            }
            
            file_put_contents($file_path, json_encode($original_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            echo '<div class="updated"><p>Events saved successfully.</p></div>';
        } else {
            echo '<div class="error"><p>You do not have sufficient permissions to perform this action.</p></div>';
        }
    }

    $events = json_decode(file_get_contents($file_path), true);
    ?>
    <div class="wrap">
        <h1>Event Edit</h1>
        <p>Edit the details for each event below. Click "Save All Changes" at the bottom of the page to save your edits.</p>
        <form method="post">
            <?php wp_nonce_field('jfe_event_action', 'jfe_event_nonce'); ?>
            
            <?php foreach ($events as $index => $event): ?>
                <div style="border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; background: #fff;">
                    <h2><?php echo esc_html($event['eventName'] ?: 'New Event ' . ($index + 1)); ?></h2>
                    <table class="form-table">
                        <?php
                        // Helper function to create table rows
                        function jfe_create_editor_row($label, $name, $value, $type = 'text', $options = []) {
                            ?>
                            <tr valign="top">
                                <th scope="row"><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($label); ?></label></th>
                                <td>
                                    <?php if ($type === 'textarea'): ?>
                                        <textarea id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" rows="5" class="large-text"><?php echo esc_textarea($value); ?></textarea>
                                    <?php elseif ($type === 'select'): ?>
                                        <select id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>">
                                            <?php foreach ($options['choices'] as $val => $text): ?>
                                                <option value="<?php echo esc_attr($val); ?>" <?php selected($value, $val); ?>><?php echo esc_html($text); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else: ?>
                                        <input type="<?php echo esc_attr($type); ?>" id="<?php echo esc_attr($name); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }

                        // Display form fields for the event
                        jfe_create_editor_row('Event Name', "events[{$index}][eventName]", $event['eventName']);
                        jfe_create_editor_row('Building Name', "events[{$index}][buildingName]", $event['buildingName']);
                        jfe_create_editor_row('Time', "events[{$index}][time]", $event['time']);
                        jfe_create_editor_row('Date', "events[{$index}][date]", $event['date']);
                        jfe_create_editor_row('Description', "events[{$index}][description]", $event['description'], 'textarea');
                        
                        if (isset($event['status'])) {
                           jfe_create_editor_row('Status', "events[{$index}][status]", $event['status'] ? 'true' : 'false', 'select', ['choices' => ['true' => '営業中 (Open)', 'false' => '営業終了 (Closed)']]);
                        }
                        
                        jfe_create_editor_row('Group Name', "events[{$index}][groupName]", $event['groupName']);
                        jfe_create_editor_row('Caution', "events[{$index}][caution]", $event['caution'], 'textarea');
                        jfe_create_editor_row('Others', "events[{$index}][others]", $event['others'], 'textarea');
                        jfe_create_editor_row('Image L', "events[{$index}][image_L]", $event['image_L']);
                        jfe_create_editor_row('Image B', "events[{$index}][image_B]", $event['image_B']);
                        jfe_create_editor_row('SNS Link', "events[{$index}][snsLink]", $event['snsLink']);

                        if (!empty($event['reservationSlots'])) {
                            ?>
                            <tr valign="top">
                                <th scope="row">Reservation Slots</th>
                                <td>
                                    <?php foreach ($event['reservationSlots'] as $slot_index => $slot): ?>
                                        <div style="margin-bottom: 5px;">
                                            <strong><?php echo esc_html($slot['time']); ?>: </strong>
                                            <select name="events[<?php echo $index; ?>][reservationSlots][<?php echo $slot_index; ?>][status]">
                                                <?php foreach ($reservation_statuses as $val => $text): ?>
                                                    <option value="<?php echo esc_attr($val); ?>" <?php selected($slot['status'], $val); ?>><?php echo esc_html($text); ?></option>
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

            <?php submit_button('Save All Changes'); ?>
        </form>
    </div>
    <?php
}

function jfe_announcement_editor_page() {
    $file_path = WP_CONTENT_DIR . '/dataforapp/announcements.json';

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['jfe_announcement_nonce']) && wp_verify_nonce($_POST['jfe_announcement_nonce'], 'jfe_announcement_action')) {
            if (current_user_can('manage_options')) {
                $data = json_decode(file_get_contents($file_path), true);

                // Add new announcement
                if (isset($_POST['add_announcement'])) {
                    $new_id = 1;
                    if (!empty($data)) {
                        $last_item = end($data);
                        $new_id = $last_item['id'] + 1;
                    }
                    $new_announcement = [
                        'id' => (string)$new_id,
                        'message' => sanitize_textarea_field($_POST['new_message']),
                        'enabled' => $_POST['new_enabled'] === 'true' ? true : false,
                    ];
                    $data[] = $new_announcement;
                }

                // Update or delete existing announcements
                if (isset($_POST['update_announcement'])) {
                    $update_id = sanitize_text_field($_POST['id']);
                    foreach ($data as &$item) {
                        if ($item['id'] === $update_id) {
                            $item['message'] = sanitize_textarea_field($_POST['message'][$update_id]);
                            $item['enabled'] = $_POST['enabled'][$update_id] === 'true' ? true : false;
                            break;
                        }
                    }
                }

                if (isset($_POST['delete_announcement'])) {
                    $delete_id = sanitize_text_field($_POST['id']);
                    $data = array_filter($data, function ($item) use ($delete_id) {
                        return $item['id'] !== $delete_id;
                    });
                    // Re-index array if needed, but IDs are kept as is.
                }

                file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                echo '<div class="updated"><p>Changes saved.</p></div>';
            } else {
                echo '<div class="error"><p>You do not have permission to perform this action.</p></div>';
            }
        }
    }

    $announcements = json_decode(file_get_contents($file_path), true);
    ?>
    <div class="wrap">
        <h1>Announcement Edit</h1>

        <h2>Add New Announcement</h2>
        <form method="post">
            <?php wp_nonce_field('jfe_announcement_action', 'jfe_announcement_nonce'); ?>
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
            <?php submit_button('Add Announcement', 'primary', 'add_announcement'); ?>
        </form>

        <hr>

        <h2>Edit Existing Announcements</h2>
        <?php foreach ($announcements as $index => $announcement) : ?>
            <form method="post" style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">
                <?php wp_nonce_field('jfe_announcement_action', 'jfe_announcement_nonce'); ?>
                <input type="hidden" name="id" value="<?php echo esc_attr($announcement['id']); ?>">
                <h3>Announcement ID: <?php echo esc_html($announcement['id']); ?></h3>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="message_<?php echo esc_attr($announcement['id']); ?>">Message</label></th>
                        <td><textarea id="message_<?php echo esc_attr($announcement['id']); ?>" name="message[<?php echo esc_attr($announcement['id']); ?>]" rows="3" class="large-text"><?php echo esc_textarea($announcement['message']); ?></textarea></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><label for="enabled_<?php echo esc_attr($announcement['id']); ?>">Status</label></th>
                        <td>
                            <select id="enabled_<?php echo esc_attr($announcement['id']); ?>" name="enabled[<?php echo esc_attr($announcement['id']); ?>]">
                                <option value="true" <?php selected($announcement['enabled'], true); ?>>Show</option>
                                <option value="false" <?php selected($announcement['enabled'], false); ?>>Hide</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Update Announcement', 'primary', 'update_announcement'); ?>
                <?php submit_button('Delete Announcement', 'delete', 'delete_announcement', true, ['onclick' => 'return confirm("Are you sure you want to delete this announcement?");']); ?>
            </form>
        <?php endforeach; ?>
    </div>
    <?php
}
