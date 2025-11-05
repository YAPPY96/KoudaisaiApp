jQuery(document).ready(function($) {
    let currentFile = null;
    let currentData = null;
    let currentEditingIndex = null;
    
    // テンプレート定義
    const templates = {
        'events.json': {
            buildingName: "",
            eventName: "",
            time: "",
            description: "",
            date: "",
            image_L: "",
            image_B: "",
            status: true,
            groupName: "",
            snsLink: "",
            reservation: false,
            caution: "",
            others: ""
        },
        'announcements.json': {
            id: "",
            message: "",
            enabled: true
        },
        'others.json': {
            eventName: "",
            groupName: "",
            image: "",
            description: ""
        }
    };
    
    // ファイル選択
    $('.kjm-file-btn').on('click', function() {
        const file = $(this).data('file');
        $('.kjm-file-btn').removeClass('active');
        $(this).addClass('active');
        loadFile(file);
    });
    
    // ファイル読み込み
    function loadFile(file) {
        currentFile = file;
        currentEditingIndex = null;
        $('#kjm-empty').hide();
        $('#kjm-content').hide();
        $('#kjm-loading').show();
        
        $.ajax({
            url: kjmAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'kjm_load_json',
                nonce: kjmAjax.nonce,
                file: file
            },
            success: function(response) {
                if (response.success) {
                    currentData = response.data.data;
                    renderEditor(file, currentData);
                    $('#kjm-loading').hide();
                    $('#kjm-content').show();
                } else {
                    alert('エラー: ' + response.data);
                    $('#kjm-loading').hide();
                    $('#kjm-empty').show();
                }
            },
            error: function() {
                alert('通信エラーが発生しました');
                $('#kjm-loading').hide();
                $('#kjm-empty').show();
            }
        });
    }
    
    // エディタのレンダリング
    function renderEditor(file, data) {
        const fileNames = {
            'events.json': 'イベント情報',
            'stage.json': 'ステージ企画',
            '5253.json': '52・53号館',
            'others.json': 'その他',
            'announcements.json': 'お知らせ'
        };
        
        $('#kjm-file-title').text(fileNames[file] || file);
        $('#kjm-editor').empty();
        
        if (file === '5253.json') {
            $('#kjm-item-selector').hide();
            render5253Editor(data);
        } else if (Array.isArray(data)) {
            $('#kjm-item-selector').show();
            
            // stage.jsonは追加ボタンを非表示
            if (file === 'stage.json') {
                $('#kjm-add-item-btn').hide();
            } else {
                $('#kjm-add-item-btn').show();
            }
            
            setupItemSelector(data, file);
            $('#kjm-editor').html('<div class="kjm-empty" style="padding:50px;text-align:center;color:#787c82;"><span class="dashicons dashicons-arrow-up-alt2" style="font-size:40px;"></span><p style="font-size:16px;margin-top:10px;">上のプルダウンから編集する項目を選択してください</p></div>');
        } else {
            $('#kjm-item-selector').hide();
            renderObjectEditor(data, file);
        }
    }
    
    // 項目選択のセットアップ
    function setupItemSelector(data, file) {
        let options = '<option value="">-- 選択してください --</option>';
        
        data.forEach((item, index) => {
            if (!item || Object.keys(item).length === 0) return;
            
            // 項目を識別するための表示名を作成
            let displayName = `項目 ${index + 1}`;
            
            if (file === 'events.json') {
                if (item.eventName) displayName += ` - ${item.eventName}`;
                if (item.buildingName) displayName += ` (${item.buildingName})`;
                if (item.date) displayName += ` [${item.date}]`;
            } else if (file === 'stage.json') {
                if (item.eventName) displayName += ` - ${item.eventName}`;
                if (item.time) displayName += ` (${item.time})`;
                if (item.date) displayName += ` [${item.date}]`;
            } else if (file === 'announcements.json') {
                if (item.message) {
                    const msg = item.message.length > 40 ? item.message.substring(0, 40) + '...' : item.message;
                    displayName += ` - ${msg}`;
                }
            } else {
                if (item.eventName) displayName += ` - ${item.eventName}`;
                else if (item.groupName) displayName += ` - ${item.groupName}`;
                else if (item.buildingName) displayName += ` - ${item.buildingName}`;
            }
            
            options += `<option value="${index}">${escapeHtml(displayName)}</option>`;
        });
        
        $('#kjm-item-select').html(options).off('change').on('change', function() {
            const selectedIndex = $(this).val();
            if (selectedIndex === '') {
                $('#kjm-editor').html('<div class="kjm-empty" style="padding:50px;text-align:center;color:#787c82;"><span class="dashicons dashicons-arrow-up-alt2" style="font-size:40px;"></span><p style="font-size:16px;margin-top:10px;">上のプルダウンから編集する項目を選択してください</p></div>');
                currentEditingIndex = null;
            } else {
                currentEditingIndex = parseInt(selectedIndex);
                renderSingleItem(data[selectedIndex], selectedIndex);
            }
        });
    }
    
    // 単一項目のレンダリング
    function renderSingleItem(item, index) {
        let html = `<div class="kjm-item" data-index="${index}">`;
        html += `<div class="kjm-item-header">`;
        html += `<div class="kjm-item-title">項目 ${parseInt(index) + 1} を編集中</div>`;
        html += `<button class="kjm-delete-item-btn" data-index="${index}">
                    <span class="dashicons dashicons-trash"></span>
                    削除
                 </button>`;
        html += `</div>`;
        
        for (const [key, value] of Object.entries(item)) {
            html += renderField(key, value, `[${index}].${key}`);
        }
        
        html += `</div>`;
        $('#kjm-editor').html(html);
        
        // トグルスイッチのイベント（編集フォーム用）
        $('#kjm-editor .kjm-toggle-switch').on('click', function() {
            const currentValue = $(this).data('value');
            const newValue = !currentValue;
            
            if (newValue) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
            
            $(this).data('value', newValue);
            $(this).siblings('.kjm-toggle-label').text(newValue ? '有効' : '無効');
        });
        
        // 削除ボタンのイベント
        $('.kjm-delete-item-btn').on('click', function() {
            const itemIndex = $(this).data('index');
            deleteItem(itemIndex);
        });
    }
    
    // 52・53号館用エディタ
    function render5253Editor(data) {
        let html = '<div class="kjm-5253-grid">';
        
        for (const [roomKey, roomData] of Object.entries(data)) {
            if (!roomData.eventName) continue;
            
            const status = roomData.status === true || roomData.status === 'true';
            html += `
                <div class="kjm-5253-room" data-room="${roomKey}">
                    <div class="kjm-5253-room-header">
                        <div class="kjm-5253-room-name">${roomKey}</div>
                        <div class="kjm-toggle">
                            <div class="kjm-toggle-switch ${status ? 'active' : ''}" data-room="${roomKey}">
                            </div>
                        </div>
                    </div>
                    <div class="kjm-5253-event">${escapeHtml(roomData.eventName || '未設定')}</div>
                    <div class="kjm-5253-event" style="font-size:12px;color:#787c82;">${escapeHtml(roomData.groupName || '')}</div>
                </div>
            `;
        }
        
        html += '</div>';
        $('#kjm-editor').html(html);
        
        // トグルスイッチのイベント
        $('.kjm-toggle-switch').on('click', function() {
            const room = $(this).data('room');
            toggle5253Status(room, $(this));
        });
    }
    
    // オブジェクトデータ用エディタ
    function renderObjectEditor(data, file) {
        let html = '<div class="kjm-item">';
        
        for (const [key, value] of Object.entries(data)) {
            html += renderField(key, value, key);
        }
        
        html += '</div>';
        $('#kjm-editor').html(html);
    }
    
    // フィールドのレンダリング
    function renderField(key, value, path) {
        let html = '<div class="kjm-field">';
        html += `<label>${escapeHtml(key)}</label>`;
        
        // statusとreservationキー、または値がブール値の場合はトグルスイッチを表示
        if (key === 'status' || key === 'reservation' || typeof value === 'boolean') {
            const isTrue = value === true || value === 'true';
            html += `
                <div class="kjm-toggle">
                    <div class="kjm-toggle-switch ${isTrue ? 'active' : ''}" data-path="${path}" data-value="${isTrue}">
                    </div>
                    <span class="kjm-toggle-label">${isTrue ? '有効' : '無効'}</span>
                </div>
            `;
        } else if (typeof value === 'string' && value.length > 100) {
            html += `<textarea data-path="${path}">${escapeHtml(value)}</textarea>`;
        } else if (typeof value === 'string' && (value.startsWith('http://') || value.startsWith('https://'))) {
            html += `<input type="url" value="${escapeHtml(value)}" data-path="${path}">`;
        } else if (typeof value === 'object' && value !== null) {
            html += `<textarea data-path="${path}">${escapeHtml(JSON.stringify(value, null, 2))}</textarea>`;
        } else {
            html += `<input type="text" value="${escapeHtml(String(value))}" data-path="${path}">`;
        }
        
        html += '</div>';
        return html;
    }
    
    // 52・53号館のステータス切り替え
    function toggle5253Status(room, element) {
        $.ajax({
            url: kjmAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'kjm_toggle_5253_status',
                nonce: kjmAjax.nonce,
                room: room
            },
            success: function(response) {
                if (response.success) {
                    element.toggleClass('active');
                    showMessage('保存しました', 'success');
                } else {
                    alert('エラー: ' + response.data);
                }
            },
            error: function() {
                alert('通信エラーが発生しました');
            }
        });
    }
    
    // 新規項目追加
    $('#kjm-add-item-btn').on('click', function() {
        if (!currentFile || !Array.isArray(currentData)) return;
        
        if (templates[currentFile]) {
            const newItem = JSON.parse(JSON.stringify(templates[currentFile]));
            
            // IDが必要な場合は自動生成
            if (currentFile === 'announcements.json') {
                newItem.id = String(Date.now());
            }
            
            currentData.push(newItem);
            const newIndex = currentData.length - 1;
            
            // セレクトボックスを更新
            setupItemSelector(currentData, currentFile);
            
            // 新規項目を選択して表示
            $('#kjm-item-select').val(newIndex).trigger('change');
            
            showMessage('新しい項目を追加しました。編集して保存してください。', 'success');
        }
    });
    
    // 項目削除
    function deleteItem(index) {
        if (!confirm('この項目を削除しますか？')) return;
        
        currentData.splice(index, 1);
        currentEditingIndex = null;
        
        // セレクトボックスを更新
        setupItemSelector(currentData, currentFile);
        
        // エディタをクリア
        $('#kjm-editor').html('<div class="kjm-empty" style="padding:50px;text-align:center;color:#787c82;"><span class="dashicons dashicons-arrow-up-alt2" style="font-size:40px;"></span><p style="font-size:16px;margin-top:10px;">項目を削除しました。変更を保存してください。</p></div>');
        
        showMessage('項目を削除しました。保存ボタンをクリックして確定してください。', 'success');
    }
    
    // 保存ボタン
    $('#kjm-save-btn').on('click', function() {
        if (!currentFile || !currentData) {
            alert('保存するデータがありません');
            return;
        }
        
        if (currentFile === '5253.json') {
            alert('52・53号館のstatusは個別に切り替えられます。その他の編集には直接JSONファイルを編集してください。');
            return;
        }
        
        // 確認ダイアログ
        if (!confirm('変更を保存しますか？')) {
            return;
        }
        
        // フォームからデータを収集
        const updatedData = collectFormData();
        
        // JSON文字列に変換（整形付き）
        const jsonString = JSON.stringify(updatedData, null, 2);
        
        // 保存中のメッセージ
        $('#kjm-save-btn').prop('disabled', true).text('保存中...');
        
        $.ajax({
            url: kjmAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'kjm_save_json',
                nonce: kjmAjax.nonce,
                file: currentFile,
                data: jsonString
            },
            success: function(response) {
                $('#kjm-save-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> 保存');
                
                if (response.success) {
                    showMessage('保存しました！ファイルサイズ: ' + response.data.filesize + ' bytes', 'success');
                    currentData = updatedData;
                    
                    // プルダウンを再構築（項目名が変更された可能性があるため）
                    if (Array.isArray(currentData)) {
                        const currentIndex = currentEditingIndex;
                        setupItemSelector(currentData, currentFile);
                        if (currentIndex !== null) {
                            $('#kjm-item-select').val(currentIndex);
                        }
                    }
                } else {
                    alert('保存エラー: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                $('#kjm-save-btn').prop('disabled', false).html('<span class="dashicons dashicons-yes"></span> 保存');
                alert('通信エラーが発生しました: ' + error);
            }
        });
    });
    
    // フォームデータの収集
    function collectFormData() {
        const data = JSON.parse(JSON.stringify(currentData));
        
        // 現在編集中の項目のみを更新
        if (currentEditingIndex !== null && Array.isArray(data)) {
            $('#kjm-editor input, #kjm-editor textarea').each(function() {
                const path = $(this).data('path');
                if (!path) return;
                
                let value;
                if ($(this).attr('type') === 'checkbox') {
                    value = $(this).is(':checked');
                } else if ($(this).is('textarea')) {
                    try {
                        value = JSON.parse($(this).val());
                    } catch (e) {
                        value = $(this).val();
                    }
                } else {
                    value = $(this).val();
                }
                
                setNestedValue(data, path, value);
            });
            
            // トグルスイッチの値を収集
            $('#kjm-editor .kjm-toggle-switch').each(function() {
                const path = $(this).data('path');
                if (!path) return;
                
                let value = $(this).data('value');
                // data-valueから取得した値は文字列の"true" "false"になるため、ブール値に変換
                if (typeof value === 'string') {
                    value = value === 'true';
                }
                setNestedValue(data, path, value);
            });
        } else {
            // 配列でない場合は全体を更新
            $('#kjm-editor input, #kjm-editor textarea').each(function() {
                const path = $(this).data('path');
                if (!path) return;
                
                let value;
                if ($(this).attr('type') === 'checkbox') {
                    value = $(this).is(':checked');
                } else if ($(this).is('textarea')) {
                    try {
                        value = JSON.parse($(this).val());
                    } catch (e) {
                        value = $(this).val();
                    }
                } else {
                    value = $(this).val();
                }
                
                setNestedValue(data, path, value);
            });
            
            // トグルスイッチの値を収集
            $('#kjm-editor .kjm-toggle-switch').each(function() {
                const path = $(this).data('path');
                if (!path) return;
                
                const value = $(this).data('value');
                setNestedValue(data, path, value);
            });
        }
        
        return data;
    }
    
    // ネストされた値の設定
    function setNestedValue(obj, path, value) {
        const keys = path.replace(/\[(\d+)\]/g, '.$1').replace(/^\./, '').split('.');
        let current = obj;
        
        for (let i = 0; i < keys.length - 1; i++) {
            const key = keys[i];
            if (!(key in current)) {
                current[key] = {};
            }
            current = current[key];
        }
        
        current[keys[keys.length - 1]] = value;
    }
    
    // メッセージ表示
    function showMessage(message, type) {
        const className = type === 'success' ? 'kjm-success' : 'kjm-error';
        const $message = $(`<div class="${className}">${message}</div>`);
        $('#kjm-content').prepend($message);
        setTimeout(() => $message.fadeOut(() => $message.remove()), 3000);
    }
    
    // HTMLエスケープ
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }
});