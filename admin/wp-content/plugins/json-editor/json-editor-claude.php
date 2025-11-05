<?php
/**
 * Plugin Name: JSON Editor for Event Management
 * Plugin URI: https://example.com
 * Description: events.json, 5253.json, others.json, announcements.jsonを編集できるプラグイン
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: json-editor
 */

if (!defined('ABSPATH')) {
    exit;
}

class JSON_Editor_Plugin {
    private $data_dir;
    private $image_dir;
    
    public function __construct() {
        $this->data_dir = WP_CONTENT_DIR . '/dataforapp/';
        $this->image_dir = WP_CONTENT_DIR . '/dataforapp/image/';
        
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_save_json_data', array($this, 'ajax_save_json_data'));
        add_action('wp_ajax_upload_image', array($this, 'ajax_upload_image'));
        add_action('wp_ajax_get_json_data', array($this, 'ajax_get_json_data'));
        add_action('wp_ajax_delete_announcement', array($this, 'ajax_delete_announcement'));
        
        // ショートコード登録
        add_shortcode('event_json', array($this, 'event_json_shortcode'));
        add_shortcode('5253_json', array($this, 'room_5253_json_shortcode'));
        add_shortcode('others_json', array($this, 'others_json_shortcode'));
        add_shortcode('announcements_json', array($this, 'announcements_json_shortcode'));
        
        $this->init_directories();
    }
    
    private function init_directories() {
        if (!file_exists($this->data_dir)) {
            wp_mkdir_p($this->data_dir);
        }
        if (!file_exists($this->image_dir)) {
            wp_mkdir_p($this->image_dir);
        }
    }
    
    public function add_admin_menu() {
        add_menu_page(
            'JSON Editor',
            'JSON Editor',
            'manage_options',
            'json-editor',
            array($this, 'admin_page'),
            'dashicons-edit',
            30
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'toplevel_page_json-editor') {
            return;
        }
        
        wp_enqueue_style('json-editor-admin', plugin_dir_url(__FILE__) . 'admin-style.css', array(), '1.0.0');
        wp_enqueue_script('json-editor-admin', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0.0', true);
        
        wp_localize_script('json-editor-admin', 'jsonEditorAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('json_editor_nonce')
        ));
    }
    
    public function admin_page() {
        include plugin_dir_path(__FILE__) . 'admin-page.php';
    }
    
    public function ajax_get_json_data() {
        check_ajax_referer('json_editor_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $file = sanitize_text_field($_POST['file']);
        $file_path = $this->data_dir . $file;
        
        if (!file_exists($file_path)) {
            // ファイルが存在しない場合は空配列を返す
            $default_data = ($file === '5253.json') ? new stdClass() : array();
            wp_send_json_success(array(
                'data' => $default_data,
                'images' => $this->get_images()
            ));
            return;
        }
        
        $content = file_get_contents($file_path);
        $data = json_decode($content, true);
        
        if ($data === null) {
            $data = ($file === '5253.json') ? new stdClass() : array();
        }
        
        wp_send_json_success(array(
            'data' => $data,
            'images' => $this->get_images()
        ));
    }
    
    private function get_images() {
        $images = array();
        if (file_exists($this->image_dir)) {
            $files = scandir($this->image_dir);
            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'webp') {
                    $images[] = $file;
                }
            }
        }
        return $images;
    }
    
    public function ajax_save_json_data() {
        check_ajax_referer('json_editor_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $file = sanitize_text_field($_POST['file']);
        $data = $_POST['data'];
        
        // データの検証と変換
        if ($file === 'events.json' || $file === 'others.json' || $file === 'announcements.json') {
            if (!is_array($data)) {
                $data = array();
            }
        } elseif ($file === '5253.json') {
            if (!is_array($data) && !is_object($data)) {
                $data = new stdClass();
            }
        }
        
        $file_path = $this->data_dir . $file;
        
        $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if ($json_data === false) {
            wp_send_json_error('Invalid JSON data');
            return;
        }
        
        if (file_put_contents($file_path, $json_data) !== false) {
            wp_send_json_success('Data saved successfully');
        } else {
            wp_send_json_error('Failed to save data');
        }
    }
    
    public function ajax_upload_image() {
        check_ajax_referer('json_editor_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        if (!isset($_FILES['image'])) {
            wp_send_json_error('No file uploaded');
            return;
        }
        
        $file = $_FILES['image'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if ($extension !== 'webp') {
            wp_send_json_error('Only WebP files are allowed');
            return;
        }
        
        $filename = sanitize_file_name($file['name']);
        $destination = $this->image_dir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            wp_send_json_success(array('filename' => $filename));
        } else {
            wp_send_json_error('Failed to upload image');
        }
    }
    
    public function ajax_delete_announcement() {
        check_ajax_referer('json_editor_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $id = sanitize_text_field($_POST['id']);
        $file_path = $this->data_dir . 'announcements.json';
        
        if (!file_exists($file_path)) {
            wp_send_json_error('File not found');
            return;
        }
        
        $content = file_get_contents($file_path);
        $data = json_decode($content, true);
        
        if (!is_array($data)) {
            $data = array();
        }
        
        $filtered = array_filter($data, function($item) use ($id) {
            return isset($item['id']) && $item['id'] !== $id;
        });
        
        $json_data = json_encode(array_values($filtered), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        if (file_put_contents($file_path, $json_data) !== false) {
            wp_send_json_success('Announcement deleted');
        } else {
            wp_send_json_error('Failed to delete announcement');
        }
    }
    
    // ショートコード機能
    public function event_json_shortcode($atts) {
        $file_path = $this->data_dir . 'events.json';
        if (!file_exists($file_path)) {
            return '<p>Events data not found</p>';
        }
        $data = json_decode(file_get_contents($file_path), true);
        return '<pre>' . esc_html(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }
    
    public function room_5253_json_shortcode($atts) {
        $file_path = $this->data_dir . '5253.json';
        if (!file_exists($file_path)) {
            return '<p>5253 data not found</p>';
        }
        $data = json_decode(file_get_contents($file_path), true);
        return '<pre>' . esc_html(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }
    
    public function others_json_shortcode($atts) {
        $file_path = $this->data_dir . 'others.json';
        if (!file_exists($file_path)) {
            return '<p>Others data not found</p>';
        }
        $data = json_decode(file_get_contents($file_path), true);
        return '<pre>' . esc_html(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }
    
    public function announcements_json_shortcode($atts) {
        $file_path = $this->data_dir . 'announcements.json';
        if (!file_exists($file_path)) {
            return '<p>Announcements data not found</p>';
        }
        $data = json_decode(file_get_contents($file_path), true);
        return '<pre>' . esc_html(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
    }
}

new JSON_Editor_Plugin();