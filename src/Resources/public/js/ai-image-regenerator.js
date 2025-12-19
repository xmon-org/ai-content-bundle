/**
 * AI Image Regenerator
 *
 * JavaScript class for handling AI image generation in Sonata Admin.
 * Manages the modal, AJAX calls, style selection, and history.
 */
class XmonAiImageRegenerator {
    constructor(modal) {
        this.modal = modal;
        this.modalEl = document.getElementById(modal.id);
        if (!this.modalEl) return;

        // Cache elements
        this.subjectInput = this.modalEl.querySelector('.xmon-ai-subject-input');
        this.styleModeInput = this.modalEl.querySelector('.xmon-ai-style-mode-input');
        this.presetSelect = this.modalEl.querySelector('.xmon-ai-preset-select');
        this.styleSelect = this.modalEl.querySelector('.xmon-ai-style-select');
        this.compositionSelect = this.modalEl.querySelector('.xmon-ai-composition-select');
        this.paletteSelect = this.modalEl.querySelector('.xmon-ai-palette-select');
        this.extraInput = this.modalEl.querySelector('.xmon-ai-extra-input');

        // Preview elements
        this.globalPreview = this.modalEl.querySelector('.xmon-ai-global-preview');
        this.presetPreview = this.modalEl.querySelector('.xmon-ai-preset-preview');
        this.customPreview = this.modalEl.querySelector('.xmon-ai-custom-preview');
        this.newPreview = this.modalEl.querySelector('.xmon-ai-new-preview');
        this.newPlaceholder = this.modalEl.querySelector('.xmon-ai-new-placeholder');
        this.newActions = this.modalEl.querySelector('.xmon-ai-new-actions');

        // Buttons
        this.generateSubjectBtn = this.modalEl.querySelector('.xmon-ai-generate-subject-btn');
        this.regenerateBtn = this.modalEl.querySelector('.xmon-ai-regenerate-btn');
        this.useNewBtn = this.modalEl.querySelector('.xmon-ai-use-new-btn');

        // Timer
        this.timerEl = this.modalEl.querySelector('.xmon-ai-generation-timer');
        this.timerValueEl = this.modalEl.querySelector('.xmon-ai-timer-value');
        this.timerInterval = null;
        this.timerStart = null;

        // Data
        this.stylesData = {};
        this.compositionsData = {};
        this.palettesData = {};
        this.presetsData = {};
        this.globalStyle = '';
        this.lastGeneratedResult = null;

        this.init();
    }

    init() {
        this.loadData();
        this.populateSelects();
        this.bindEvents();
        this.updatePreviews();
    }

    loadData() {
        // Load data from hidden script elements or data attributes
        const styleSelector = this.modalEl.closest('.xmon-ai-image-field')?.querySelector('[data-ai-style-selector]');
        if (styleSelector) {
            try {
                this.stylesData = JSON.parse(styleSelector.querySelector('.xmon-ai-styles-data')?.textContent || '{}');
                this.compositionsData = JSON.parse(styleSelector.querySelector('.xmon-ai-compositions-data')?.textContent || '{}');
                this.palettesData = JSON.parse(styleSelector.querySelector('.xmon-ai-palettes-data')?.textContent || '{}');
                this.presetsData = JSON.parse(styleSelector.querySelector('.xmon-ai-presets-data')?.textContent || '{}');
                this.globalStyle = styleSelector.querySelector('.xmon-ai-global-style')?.value || '';
            } catch (e) {
                console.error('Error loading AI image data:', e);
            }
        }
    }

    populateSelects() {
        // Populate preset select
        if (this.presetSelect) {
            Object.entries(this.presetsData).forEach(([key, data]) => {
                const option = new Option(data.name, key);
                this.presetSelect.appendChild(option);
            });
        }

        // Populate style select
        if (this.styleSelect) {
            this.styleSelect.innerHTML = '<option value="">Select...</option>';
            Object.entries(this.stylesData).forEach(([key, data]) => {
                const option = new Option(data.label, key);
                this.styleSelect.appendChild(option);
            });
        }

        // Populate composition select
        if (this.compositionSelect) {
            this.compositionSelect.innerHTML = '<option value="">Select...</option>';
            Object.entries(this.compositionsData).forEach(([key, data]) => {
                const option = new Option(data.label, key);
                this.compositionSelect.appendChild(option);
            });
        }

        // Populate palette select
        if (this.paletteSelect) {
            this.paletteSelect.innerHTML = '<option value="">Select...</option>';
            Object.entries(this.palettesData).forEach(([key, data]) => {
                const option = new Option(data.label, key);
                this.paletteSelect.appendChild(option);
            });
        }
    }

    bindEvents() {
        // Tab changes for style mode
        this.modalEl.querySelectorAll('[data-style-mode]').forEach(tab => {
            tab.addEventListener('click', (e) => {
                const mode = e.currentTarget.dataset.styleMode;
                if (this.styleModeInput) {
                    this.styleModeInput.value = mode;
                }
                this.updatePreviews();
            });
        });

        // Preset change
        if (this.presetSelect) {
            this.presetSelect.addEventListener('change', () => this.updatePreviews());
        }

        // Custom style changes
        [this.styleSelect, this.compositionSelect, this.paletteSelect, this.extraInput].forEach(el => {
            if (el) {
                el.addEventListener('change', () => this.updatePreviews());
                el.addEventListener('input', () => this.updatePreviews());
            }
        });

        // Generate subject button
        if (this.generateSubjectBtn) {
            this.generateSubjectBtn.addEventListener('click', () => this.generateSubject());
        }

        // Regenerate button
        if (this.regenerateBtn) {
            this.regenerateBtn.addEventListener('click', () => this.regenerateImage());
        }

        // Use new image button
        if (this.useNewBtn) {
            this.useNewBtn.addEventListener('click', () => this.useNewImage());
        }

        // History item clicks
        this.modalEl.querySelectorAll('.xmon-ai-history-item img').forEach(img => {
            img.addEventListener('click', (e) => {
                const item = e.currentTarget.closest('.xmon-ai-history-item');
                if (this.subjectInput) {
                    this.subjectInput.value = e.currentTarget.dataset.subject || '';
                }
            });
        });

        // History use buttons
        this.modalEl.querySelectorAll('.xmon-ai-use-history-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.useHistoryImage(e.currentTarget));
        });

        // History delete buttons
        this.modalEl.querySelectorAll('.xmon-ai-delete-history-btn').forEach(btn => {
            btn.addEventListener('click', (e) => this.deleteHistoryImage(e.currentTarget));
        });
    }

    updatePreviews() {
        const mode = this.styleModeInput?.value || 'global';

        // Update global preview
        if (this.globalPreview) {
            this.globalPreview.textContent = this.globalStyle || '(no default style)';
        }

        // Update preset preview
        if (this.presetPreview && this.presetSelect) {
            const presetKey = this.presetSelect.value;
            if (presetKey && this.presetsData[presetKey]) {
                this.presetPreview.textContent = this.presetsData[presetKey].preview || '';
            } else {
                this.presetPreview.textContent = '';
            }
        }

        // Update custom preview
        if (this.customPreview) {
            this.customPreview.textContent = this.buildCustomStyle();
        }
    }

    buildCustomStyle() {
        const parts = [];

        if (this.styleSelect?.value && this.stylesData[this.styleSelect.value]) {
            parts.push(this.stylesData[this.styleSelect.value].prompt);
        }

        if (this.compositionSelect?.value && this.compositionsData[this.compositionSelect.value]) {
            parts.push(this.compositionsData[this.compositionSelect.value].prompt);
        }

        if (this.paletteSelect?.value && this.palettesData[this.paletteSelect.value]) {
            parts.push(this.palettesData[this.paletteSelect.value].prompt);
        }

        if (this.extraInput?.value?.trim()) {
            parts.push(this.extraInput.value.trim());
        }

        return parts.join(', ');
    }

    async generateSubject() {
        if (!this.generateSubjectBtn) return;

        const endpoint = this.generateSubjectBtn.dataset.endpoint;
        if (!endpoint) return;

        this.setButtonLoading(this.generateSubjectBtn, true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = await response.json();

            if (data.success && this.subjectInput) {
                this.subjectInput.value = data.prompt || '';
            } else {
                this.showError(data.error || 'Failed to generate subject');
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        } finally {
            this.setButtonLoading(this.generateSubjectBtn, false);
        }
    }

    async regenerateImage() {
        if (!this.regenerateBtn) return;

        const endpoint = this.regenerateBtn.dataset.endpoint;
        if (!endpoint) return;

        const subject = this.subjectInput?.value?.trim();
        if (!subject) {
            this.showError('Please enter an image description');
            return;
        }

        this.setButtonLoading(this.regenerateBtn, true);
        this.startTimer();

        try {
            const formData = new FormData();
            formData.append('prompt', subject);
            formData.append('styleMode', this.styleModeInput?.value || 'global');

            // Add style-specific data
            if (this.styleModeInput?.value === 'preset' && this.presetSelect?.value) {
                formData.append('stylePreset', this.presetSelect.value);
            } else if (this.styleModeInput?.value === 'custom') {
                if (this.styleSelect?.value) formData.append('styleStyle', this.styleSelect.value);
                if (this.compositionSelect?.value) formData.append('styleComposition', this.compositionSelect.value);
                if (this.paletteSelect?.value) formData.append('stylePalette', this.paletteSelect.value);
                if (this.extraInput?.value) formData.append('styleExtra', this.extraInput.value);
            }

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData,
            });

            const data = await response.json();

            if (data.success) {
                this.lastGeneratedResult = data;
                this.showNewImage(data.imageUrl);
            } else {
                this.showError(data.error || 'Failed to generate image');
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        } finally {
            this.setButtonLoading(this.regenerateBtn, false);
            this.stopTimer();
        }
    }

    showNewImage(url) {
        if (this.newPreview) {
            this.newPreview.src = url;
            this.newPreview.classList.remove('d-none');
        }
        if (this.newPlaceholder) {
            this.newPlaceholder.classList.add('d-none');
        }
        if (this.newActions) {
            this.newActions.classList.remove('d-none');
        }
    }

    useNewImage() {
        if (!this.lastGeneratedResult) return;

        // Reload the page to reflect changes
        window.location.reload();
    }

    async useHistoryImage(button) {
        const endpoint = button.dataset.endpoint;
        if (!endpoint) return;

        this.setButtonLoading(button, true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = await response.json();

            if (data.success) {
                window.location.reload();
            } else {
                this.showError(data.error || 'Failed to use image');
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        } finally {
            this.setButtonLoading(button, false);
        }
    }

    async deleteHistoryImage(button) {
        if (!confirm('Are you sure you want to delete this image from history?')) return;

        const endpoint = button.dataset.endpoint;
        if (!endpoint) return;

        const item = button.closest('.xmon-ai-history-item');
        this.setButtonLoading(button, true);

        try {
            const response = await fetch(endpoint, {
                method: 'DELETE',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });

            const data = await response.json();

            if (data.success) {
                item?.remove();
            } else {
                this.showError(data.error || 'Failed to delete image');
            }
        } catch (error) {
            this.showError('Network error: ' + error.message);
        } finally {
            this.setButtonLoading(button, false);
        }
    }

    setButtonLoading(button, loading) {
        const spinner = button.querySelector('.spinner-border');
        if (loading) {
            button.disabled = true;
            spinner?.classList.remove('d-none');
        } else {
            button.disabled = false;
            spinner?.classList.add('d-none');
        }
    }

    startTimer() {
        this.timerStart = Date.now();
        if (this.timerEl) {
            this.timerEl.classList.remove('d-none');
        }
        this.timerInterval = setInterval(() => {
            const elapsed = Math.floor((Date.now() - this.timerStart) / 1000);
            if (this.timerValueEl) {
                this.timerValueEl.textContent = elapsed;
            }
        }, 1000);
    }

    stopTimer() {
        if (this.timerInterval) {
            clearInterval(this.timerInterval);
            this.timerInterval = null;
        }
        if (this.timerEl) {
            this.timerEl.classList.add('d-none');
        }
    }

    showError(message) {
        alert(message); // Simple for now, could be improved with toast notifications
    }
}

/**
 * AI Text Field Handler
 *
 * Handles the "Generate with AI" button for text fields.
 */
class XmonAiTextField {
    constructor(container) {
        this.container = container;
        this.textarea = container.querySelector('textarea');
        this.button = container.querySelector('.xmon-ai-generate-btn');

        if (this.button) {
            this.button.addEventListener('click', () => this.generate());
        }
    }

    async generate() {
        if (!this.button) return;

        const endpoint = this.button.dataset.endpoint;
        const method = this.button.dataset.method || 'POST';
        const confirmOverwrite = this.button.dataset.confirmOverwrite === 'true';
        const confirmMessage = this.button.dataset.confirmMessage || 'Replace current content?';

        if (!endpoint) return;

        // Confirm if there's existing content
        if (confirmOverwrite && this.textarea?.value?.trim() && !confirm(confirmMessage)) {
            return;
        }

        this.setLoading(true);

        try {
            // Gather source field values
            const sourceFields = JSON.parse(this.button.dataset.sourceFields || '[]');
            const extraData = JSON.parse(this.button.dataset.extraData || '{}');

            const formData = new FormData();

            // Add source field values
            sourceFields.forEach(fieldName => {
                const input = document.querySelector(`[name*="${fieldName}"]`);
                if (input) {
                    formData.append(fieldName, input.value);
                }
            });

            // Add extra data
            Object.entries(extraData).forEach(([key, value]) => {
                formData.append(key, value);
            });

            const response = await fetch(endpoint, {
                method: method,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: method === 'GET' ? undefined : formData,
            });

            const data = await response.json();

            if (data.success && this.textarea) {
                this.textarea.value = data.prompt || data.text || '';
            } else {
                alert(data.error || 'Failed to generate text');
            }
        } catch (error) {
            alert('Network error: ' + error.message);
        } finally {
            this.setLoading(false);
        }
    }

    setLoading(loading) {
        const spinner = this.button.querySelector('.xmon-ai-spinner');
        const text = this.button.querySelector('.xmon-ai-btn-text');

        if (loading) {
            this.button.disabled = true;
            spinner?.classList.remove('d-none');
        } else {
            this.button.disabled = false;
            spinner?.classList.add('d-none');
        }
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize AI text fields
    document.querySelectorAll('[data-ai-text-field]').forEach(container => {
        new XmonAiTextField(container);
    });

    // Initialize AI image modals
    document.querySelectorAll('.xmon-ai-image-modal').forEach(modal => {
        new XmonAiImageRegenerator(modal);
    });
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { XmonAiImageRegenerator, XmonAiTextField };
}
