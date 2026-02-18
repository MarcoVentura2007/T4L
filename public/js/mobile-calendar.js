/**
 * Mobile Calendar Library for T4L Gestionale
 * Compact, professional calendar for mobile devices
 * Shows activities below when clicking on a day
 */

class MobileCalendar {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container #${containerId} not found`);
            return;
        }

        this.options = {
            onDayClick: null,
            onMonthChange: null,
            activitiesData: {},
            selectedDate: new Date(),
            activitiesPanel: null, // External panel selector or element
            ...options
        };

        this.currentDate = new Date(this.options.selectedDate);
        this.selectedDay = null;
        this.activitiesData = this.options.activitiesData || {};
        
        // Get external activities panel if provided
        this.externalPanel = null;
        if (this.options.activitiesPanel) {
            if (typeof this.options.activitiesPanel === 'string') {
                this.externalPanel = document.querySelector(this.options.activitiesPanel);
            } else if (this.options.activitiesPanel instanceof HTMLElement) {
                this.externalPanel = this.options.activitiesPanel;
            }
        }

        this.init();
    }


    init() {
        this.render();
        this.attachEvents();
    }

    /**
     * Render the calendar structure
     */
    render() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        const monthNames = [
            'Gennaio', 'Febbraio', 'Marzo', 'Aprile', 'Maggio', 'Giugno',
            'Luglio', 'Agosto', 'Settembre', 'Ottobre', 'Novembre', 'Dicembre'
        ];

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();

        // Adjust for Monday start (0 = Monday, 6 = Sunday)
        let startDay = firstDay.getDay();
        startDay = startDay === 0 ? 6 : startDay - 1;

        const days = ['Lun', 'Mar', 'Mer', 'Gio', 'Ven', 'Sab', 'Dom'];

        let html = `
            <div class="mobile-calendar">
                <div class="mc-header">
                    <button class="mc-nav-btn mc-prev" aria-label="Mese precedente">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="15,18 9,12 15,6"></polyline>
                        </svg>
                    </button>
                    <div class="mc-title">
                        <span class="mc-month">${monthNames[month]}</span>
                        <span class="mc-year">${year}</span>
                    </div>
                    <button class="mc-nav-btn mc-next" aria-label="Mese successivo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9,18 15,12 9,6"></polyline>
                        </svg>
                    </button>
                </div>
                <div class="mc-weekdays">
                    ${days.map(day => `<span>${day}</span>`).join('')}
                </div>
                <div class="mc-days">
        `;

        // Empty cells before first day
        for (let i = 0; i < startDay; i++) {
            html += `<div class="mc-day mc-empty"></div>`;
        }

        // Days of the month
        const today = new Date();
        const todayStr = this.formatDate(today);

        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = this.formatDate(new Date(year, month, day));
            const isToday = dateStr === todayStr;
            const hasActivity = this.activitiesData[dateStr] && this.activitiesData[dateStr].length > 0;
            const isSelected = this.selectedDay === dateStr;

            const classes = ['mc-day'];
            if (isToday) classes.push('mc-today');
            if (hasActivity) classes.push('mc-has-activity');
            if (isSelected) classes.push('mc-selected');

            html += `
                <div class="${classes.join(' ')}" data-date="${dateStr}">
                    <span class="mc-day-number">${day}</span>
                    ${hasActivity ? '<span class="mc-activity-dot"></span>' : ''}
                </div>
            `;
        }

        html += `
                </div>
            </div>
        `;

        this.container.innerHTML = html;
        
        // Initialize external panel if provided
        if (this.externalPanel) {
            this.externalPanel.innerHTML = `
                <div class="mc-activities-placeholder">
                    Seleziona un giorno per vedere le attività
                </div>
            `;
        }

    }

    /**
     * Attach event listeners
     */
    attachEvents() {
        // Navigation buttons
        const prevBtn = this.container.querySelector('.mc-prev');
        const nextBtn = this.container.querySelector('.mc-next');

        if (prevBtn) {
            prevBtn.addEventListener('click', () => this.changeMonth(-1));
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => this.changeMonth(1));
        }

        // Day clicks
        const days = this.container.querySelectorAll('.mc-day:not(.mc-empty)');
        days.forEach(day => {
            day.addEventListener('click', (e) => {
                const dateStr = day.dataset.date;
                this.selectDay(dateStr, day);
            });
        });
    }

    /**
     * Change current month
     */
    changeMonth(direction) {
        this.currentDate.setMonth(this.currentDate.getMonth() + direction);
        // Clear activities data when changing month - new data will be loaded by parent
        this.activitiesData = {};
        this.selectedDay = null;
        this.render();
        this.attachEvents();
        
        // Reset external panel to placeholder
        if (this.externalPanel) {
            this.externalPanel.innerHTML = `
                <div class="mc-activities-placeholder">
                    Caricamento attività...
                </div>
            `;
        }

        if (this.options.onMonthChange) {
            this.options.onMonthChange(this.currentDate);
        }
    }


    /**
     * Select a day and show activities
     */
    selectDay(dateStr, dayElement) {
        // Remove previous selection
        const prevSelected = this.container.querySelector('.mc-selected');
        if (prevSelected) {
            prevSelected.classList.remove('mc-selected');
        }

        // Add selection to clicked day
        if (dayElement) {
            dayElement.classList.add('mc-selected');
        }

        this.selectedDay = dateStr;

        // Show activities for this day
        this.showActivities(dateStr);

        // Callback
        if (this.options.onDayClick) {
            this.options.onDayClick(dateStr, this.activitiesData[dateStr] || []);
        }
    }

    /**
     * Show activities for selected day
     */
    showActivities(dateStr) {
        // Use external panel if provided, otherwise look for internal one
        let panel = this.externalPanel;
        if (!panel) {
            panel = this.container.querySelector('#mc-activities-panel');
        }
        
        const activities = this.activitiesData[dateStr] || [];

        if (!panel) return;

        if (activities.length === 0) {
            panel.innerHTML = `
                <div class="mc-activities-empty">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p>Nessuna attività per questo giorno</p>
                </div>
            `;
            return;
        }

        const [year, month, day] = dateStr.split('-');
        const dateFormatted = `${day}/${month}/${year}`;

        let html = `
            <div class="mc-activities-header">
                <h4>Attività del ${dateFormatted}</h4>
                <span class="mc-activities-count">${activities.length} attività</span>
            </div>
            <div class="mc-activities-list">
        `;

        activities.forEach(activity => {
            html += `
                <div class="mc-activity-item">
                    <div class="mc-activity-time">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"></circle>
                            <polyline points="12,6 12,12 16,14"></polyline>
                        </svg>
                        <span>${activity.ora_inizio || ''} - ${activity.ora_fine || ''}</span>
                    </div>
                    <div class="mc-activity-name">${activity.nome || 'Attività'}</div>
                    ${activity.descrizione ? `<div class="mc-activity-desc">${activity.descrizione}</div>` : ''}
                    ${activity.educatori ? `<div class="mc-activity-educatori">👤 ${activity.educatori}</div>` : ''}
                </div>
            `;
        });

        html += `</div>`;
        panel.innerHTML = html;
    }


    /**
     * Update activities data
     */
    setActivitiesData(data) {
        this.activitiesData = data || {};
        this.render();
        this.attachEvents();
    }

    /**
     * Format date as YYYY-MM-DD
     */
    formatDate(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    /**
     * Get current selected date
     */
    getSelectedDate() {
        return this.selectedDay;
    }

    /**
     * Destroy the calendar
     */
    destroy() {
        if (this.container) {
            this.container.innerHTML = '';
        }
    }
}

// Export for global use
window.MobileCalendar = MobileCalendar;
