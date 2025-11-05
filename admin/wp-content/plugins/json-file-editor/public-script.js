jQuery(document).ready(function($) {
    let currentFile = null;
    let currentData = null;
    let currentEditingIndex = null;

    // File selection
    $('.kjm-file-btn').on('click', function() {
        const file = $(this).data('file');
        $('.kjm-file-btn').removeClass('active');
        $(this).addClass('active');
        loadFile(file);
    });

    // Load file via AJAX
    function loadFile(file) {
        currentFile = file;
        currentEditingIndex = null;
        $('#kjm-empty').hide();
        $('#kjm-content').show();
        
        $.ajax({
            url: jfekAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'jfek_load_json',
                nonce: jfekAjax.nonce,
                file: file
            },
            success: function(response) {
                if (response.success) {
                    currentData = response.data.data;
                    renderEditor(file, currentData);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred.');
            }
        });
    }

    // Render the editor UI based on the file
    function renderEditor(file, data) {
        const fileNames = {
            '5253.json': '52・53号館',
            'events.json': 'イベント情報',
            'announcements.json': 'お知らせ'
        };
        $('#kjm-file-title').text(fileNames[file] || file);
        $('#kjm-editor').empty();

        if (file === '5253.json') {
             $('#kjm-item-selector').hide();
            render5253Editor(data);
        } else if (Array.isArray(data)) {
            $('#kjm-item-selector').show();
            setupItemSelector(data, file);
            $('#kjm-editor').html('<p>Select an item from the dropdown to edit.</p>');
        }
    }
    
    // Setup item selector dropdown
    function setupItemSelector(data, file) {
        let options = '<option value="">-- Select --</option>';
        data.forEach((item, index) => {
            let displayName = `Item ${index + 1}`;
            if (file === 'events.json' && item.eventName) {
                displayName = item.eventName;
            } else if (file === 'announcements.json' && item.message) {
                displayName = item.message.substring(0, 40) + '...';
            }
            options += `<option value="${index}">${escapeHtml(displayName)}</option>`;
        });
        
        $('#kjm-item-select').html(options).off('change').on('change', function() {
            const selectedIndex = $(this).val();
            if (selectedIndex === '') {
                $('#kjm-editor').html('<p>Select an item to edit.</p>');
                currentEditingIndex = null;
            } else {
                currentEditingIndex = parseInt(selectedIndex);
                renderSingleItem(data[selectedIndex], selectedIndex, file);
            }
        });
    }

    // Render a single item for editing
    function renderSingleItem(item, index, file) {
        let html = `<div class="kjm-item" data-index="${index}">`;

        // Add a header with a delete button, but hide it for announcements.json and events.json
        html += `<div class="kjm-item-header">`;
        html += `<div class="kjm-item-title">Editing Item ${index + 1}</div>`;
        if (file !== 'announcements.json' && file !== 'events.json') {
            html += `<button class="kjm-delete-item-btn" data-index="${index}">Delete</button>`;
        }
        html += `</div>`;
        
        for (const [key, value] of Object.entries(item)) {
            html += renderField(key, value, `[${index}].${key}`, file);
        }
        
        html += `</div>`;
        $('#kjm-editor').html(html);
        
        // Event handlers for toggle switches
        $('#kjm-editor .kjm-toggle-switch').on('click', function() {
            const currentValue = $(this).data('value');
            const newValue = !currentValue;
            $(this).toggleClass('active', newValue).data('value', newValue);
            $(this).siblings('.kjm-toggle-label').text(newValue ? 'Enabled' : 'Disabled');
        });

        // Event handler for the delete button
        $('.kjm-delete-item-btn').on('click', function() {
            if (!confirm('Are you sure you want to delete this item?')) return;
            const itemIndex = $(this).data('index');
            currentData.splice(itemIndex, 1);
            setupItemSelector(currentData, file);
            $('#kjm-editor').html('<p>Item deleted. Save to apply changes.</p>');
        });
    }
    
    // Render 5253.json editor
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
                            <div class="kjm-toggle-switch ${status ? 'active' : ''}" data-room="${roomKey}" data-value="${status}"></div>
                        </div>
                    </div>
                    <div>${escapeHtml(roomData.eventName || '')}</div>
                </div>
            `;
        }
        html += '</div>';
        $('#kjm-editor').html(html);

        $('#kjm-editor .kjm-toggle-switch').on('click', function() {
             const currentValue = $(this).data('value');
             const newValue = !currentValue;
             $(this).toggleClass('active', newValue).data('value', newValue);
        });
    }

    // Render a single field
    function renderField(key, value, path, file) {
        let html = '<div class="kjm-field">';
        html += `<label>${escapeHtml(key)}</label>`;

        const editableFields = {
            'events.json': ['status', 'reservation', 'reservationSlot', 'waitinglist', 'waitingSumSelect']
        };

        const isEditable = !editableFields[file] || editableFields[file].includes(key);

        if (!isEditable) {
            html += `<input type="text" value="${escapeHtml(String(value))}" readonly>`;
        } else if (key === 'status' || key === 'reservation' || typeof value === 'boolean') {
            const isTrue = value === true || value === 'true';
            html += `
                <div class="kjm-toggle">
                    <div class="kjm-toggle-switch ${isTrue ? 'active' : ''}" data-path="${path}" data-value="${isTrue}"></div>
                    <span class="kjm-toggle-label">${isTrue ? 'Enabled' : 'Disabled'}</span>
                </div>
            `;
        } else {
            html += `<input type="text" value="${escapeHtml(String(value))}" data-path="${path}">`;
        }
        
        html += '</div>';
        return html;
    }
    
    // Save button
    $('#kjm-save-btn').on('click', function() {
        if (!currentFile || !currentData) return;
        if (!confirm('Are you sure you want to save?')) return;

        const updatedData = collectFormData();
        
        $.ajax({
            url: jfekAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'jfek_save_json',
                nonce: jfekAjax.nonce,
                file: currentFile,
                data: JSON.stringify(updatedData)
            },
            success: function(response) {
                if (response.success) {
                    alert('Saved successfully!');
                    currentData = updatedData;
                } else {
                    alert('Error saving: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while saving.');
            }
        });
    });

    // Collect form data
    function collectFormData() {
        const data = JSON.parse(JSON.stringify(currentData));
        
        if (currentFile === '5253.json') {
             $('#kjm-editor .kjm-toggle-switch').each(function() {
                const room = $(this).data('room');
                if (room && data[room]) {
                    data[room].status = $(this).data('value');
                }
            });
        } else if (currentEditingIndex !== null && Array.isArray(data)) {
            $('#kjm-editor input[data-path], #kjm-editor textarea[data-path]').each(function() {
                setNestedValue(data, $(this).data('path'), $(this).val());
            });
            $('#kjm-editor .kjm-toggle-switch[data-path]').each(function() {
                let value = $(this).data('value');
                if (typeof value === 'string') {
                    value = value === 'true';
                }
                setNestedValue(data, $(this).data('path'), value);
            });
        }
        return data;
    }

    // Helper to set nested values in an object
    function setNestedValue(obj, path, value) {
        const keys = path.replace(/\[(\d+)\]/g, '.$1').replace(/^\./, '').split('.');
        let current = obj;
        for (let i = 0; i < keys.length - 1; i++) {
            current = current[keys[i]];
        }
        current[keys[keys.length - 1]] = value;
    }

    // Helper to escape HTML
    function escapeHtml(text) {
        if (typeof text !== 'string') return text;
        return text.replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }
});
