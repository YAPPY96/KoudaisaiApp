jQuery(document).ready(function($) {
    let eventsData = null;
    const saveButton = $('#jfek-save-btn');
    const eventGrid = $('#jfek-event-grid');

    // Load events data
    function loadEvents() {
        $.ajax({
            url: jfekAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'jfek_load_json',
                nonce: jfekAjax.nonce,
                file: 'events.json'
            },
            success: function(response) {
                if (response.success) {
                    eventsData = response.data.data;
                    renderEventCards(eventsData);
                    setupEventHandlers();
                } else {
                    eventGrid.html('<p>Error loading events.</p>');
                }
            },
            error: function() {
                eventGrid.html('<p>An error occurred while loading events.</p>');
            }
        });
    }

    // Render the event cards
    function renderEventCards(events) {
        let html = '';
        const reservationStatuses = { 'available': 'Available', 'few_left': 'Few Left', 'full': 'Full' };

        events.forEach((event, index) => {
            html += `
                <div class="jfek-event-card" data-index="${index}">
                    <h2>${escapeHtml(event.eventName || 'Event')}</h2>
                    <div class="jfek-info-grid">
                        <div><strong>Date:</strong> ${escapeHtml(event.date || '—')}</div>
                        <div><strong>Time:</strong> ${escapeHtml(event.time || '—')}</div>
                        <div><strong>Building:</strong> ${escapeHtml(event.buildingName || '—')}</div>
                        <div><strong>Group:</strong> ${escapeHtml(event.groupName || '—')}</div>
                    </div>
                    
                    <div class="jfek-field">
                        <label>Status</label>
                        <div class="jfek-toggle">
                            <div class="jfek-toggle-switch ${event.status ? 'active' : ''}" data-field="status"></div>
                            <span class="jfek-toggle-label">${event.status ? 'Open' : 'Closed'}</span>
                        </div>
                    </div>

                    <div class="jfek-field">
                        <label>Reservation</label>
                        <div class="jfek-toggle">
                            <div class="jfek-toggle-switch ${event.reservation ? 'active' : ''}" data-field="reservation"></div>
                            <span class="jfek-toggle-label">${event.reservation ? 'Enabled' : 'Disabled'}</span>
                        </div>
                    </div>

                    <div class="jfek-field">
                        <label>Waiting List</label>
                        <select data-field="waitinglist">
                            <option value="true" ${event.waitinglist ? 'selected' : ''}>Enabled</option>
                            <option value="false" ${!event.waitinglist ? 'selected' : ''}>Disabled</option>
                        </select>
                    </div>
                    
                    <div class="jfek-field">
                        <label>Waiting Sum Select</label>
                        <input type="number" value="${escapeHtml(event.waitingSumSelect || 0)}" data-field="waitingSumSelect" />
                    </div>
                    
                    ${(event.reservationSlots || []).map((slot, slotIndex) => `
                        <div class="jfek-field">
                            <label>Reservation Slot: ${escapeHtml(slot.time)}</label>
                            <select data-field="reservationSlots" data-slot-index="${slotIndex}">
                                ${Object.keys(reservationStatuses).map(key => `
                                    <option value="${key}" ${slot.status === key ? 'selected' : ''}>${reservationStatuses[key]}</option>
                                `).join('')}
                            </select>
                        </div>
                    `).join('')}
                </div>
            `;
        });
        eventGrid.html(html);
    }

    // Setup event handlers
    function setupEventHandlers() {
        // Toggle switches
        $('.jfek-toggle-switch').on('click', function() {
            const card = $(this).closest('.jfek-event-card');
            const index = card.data('index');
            const field = $(this).data('field');
            
            const currentValue = eventsData[index][field];
            const newValue = !currentValue;
            eventsData[index][field] = newValue;
            
            $(this).toggleClass('active', newValue);
            const label = field === 'status' ? (newValue ? 'Open' : 'Closed') : (newValue ? 'Enabled' : 'Disabled');
            $(this).siblings('.jfek-toggle-label').text(label);
            
            saveButton.prop('disabled', false);
        });
        
        // Select dropdowns
        $('select').on('change', function() {
            const card = $(this).closest('.jfek-event-card');
            const index = card.data('index');
            const field = $(this).data('field');
            
            if (field === 'reservationSlots') {
                const slotIndex = $(this).data('slot-index');
                eventsData[index].reservationSlots[slotIndex].status = $(this).val();
            } else {
                eventsData[index][field] = $(this).val() === 'true';
            }
            saveButton.prop('disabled', false);
        });

        // Input fields
        $('input[type="number"]').on('input', function() {
            const card = $(this).closest('.jfek-event-card');
            const index = card.data('index');
            const field = $(this).data('field');
            
            eventsData[index][field] = parseInt($(this).val(), 10);
            saveButton.prop('disabled', false);
        });
    }

    // Save button
    saveButton.on('click', function() {
        if (!eventsData) return;

        $(this).prop('disabled', true).text('Saving...');
        
        $.ajax({
            url: jfekAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'jfek_save_json',
                nonce: jfekAjax.nonce,
                file: 'events.json',
                data: JSON.stringify(eventsData)
            },
            success: function(response) {
                if (response.success) {
                    alert('Events saved successfully!');
                } else {
                    alert('Error saving events: ' + response.data);
                }
            },
            error: function() {
                alert('An error occurred while saving.');
            },
            complete: function() {
                saveButton.text('Save Changes');
            }
        });
    });

    // Helper to escape HTML
    function escapeHtml(text) {
        if (typeof text !== 'string') return text;
        return text.replace(/[&<>"']/g, function(m) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[m];
        });
    }

    // Initial load
    loadEvents();
});