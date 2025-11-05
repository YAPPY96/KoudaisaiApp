jQuery(document).ready(function($) {
    let currentData = {};
    let currentImages = [];
    
    // タブ切り替え
    $('.json-editor-tab').on('click', function() {
        const tab = $(this).data('tab');
        $('.json-editor-tab').removeClass('active');
        $('.json-editor-tab-content').removeClass('active');
        $(this).addClass('active');
        $('#' + tab + '-tab').addClass('active');
        
        loadData(tab);
    });
    
    // 初期データ読み込み
    loadData('events');
    
    function loadData(type) {
        const fileMap = {
            'events': 'events.json',
            '5253': '5253.json',
            'others': 'others.json',
            'announcements': 'announcements.json'
        };
        
        $.ajax({
            url: jsonEditorAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'get_json_data',
                nonce: jsonEditorAjax.nonce,
                file: fileMap[type]
            },
            success: function(response) {
                if (response.success) {
                    currentData[type] = response.data.data;
                    currentImages = response.data.images;
                    
                    if (type === 'events' || type === '5253') {
                        populateSelector(type);
                        populateImages(type);
                    } else if (type === 'others') {
                        populateOthersSelector();
                    } else if (type === 'announcements') {
                        renderAnnouncements();
                    }
                }
            }
        });
    }
    
    function populateSelector(type) {
        const selector = $('#' + type + '-selector');
        selector.empty().append('<option value="">選択してください</option>');
        
        if (Array.isArray(currentData[type])) {
            currentData[type].forEach((item, index) => {
                const name = item.eventName || item.buildingName || 'Item ' + (index + 1);
                selector.append(`<option value="${index}">${name}</option>`);
            });
        } else {
            Object.keys(currentData[type] || {}).forEach(key => {
                const item = currentData[type][key];
                const name = item.eventName || key;
                selector.append(`<option value="${key}">${name}</option>`);
            });
        }
    }
    
    function populateOthersSelector() {
        const selector = $('#others-selector');
        selector.empty().append('<option value="">選択してください</option>');
        
        if (Array.isArray(currentData.others)) {
            currentData.others.forEach((item, index) => {
                const name = item.eventName || 'Item ' + (index + 1);
                selector.append(`<option value="${index}">${name}</option>`);
            });
        }
    }
    
    function populateImages(type) {
        const container = $('#' + type + '-images');
        container.empty();
        
        currentImages.forEach(image => {
            container.append(`<div class="image-item" data-image="${image}">${image}</div>`);
        });
        
        $('.image-item').on('click', function() {
            const image = $(this).data('image');
            $('.image-item').removeClass('selected');
            $(this).addClass('selected');
            
            const imageField = $(this).closest('.json-editor-container').find('input[name="image"], input[name="image_L"], input[name="image_B"]').first();
            if (imageField.length) {
                imageField.val(image);
            }
        });
    }
    
    // Events エディタ
    $('#events-selector').on('change', function() {
        const index = $(this).val();
        if (index === '') return;
        
        const item = currentData.events[index];
        renderEventsEditor(item, index);
    });
    
    function renderEventsEditor(item, index) {
        const editor = $('#events-editor');
        editor.empty();
        
        let html = `
            <input type="hidden" name="index" value="${index}">
            <div class="form-group">
                <label>建物名</label>
                <input type="text" name="buildingName" value="${item.buildingName || ''}">
            </div>
            <div class="form-group">
                <label>イベント名</label>
                <input type="text" name="eventName" value="${item.eventName || ''}">
            </div>
            <div class="form-group">
                <label>時間</label>
                <input type="text" name="time" value="${item.time || ''}">
            </div>
            <div class="form-group">
                <label>説明</label>
                <textarea name="description">${item.description || ''}</textarea>
            </div>
            <div class="form-group">
                <label>日付</label>
                <input type="text" name="date" value="${item.date || ''}">
            </div>
            <div class="form-group">
                <label>画像</label>
                <input type="text" name="image" value="${item.image || ''}">
            </div>
            <div class="form-group">
                <label>グループ名</label>
                <input type="text" name="groupName" value="${item.groupName || ''}">
            </div>
            <div class="form-group">
                <label>X (Twitter)</label>
                <input type="text" name="X" value="${item.X || ''}">
            </div>
            <div class="form-group">
                <label>Instagram</label>
                <input type="text" name="instagram" value="${item.instagram || ''}">
            </div>
            <div class="form-group">
                <label>ステータス</label>
                <select name="status">
                    <option value="true" ${item.status === true ? 'selected' : ''}>有効</option>
                    <option value="false" ${item.status === false ? 'selected' : ''}>無効</option>
                </select>
            </div>
        `;
        
        editor.append(html);
    }
    
    // Others エディタ
    $('#others-selector').on('change', function() {
        const index = $(this).val();
        if (index === '') return;
        
        const item = currentData.others[index];
        renderOthersEditor(item, index);
    });
    
    function renderOthersEditor(item, index) {
        const editor = $('#others-editor');
        editor.empty();
        
        let html = `
            <input type="hidden" name="index" value="${index}">
            <div class="form-group">
                <label>イベント名</label>
                <input type="text" name="eventName" value="${item.eventName || ''}">
            </div>
            <div class="form-group">
                <label>グループ名</label>
                <input type="text" name="groupName" value="${item.groupName || ''}">
            </div>
            <div class="form-group">
                <label>画像</label>
                <input type="text" name="image" value="${item.image || ''}">
            </div>
            <div class="form-group">
                <label>説明</label>
                <textarea name="description">${item.description || ''}</textarea>
            </div>
        `;
        
        editor.append(html);
    }
    
    // Announcements エディタ
    function renderAnnouncements() {
        const editor = $('#announcements-editor');
        editor.empty();
        
        if (!Array.isArray(currentData.announcements)) {
            currentData.announcements = [];
        }
        
        currentData.announcements.forEach((item, index) => {
            let html = `
                <div class="announcement-item" data-index="${index}">
                    <div class="form-group">
                        <label>ID</label>
                        <input type="text" name="id_${index}" value="${item.id || ''}">
                    </div>
                    <div class="form-group">
                        <label>メッセージ</label>
                        <textarea name="message_${index}">${item.message || ''}</textarea>
                    </div>
                    <div class="form-group">
                        <label>有効</label>
                        <select name="enabled_${index}">
                            <option value="true" ${item.enabled === true ? 'selected' : ''}>有効</option>
                            <option value="false" ${item.enabled === false ? 'selected' : ''}>無効</option>
                        </select>
                    </div>
                    <button type="button" class="button delete-btn" data-id="${item.id}">削除</button>
                </div>
            `;
            editor.append(html);
        });
        
        $('.delete-btn').on('click', function() {
            const id = $(this).data('id');
            if (confirm('本当に削除しますか?')) {
                deleteAnnouncement(id);
            }
        });
    }
    
    function deleteAnnouncement(id) {
        $.ajax({
            url: jsonEditorAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'delete_announcement',
                nonce: jsonEditorAjax.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    showMessage('削除しました', 'success');
                    loadData('announcements');
                } else {
                    showMessage('削除に失敗しました', 'error');
                }
            }
        });
    }
    
    // 新規追加ボタン
    $('#events-add').on('click', function() {
        const newItem = {
            buildingName: '',
            eventName: '',
            time: '',
            description: '',
            date: '',
            image_L: '',
            image_B: '',
            groupName: '',
            status: true,
            snsLink: '',
            reservation: false,
            others: '',
            caution: '',
            reservationSlots: []
        };
        
        if (!Array.isArray(currentData.events)) {
            currentData.events = [];
        }
        
        currentData.events.push(newItem);
        populateSelector('events');
        $('#events-selector').val(currentData.events.length - 1).trigger('change');
    });
    
    $('#5253-add').on('click', function() {
        const newKey = 'room' + Date.now();
        const newItem = {
            buildingName: '',
            eventName: '',
            time: '',
            description: '',
            date: '',
            image: '',
            groupName: '',
            X: '',
            instagram: '',
            status: true
        };
        
        if (typeof currentData['5253'] !== 'object') {
            currentData['5253'] = {};
        }
        
        currentData['5253'][newKey] = newItem;
        populateSelector('5253');
        $('#5253-selector').val(newKey).trigger('change');
    });
    
    $('#others-add').on('click', function() {
        const newItem = {
            eventName: '',
            groupName: '',
            image: '',
            description: ''
        };
        
        if (!Array.isArray(currentData.others)) {
            currentData.others = [];
        }
        
        currentData.others.push(newItem);
        populateOthersSelector();
        $('#others-selector').val(currentData.others.length - 1).trigger('change');
    });
    
    $('#announcements-add').on('click', function() {
        const newId = Date.now().toString();
        const newItem = {
            id: newId,
            message: '',
            enabled: false
        };
        
        if (!Array.isArray(currentData.announcements)) {
            currentData.announcements = [];
        }
        
        currentData.announcements.push(newItem);
        renderAnnouncements();
    });
    
    // 保存機能
    $('#events-save').on('click', function() {
        saveData('events', 'events.json');
    });
    
    $('#5253-save').on('click', function() {
        saveData('5253', '5253.json');
    });
    
    $('#others-save').on('click', function() {
        saveData('others', 'others.json');
    });
    
    $('#announcements-save').on('click', function() {
        saveData('announcements', 'announcements.json');
    });
    
    function saveData(type, filename) {
        let dataToSave;
        
        if (type === 'events') {
            const index = $('#events-editor input[name="index"]').val();
            if (index !== undefined && index !== '') {
                const formData = getFormData('#events-editor');
                
                // 予約スロットの処理
                const slots = [];
                $('.slot-item').each(function() {
                    const idx = $(this).data('index');
                    const time = $(`input[name="slot_time_${idx}"]`).val();
                    const status = $(`select[name="slot_status_${idx}"]`).val();
                    if (time) {
                        slots.push({ time: time, status: status });
                    }
                });
                formData.reservationSlots = slots;
                
                currentData.events[index] = formData;
            }
            dataToSave = currentData.events;
        } else if (type === '5253') {
            const key = $('#5253-editor input[name="key"]').val();
            if (key) {
                const formData = getFormData('#5253-editor');
                currentData['5253'][key] = formData;
            }
            dataToSave = currentData['5253'];
        } else if (type === 'others') {
            const index = $('#others-editor input[name="index"]').val();
            if (index !== undefined && index !== '') {
                const formData = getFormData('#others-editor');
                currentData.others[index] = formData;
            }
            dataToSave = currentData.others;
        } else if (type === 'announcements') {
            const items = [];
            $('.announcement-item').each(function() {
                const index = $(this).data('index');
                const id = $(`input[name="id_${index}"]`).val();
                const message = $(`textarea[name="message_${index}"]`).val();
                const enabled = $(`select[name="enabled_${index}"]`).val() === 'true';
                
                items.push({ id: id, message: message, enabled: enabled });
            });
            dataToSave = items;
        }
        
        $.ajax({
            url: jsonEditorAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'save_json_data',
                nonce: jsonEditorAjax.nonce,
                file: filename,
                data: dataToSave
            },
            success: function(response) {
                if (response.success) {
                    showMessage('保存しました', 'success');
                } else {
                    showMessage('保存に失敗しました', 'error');
                }
            },
            error: function() {
                showMessage('保存に失敗しました', 'error');
            }
        });
    }
    
    function getFormData(container) {
        const data = {};
        
        $(container + ' input[type="text"], ' + container + ' input[type="date"], ' + container + ' textarea').each(function() {
            const name = $(this).attr('name');
            if (name && name !== 'index' && name !== 'key' && !name.startsWith('slot_')) {
                data[name] = $(this).val();
            }
        });
        
        $(container + ' select').each(function() {
            const name = $(this).attr('name');
            if (name && !name.startsWith('slot_')) {
                const value = $(this).val();
                if (name === 'status' || name === 'reservation' || name.startsWith('enabled_')) {
                    data[name] = value === 'true';
                } else {
                    data[name] = value;
                }
            }
        });
        
        return data;
    }
    
    // 画像アップロード
    $('#events-upload-btn').on('click', function() {
        uploadImage('events');
    });
    
    $('#5253-upload-btn').on('click', function() {
        uploadImage('5253');
    });
    
    function uploadImage(type) {
        const fileInput = $('#' + type + '-image-upload')[0];
        const file = fileInput.files[0];
        
        if (!file) {
            alert('ファイルを選択してください');
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'upload_image');
        formData.append('nonce', jsonEditorAjax.nonce);
        formData.append('image', file);
        
        $.ajax({
            url: jsonEditorAjax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showMessage('アップロードしました', 'success');
                    loadData(type);
                    fileInput.value = '';
                } else {
                    showMessage('アップロードに失敗しました: ' + response.data, 'error');
                }
            },
            error: function() {
                showMessage('アップロードに失敗しました', 'error');
            }
        });
    }
    
    function showMessage(message, type) {
        const messageDiv = $('#json-editor-message');
        messageDiv.removeClass('notice-success notice-error')
                  .addClass('notice-' + type)
                  .text(message)
                  .fadeIn();
        
        setTimeout(function() {
            messageDiv.fadeOut();
        }, 3000);
    }
});ント名</label>
                <input type="text" name="eventName" value="${item.eventName || ''}">
            </div>
            <div class="form-group">
                <label>時間</label>
                <input type="text" name="time" value="${item.time || ''}">
            </div>
            <div class="form-group">
                <label>説明</label>
                <textarea name="description">${item.description || ''}</textarea>
            </div>
            <div class="form-group">
                <label>日付</label>
                <input type="date" name="date" value="${item.date || ''}">
            </div>
            <div class="form-group">
                <label>画像_L</label>
                <input type="text" name="image_L" value="${item.image_L || ''}">
            </div>
            <div class="form-group">
                <label>画像_B</label>
                <input type="text" name="image_B" value="${item.image_B || ''}">
            </div>
            <div class="form-group">
                <label>グループ名</label>
                <input type="text" name="groupName" value="${item.groupName || ''}">
            </div>
            <div class="form-group">
                <label>ステータス</label>
                <select name="status">
                    <option value="true" ${item.status === true ? 'selected' : ''}>有効</option>
                    <option value="false" ${item.status === false ? 'selected' : ''}>無効</option>
                </select>
            </div>
            <div class="form-group">
                <label>SNSリンク</label>
                <input type="text" name="snsLink" value="${item.snsLink || ''}">
            </div>
            <div class="form-group">
                <label>予約</label>
                <select name="reservation">
                    <option value="true" ${item.reservation === true ? 'selected' : ''}>有効</option>
                    <option value="false" ${item.reservation === false ? 'selected' : ''}>無効</option>
                </select>
            </div>
            <div class="form-group">
                <label>その他</label>
                <textarea name="others">${item.others || ''}</textarea>
            </div>
            <div class="form-group">
                <label>注意事項</label>
                <textarea name="caution">${item.caution || ''}</textarea>
            </div>
        `;
        
        editor.append(html);
        
        if (item.reservationSlots) {
            renderReservationSlots(item.reservationSlots);
        }
    }
    
    function renderReservationSlots(slots) {
        let html = '<div class="form-group"><label>予約スロット</label><div class="reservation-slots" id="reservation-slots">';
        
        slots.forEach((slot, index) => {
            html += `
                <div class="slot-item" data-index="${index}">
                    <input type="text" name="slot_time_${index}" value="${slot.time}" placeholder="時間">
                    <select name="slot_status_${index}">
                        <option value="available" ${slot.status === 'available' ? 'selected' : ''}>available</option>
                        <option value="few_left" ${slot.status === 'few_left' ? 'selected' : ''}>few_left</option>
                        <option value="closed" ${slot.status === 'closed' ? 'selected' : ''}>closed</option>
                        <option value="full" ${slot.status === 'full' ? 'selected' : ''}>full</option>
                    </select>
                    <button type="button" class="button remove-slot">削除</button>
                </div>
            `;
        });
        
        html += '<button type="button" class="button" id="add-slot">スロット追加</button></div></div>';
        $('#events-editor').append(html);
        
        $('#add-slot').on('click', function() {
            const index = $('.slot-item').length;
            const slotHtml = `
                <div class="slot-item" data-index="${index}">
                    <input type="text" name="slot_time_${index}" placeholder="時間">
                    <select name="slot_status_${index}">
                        <option value="available">available</option>
                        <option value="few_left">few_left</option>
                        <option value="closed">closed</option>
                        <option value="full">full</option>
                    </select>
                    <button type="button" class="button remove-slot">削除</button>
                </div>
            `;
            $('#add-slot').before(slotHtml);
            attachRemoveSlotHandler();
        });
        
        attachRemoveSlotHandler();
    }
    
    function attachRemoveSlotHandler() {
        $('.remove-slot').off('click').on('click', function() {
            $(this).closest('.slot-item').remove();
        });
    }
    
    // 5253 エディタ
    $('#5253-selector').on('change', function() {
        const key = $(this).val();
        if (key === '') return;
        
        const item = currentData['5253'][key];
        render5253Editor(item, key);
    });
    
    function render5253Editor(item, key) {
        const editor = $('#5253-editor');
        editor.empty();
        
        let html = `
            <input type="hidden" name="key" value="${key}">
            <div class="form-group">
                <label>建物名</label>
                <input type="text" name="buildingName" value="${item.buildingName || ''}">
            </div>
            <div class="form-group">
                <label>イベ