/**
 * Selection Module - Checkbox handling and cross-page state
 */
import { state } from './state.js';

export function handleCheckboxChange(checkbox) {
    const id = Number(checkbox.value);

    if (checkbox.checked) {
        state.selectedIds.add(id);
    } else {
        state.selectedIds.delete(id);
    }

    updateBulkDeleteButton();
    updateSelectAllState();
}

export function toggleSelectAll(checked) {
    const checkboxes = document.querySelectorAll('.category-checkbox');

    checkboxes.forEach((checkbox) => {
        const id = Number(checkbox.value);
        checkbox.checked = checked;

        if (checked) {
            state.selectedIds.add(id);
        } else {
            state.selectedIds.delete(id);
        }
    });

    updateBulkDeleteButton();
    updateSelectAllState();
}

export function restoreSelectedCheckboxes() {
    const checkboxes = document.querySelectorAll('.category-checkbox');

    checkboxes.forEach((checkbox) => {
        const id = Number(checkbox.value);
        checkbox.checked = state.selectedIds.has(id);
    });

    updateSelectAllState();
}

export function updateBulkDeleteButton() {
    const bulkDeleteButton = document.querySelector('#bulk-delete-btn');
    if (!bulkDeleteButton) return;

    const count = state.selectedIds.size;
    bulkDeleteButton.disabled = count === 0;
    bulkDeleteButton.textContent = count > 0 ? `Delete Selected (${count})` : 'Delete Selected';
}

export function updateSelectAllState() {
    const selectAll = document.querySelector('#select-all');
    if (!selectAll) return;

    const checkboxes = Array.from(document.querySelectorAll('.category-checkbox'));

    if (checkboxes.length === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;
        return;
    }

    const checkedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

    selectAll.checked = checkedCount === checkboxes.length;
    selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
}

export function getSelectedIds() {
    return Array.from(state.selectedIds);
}

export function clearSelections() {
    state.selectedIds.clear();
    updateBulkDeleteButton();
    updateSelectAllState();
}
