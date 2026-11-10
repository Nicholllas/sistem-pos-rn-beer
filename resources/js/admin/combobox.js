/**
 * Material UI Searchable Combobox / Autocomplete
 *
 * Replaces standard <select class="crud-form-control"> with a searchable
 * text-to-filter input while keeping the native select in sync for form submissions,
 * validations, and reactive event listeners.
 */

function escapeHtml(text) {
    return String(text ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function escapeRegex(string) {
    return String(string ?? "").replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
}

const CHEVRON_SVG = `
<svg viewBox="0 0 24 24" aria-hidden="true" class="mui-combobox-arrow">
    <path d="M7 10l5 5 5-5z"></path>
</svg>
`;

const CLEAR_SVG = `
<svg viewBox="0 0 24 24" aria-hidden="true" class="mui-combobox-clear-icon">
    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"></path>
</svg>
`;

const CHECK_SVG = `
<svg viewBox="0 0 24 24" aria-hidden="true" class="mui-combobox-check">
    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"></path>
</svg>
`;

export function initMuiCombobox(selectElement) {
    if (!selectElement || selectElement.dataset.comboboxInitialized) {
        return;
    }

    if (selectElement.dataset.noCombobox !== undefined) {
        return;
    }

    selectElement.dataset.comboboxInitialized = "true";

    // Create wrapper
    const wrapper = document.createElement("div");
    wrapper.className = "mui-combobox";
    if (selectElement.classList.contains("is-invalid")) {
        wrapper.classList.add("is-invalid");
    }

    // Insert wrapper before select
    selectElement.parentNode.insertBefore(wrapper, selectElement);

    // Hide native select visually while keeping in DOM
    selectElement.classList.add("mui-combobox-native-hidden");
    selectElement.setAttribute("tabindex", "-1");
    selectElement.setAttribute("aria-hidden", "true");

    // Handle HTML5 required constraint validation safely
    if (selectElement.required) {
        selectElement.dataset.required = "true";
        selectElement.required = false;
    }

    // Move select inside wrapper
    wrapper.appendChild(selectElement);

    // Create visible input
    const input = document.createElement("input");
    input.type = "text";
    input.className = "form-control crud-form-control mui-combobox-input";
    if (selectElement.classList.contains("is-invalid")) {
        input.classList.add("is-invalid");
    }
    input.autocomplete = "off";
    input.spellcheck = false;
    input.setAttribute("role", "combobox");
    input.setAttribute("aria-expanded", "false");
    input.setAttribute("aria-autocomplete", "list");
    if (selectElement.id) {
        input.setAttribute("aria-controls", `dropdown_${selectElement.id}`);
        input.dataset.comboboxFor = selectElement.id;
    }
    if (selectElement.disabled) {
        input.disabled = true;
    }
    wrapper.appendChild(input);

    // Actions container (Clear + Toggle buttons)
    const actions = document.createElement("div");
    actions.className = "mui-combobox-actions";

    const clearButton = document.createElement("button");
    clearButton.type = "button";
    clearButton.className = "mui-combobox-clear";
    clearButton.setAttribute("aria-label", "Hapus pilihan");
    clearButton.setAttribute("tabindex", "-1");
    clearButton.innerHTML = CLEAR_SVG;
    clearButton.style.display = "none";
    actions.appendChild(clearButton);

    const toggleButton = document.createElement("button");
    toggleButton.type = "button";
    toggleButton.className = "mui-combobox-toggle";
    toggleButton.setAttribute("aria-label", "Buka pilihan");
    toggleButton.setAttribute("tabindex", "-1");
    toggleButton.innerHTML = CHEVRON_SVG;
    actions.appendChild(toggleButton);

    wrapper.appendChild(actions);

    // Dropdown list
    const dropdown = document.createElement("ul");
    dropdown.className = "mui-combobox-dropdown";
    dropdown.setAttribute("role", "listbox");
    if (selectElement.id) {
        dropdown.id = `dropdown_${selectElement.id}`;
    }
    wrapper.appendChild(dropdown);

    let focusedIndex = -1;
    let visibleItems = [];
    let preventNextMouseUp = false;

    // Prevent input blur when clicking inside dropdown or action buttons
    dropdown.addEventListener("mousedown", (e) => {
        e.preventDefault();
    });
    actions.addEventListener("mousedown", (e) => {
        e.preventDefault();
    });

    // Helper: Build option items from native <select>
    function renderItems() {
        dropdown.innerHTML = "";
        const options = Array.from(selectElement.options);

        options.forEach((opt) => {
            const li = document.createElement("li");
            li.className = "mui-combobox-item";
            li.setAttribute("role", "option");
            li.dataset.value = opt.value;
            li.dataset.text = opt.text.trim();

            if (opt.value === "") {
                li.classList.add("is-placeholder");
            }

            if (opt.selected && opt.value !== "") {
                li.classList.add("is-selected");
                li.setAttribute("aria-selected", "true");
            } else {
                li.setAttribute("aria-selected", "false");
            }

            const labelSpan = document.createElement("span");
            labelSpan.className = "mui-combobox-label";
            labelSpan.textContent = opt.text.trim();
            li.appendChild(labelSpan);

            li.insertAdjacentHTML("beforeend", CHECK_SVG);

            li.addEventListener("click", (e) => {
                e.stopPropagation();
                selectValue(opt.value, opt.text.trim());
                closeDropdown();
                input.focus();
            });

            dropdown.appendChild(li);
        });

        // Empty message element
        const emptyLi = document.createElement("li");
        emptyLi.className = "mui-combobox-empty";
        emptyLi.textContent = "Tidak ada pilihan yang cocok";
        emptyLi.style.display = "none";
        dropdown.appendChild(emptyLi);
    }

    // Update the input display text and clear button based on selected option
    function syncFromNativeSelect() {
        const selectedOption = selectElement.selectedOptions[0] || selectElement.options[0];
        const placeholderOption = Array.from(selectElement.options).find((opt) => opt.value === "");

        if (selectedOption && selectedOption.value !== "") {
            input.value = selectedOption.text.trim();
            clearButton.style.display = "";
            input.required = false;
        } else {
            input.value = "";
            input.placeholder = placeholderOption ? placeholderOption.text.trim() : "Pilih opsi...";
            clearButton.style.display = "none";
            if (selectElement.dataset.required === "true") {
                input.required = true;
            }
        }

        // Highlight selected in dropdown
        Array.from(dropdown.querySelectorAll(".mui-combobox-item")).forEach((li) => {
            const isSelected = li.dataset.value === (selectedOption ? selectedOption.value : "");
            li.classList.toggle("is-selected", isSelected);
            li.setAttribute("aria-selected", isSelected ? "true" : "false");
        });
    }

    // Select an option programmatically
    function selectValue(val, text) {
        if (selectElement.value !== val) {
            selectElement.value = val;
            selectElement.dispatchEvent(new Event("input", { bubbles: true }));
            selectElement.dispatchEvent(new Event("change", { bubbles: true }));
        }
        syncFromNativeSelect();
    }

    // Filter dropdown items based on query
    function filterDropdown(query) {
        const cleanQuery = String(query ?? "").trim().toLowerCase();
        const items = Array.from(dropdown.querySelectorAll(".mui-combobox-item"));
        const emptyEl = dropdown.querySelector(".mui-combobox-empty");

        visibleItems = [];
        let matchCount = 0;

        items.forEach((item) => {
            const rawText = item.dataset.text || "";
            const isPlaceholder = item.classList.contains("is-placeholder");
            const labelSpan = item.querySelector(".mui-combobox-label");

            if (!cleanQuery) {
                // Show all
                item.style.display = "";
                if (labelSpan) {
                    labelSpan.textContent = rawText;
                }
                visibleItems.push(item);
                matchCount++;
            } else {
                // When actively searching, hide placeholder options
                if (isPlaceholder) {
                    item.style.display = "none";
                    return;
                }

                const lowerText = rawText.toLowerCase();
                const matchIndex = lowerText.indexOf(cleanQuery);

                if (matchIndex !== -1) {
                    item.style.display = "";
                    if (labelSpan) {
                        const regex = new RegExp(`(${escapeRegex(cleanQuery)})`, "gi");
                        labelSpan.innerHTML = escapeHtml(rawText).replace(
                            regex,
                            '<mark class="mui-combobox-highlight">$1</mark>',
                        );
                    }
                    visibleItems.push(item);
                    matchCount++;
                } else {
                    item.style.display = "none";
                    if (labelSpan) {
                        labelSpan.textContent = rawText;
                    }
                }
            }
        });

        if (emptyEl) {
            emptyEl.style.display = matchCount === 0 ? "block" : "none";
        }

        // Reset focused item to first matching visible item
        focusedIndex = visibleItems.length > 0 ? 0 : -1;
        updateFocusedItem();
    }

    function updateFocusedItem() {
        Array.from(dropdown.querySelectorAll(".mui-combobox-item")).forEach((item) => {
            item.classList.remove("is-focused");
        });

        if (focusedIndex >= 0 && focusedIndex < visibleItems.length) {
            const target = visibleItems[focusedIndex];
            target.classList.add("is-focused");
            target.scrollIntoView({ block: "nearest" });
        }
    }

    function openDropdown(forceShowAll = false) {
        if (input.disabled) {
            return;
        }

        // Calculate dropup if near screen bottom
        const rect = wrapper.getBoundingClientRect();
        const spaceBelow = window.innerHeight - rect.bottom;
        const dropdownHeight = 260;

        if (spaceBelow < dropdownHeight && rect.top > dropdownHeight) {
            wrapper.classList.add("is-dropup");
        } else {
            wrapper.classList.remove("is-dropup");
        }

        wrapper.classList.add("is-open");
        input.setAttribute("aria-expanded", "true");

        // Elevate parent panel so dropdown floats over subsequent siblings (e.g. results tables)
        const parentPanel = wrapper.closest(".crud-panel, .crud-form-panel");
        if (parentPanel) {
            parentPanel.classList.add("has-combobox-open");
        }

        // If forceShowAll or input value matches current selection, show all options
        const selectedOption = selectElement.selectedOptions[0];
        const isCurrentSelection =
            selectedOption &&
            input.value.trim().toLowerCase() === selectedOption.text.trim().toLowerCase();

        if (forceShowAll || isCurrentSelection || !input.value.trim()) {
            filterDropdown("");
        } else {
            filterDropdown(input.value);
        }

        // Scroll selected item into view if visible
        const selectedLi = dropdown.querySelector(".mui-combobox-item.is-selected");
        if (selectedLi && selectedLi.style.display !== "none") {
            selectedLi.scrollIntoView({ block: "nearest" });
            const selIdx = visibleItems.indexOf(selectedLi);
            if (selIdx !== -1) {
                focusedIndex = selIdx;
                updateFocusedItem();
            }
        }
    }

    function closeDropdown() {
        wrapper.classList.remove("is-open");
        wrapper.classList.remove("is-dropup");
        input.setAttribute("aria-expanded", "false");
        focusedIndex = -1;
        updateFocusedItem();

        // Restore parent panel elevation
        const parentPanel = wrapper.closest(".crud-panel, .crud-form-panel");
        if (parentPanel && !parentPanel.querySelector(".mui-combobox.is-open")) {
            parentPanel.classList.remove("has-combobox-open");
        }
    }

    // Input Events
    input.addEventListener("focus", () => {
        openDropdown(true);
        input.select();
        preventNextMouseUp = true;
    });

    input.addEventListener("mouseup", (e) => {
        if (preventNextMouseUp) {
            e.preventDefault();
            preventNextMouseUp = false;
        }
    });

    input.addEventListener("click", () => {
        if (!wrapper.classList.contains("is-open")) {
            openDropdown(true);
            input.select();
        }
    });

    input.addEventListener("input", () => {
        if (!wrapper.classList.contains("is-open")) {
            wrapper.classList.add("is-open");
            input.setAttribute("aria-expanded", "true");
        }
        filterDropdown(input.value);
        clearButton.style.display = input.value ? "" : "none";
    });

    input.addEventListener("keydown", (e) => {
        const isOpen = wrapper.classList.contains("is-open");

        if (e.key === "ArrowDown") {
            e.preventDefault();
            if (!isOpen) {
                openDropdown(true);
                return;
            }
            if (visibleItems.length > 0) {
                focusedIndex = (focusedIndex + 1) % visibleItems.length;
                updateFocusedItem();
            }
        } else if (e.key === "ArrowUp") {
            e.preventDefault();
            if (!isOpen) {
                openDropdown(true);
                return;
            }
            if (visibleItems.length > 0) {
                focusedIndex = (focusedIndex - 1 + visibleItems.length) % visibleItems.length;
                updateFocusedItem();
            }
        } else if (e.key === "Enter") {
            if (isOpen) {
                e.preventDefault();
                if (focusedIndex >= 0 && focusedIndex < visibleItems.length) {
                    const chosen = visibleItems[focusedIndex];
                    selectValue(chosen.dataset.value, chosen.dataset.text);
                }
                closeDropdown();
            }
        } else if (e.key === "Escape") {
            if (isOpen) {
                e.preventDefault();
                closeDropdown();
                syncFromNativeSelect();
            }
        } else if (e.key === "Tab") {
            if (isOpen) {
                // If only 1 visible match or user hovered an item, auto-select on tab
                if (focusedIndex >= 0 && focusedIndex < visibleItems.length) {
                    const chosen = visibleItems[focusedIndex];
                    selectValue(chosen.dataset.value, chosen.dataset.text);
                }
                closeDropdown();
            }
        }
    });

    // Handle blur: validate / revert if typed text does not match
    input.addEventListener("blur", () => {
        setTimeout(() => {
            if (document.activeElement === input || wrapper.contains(document.activeElement)) {
                return;
            }

            if (wrapper.classList.contains("is-open")) {
                const currentText = input.value.trim().toLowerCase();

                if (!currentText) {
                    // Empty input: select placeholder if exists
                    const placeholderOpt = Array.from(selectElement.options).find((opt) => opt.value === "");
                    if (placeholderOpt) {
                        selectValue("", placeholderOpt.text.trim());
                    } else {
                        syncFromNativeSelect();
                    }
                } else {
                    // Check exact match (case-insensitive)
                    const matchingOpt = Array.from(selectElement.options).find(
                        (opt) => opt.value !== "" && opt.text.trim().toLowerCase() === currentText,
                    );

                    if (matchingOpt) {
                        selectValue(matchingOpt.value, matchingOpt.text.trim());
                    } else if (visibleItems.length === 1 && visibleItems[0].dataset.value !== "") {
                        // Exactly 1 match filtered
                        const onlyItem = visibleItems[0];
                        selectValue(onlyItem.dataset.value, onlyItem.dataset.text);
                    } else {
                        // Revert to previously selected option
                        syncFromNativeSelect();
                    }
                }

                closeDropdown();
            }
        }, 180);
    });

    // Click outside handler
    document.addEventListener("pointerdown", (e) => {
        if (!wrapper.contains(e.target)) {
            closeDropdown();
        }
    });

    // Toggle button click
    toggleButton.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        if (wrapper.classList.contains("is-open")) {
            closeDropdown();
        } else {
            input.focus();
            openDropdown(true);
            input.select();
        }
    });

    // Clear button click
    clearButton.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        const placeholderOpt = Array.from(selectElement.options).find((opt) => opt.value === "");
        selectValue("", placeholderOpt ? placeholderOpt.text.trim() : "");
        input.value = "";
        clearButton.style.display = "none";
        input.focus();
        openDropdown(true);
    });

    // Forward label click to input
    selectElement.addEventListener("focus", () => {
        input.focus();
    });

    // Listen to changes initiated on native select
    selectElement.addEventListener("change", () => {
        syncFromNativeSelect();
    });

    // Listen to form reset
    const form = selectElement.closest("form");
    if (form) {
        form.addEventListener("reset", () => {
            setTimeout(syncFromNativeSelect, 10);
        });

        // Validation intercept
        form.addEventListener("submit", (e) => {
            if (selectElement.dataset.required === "true" && !selectElement.value) {
                e.preventDefault();
                input.focus();
                input.reportValidity();
            }
        });
    }

    // Observer for dynamic options mutations
    const observer = new MutationObserver(() => {
        renderItems();
        syncFromNativeSelect();
    });
    observer.observe(selectElement, { childList: true, subtree: true });

    // Initial render
    renderItems();
    syncFromNativeSelect();
}

/**
 * Initialize all matching selects on the page
 */
export function initAllComboboxes(root = document) {
    const selects = root.querySelectorAll(
        "select.crud-form-control:not([data-no-combobox]), select.form-select.crud-form-control:not([data-no-combobox])",
    );

    selects.forEach((select) => {
        initMuiCombobox(select);
    });
}

// Global auto-init on DOMContentLoaded and dynamically added DOM elements
if (typeof document !== "undefined") {
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", () => {
            initAllComboboxes();
        });
    } else {
        initAllComboboxes();
    }

    // Watch for dynamically added form elements (e.g. purchase item rows or modals)
    const bodyObserver = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    if (node.matches && node.matches("select.crud-form-control:not([data-no-combobox])")) {
                        initMuiCombobox(node);
                    } else if (node.querySelectorAll) {
                        initAllComboboxes(node);
                    }
                }
            });
        });
    });

    bodyObserver.observe(document.body || document.documentElement, {
        childList: true,
        subtree: true,
    });
}
