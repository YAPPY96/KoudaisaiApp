<?php
/**
 * Admin Page Template
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap json-editor-wrap">
    <h1>JSON Editor</h1>
    
    <div class="json-editor-tabs">
        <button class="json-editor-tab active" data-tab="events">Events</button>
        <button class="json-editor-tab" data-tab="5253">52・53号館</button>
        <button class="json-editor-tab" data-tab="others">Others</button>
        <button class="json-editor-tab" data-tab="announcements">Announcements</button>
    </div>
    
    <!-- Events Tab -->
    <div id="events-tab" class="json-editor-tab-content active">
        <div class="json-editor-container">
            <div class="json-editor-left">
                <div class="editor-controls">
                    <label>イベントを選択:</label>
                    <select id="events-selector">
                        <option value="">選択してください</option>
                    </select>
                    <button class="button button-primary" id="events-add">新規追加</button>
                </div>
                <div id="events-editor"></div>
                <button class="button button-primary button-large" id="events-save">保存</button>
            </div>
            <div class="json-editor-right">
                <h3>画像一覧</h3>
                <div class="image-upload">
                    <input type="file" id="events-image-upload" accept=".webp">
                    <button class="button" id="events-upload-btn">画像アップロード</button>
                </div>
                <div id="events-images" class="image-list"></div>
            </div>
        </div>
    </div>
    
    <!-- 5253 Tab -->
    <div id="5253-tab" class="json-editor-tab-content">
        <div class="json-editor-container">
            <div class="json-editor-left">
                <div class="editor-controls">
                    <label>部屋を選択:</label>
                    <select id="5253-selector">
                        <option value="">選択してください</option>
                    </select>
                    <button class="button button-primary" id="5253-add">新規追加</button>
                </div>
                <div id="5253-editor"></div>
                <button class="button button-primary button-large" id="5253-save">保存</button>
            </div>
            <div class="json-editor-right">
                <h3>画像一覧</h3>
                <div class="image-upload">
                    <input type="file" id="5253-image-upload" accept=".webp">
                    <button class="button" id="5253-upload-btn">画像アップロード</button>
                </div>
                <div id="5253-images" class="image-list"></div>
            </div>
        </div>
    </div>
    
    <!-- Others Tab -->
    <div id="others-tab" class="json-editor-tab-content">
        <div class="json-editor-container-full">
            <div class="editor-controls">
                <label>項目を選択:</label>
                <select id="others-selector">
                    <option value="">選択してください</option>
                </select>
                <button class="button button-primary" id="others-add">新規追加</button>
            </div>
            <div id="others-editor"></div>
            <button class="button button-primary button-large" id="others-save">保存</button>
        </div>
    </div>
    
    <!-- Announcements Tab -->
    <div id="announcements-tab" class="json-editor-tab-content">
        <div class="json-editor-container-full">
            <button class="button button-primary" id="announcements-add">新規追加</button>
            <div id="announcements-editor"></div>
            <button class="button button-primary button-large" id="announcements-save">保存</button>
        </div>
    </div>
    
    <div id="json-editor-message" class="notice" style="display:none;"></div>
</div>