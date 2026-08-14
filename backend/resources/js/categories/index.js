/**
 * Main Category Entry Point & Orchestrator
 */
import { state } from './state.js';
import { deleteCategory as apiDeleteCategory, bulkDeleteCategories } from './api.js';
import {
    handleCheckboxChange,
    toggleSelectAll,
    restoreSelectedCheckboxes,
    updateBulkDeleteButton,
    getSelectedIds,
    clearSelections,
} from './selection.js';
import { reloadCategories, handlePagination } from './table.js';
import { reloadTree } from './tree.js';
import { handleSearch, handleCategoryFilter, resetFilters } from './filters.js';
import { openCreateModal, openEditModal, closeModal, handleFormSubmit } from './form.js';
import { setButtonLoading, showSuccess, showError } from './utils.js';

let pendingDeleteAction = null;

export function openDeleteModal({ title, message, onConfirm }) {
    const modal = document.querySelector('#delete-modal');
    const titleEl = document.querySelector('#delete-modal-title');
    const messageEl = document.querySelector('#delete-modal-message');

    if (!modal) return;

    if (titleEl) titleEl.textContent = title || 'Delete Category';
    if (messageEl) messageEl.textContent = message || 'Are you sure you want to delete this?';

    pendingDeleteAction = onConfirm;
    modal.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

export function closeDeleteModal() {
    const modal = document.querySelector('#delete-modal');
    modal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    pendingDeleteAction = null;
}

export async function deleteSingleCategory(id) {
    try {
        const response = await apiDeleteCategory(id);
        showSuccess(response.message || 'Category deleted successfully.');

        state.selectedIds.delete(Number(id));
        updateBulkDeleteButton();

        await reloadCategories(state.currentPage);
        await reloadTree();
    } catch (error) {
        showError(error?.message || 'Unable to delete category.');
    }
}

export async function performBulkDelete() {
    const selectedIds = getSelectedIds();
    if (selectedIds.length === 0) {
        showError('Please select at least one category to delete.');
        return;
    }

    const bulkDeleteButton = document.querySelector('#bulk-delete-btn');

    try {
        setButtonLoading(bulkDeleteButton, true, 'Deleting...');

        const response = await bulkDeleteCategories(selectedIds);
        showSuccess(response.message || 'Categories deleted successfully.');

        clearSelections();

        await reloadCategories(state.currentPage);
        await reloadTree();
    } catch (error) {
        showError(error?.message || 'Unable to delete categories.');
    } finally {
        setButtonLoading(bulkDeleteButton, false);
        updateBulkDeleteButton();
    }
}

function initCategoryManagement() {
    // 1. Add Category Button
    document.querySelector('#add-category-btn')?.addEventListener('click', openCreateModal);

    // 2. Form Modal Close Buttons
    document.querySelector('#close-modal')?.addEventListener('click', closeModal);
    document.querySelector('#cancel-modal')?.addEventListener('click', closeModal);

    // 3. Delete Modal Buttons
    document.querySelector('#cancel-delete-btn')?.addEventListener('click', closeDeleteModal);
    document.querySelector('#confirm-delete-btn')?.addEventListener('click', async () => {
        if (typeof pendingDeleteAction === 'function') {
            const action = pendingDeleteAction;
            const confirmBtn = document.querySelector('#confirm-delete-btn');
            try {
                setButtonLoading(confirmBtn, true, 'Deleting...');
                await action();
                closeDeleteModal();
            } finally {
                setButtonLoading(confirmBtn, false, 'Delete Category');
            }
        }
    });

    // 4. Form Submit
    document.querySelector('#category-form')?.addEventListener('submit', handleFormSubmit);

    // 5. Search Filter
    document.querySelector('#search')?.addEventListener('input', handleSearch);

    // 6. Category Dropdown Filter
    document.querySelector('#category-filter')?.addEventListener('change', handleCategoryFilter);

    // 7. Reset Filters Button
    document.querySelector('#reset-filters')?.addEventListener('click', resetFilters);

    // 8. Bulk Delete Button
    document.querySelector('#bulk-delete-btn')?.addEventListener('click', () => {
        const selectedIds = getSelectedIds();
        if (selectedIds.length === 0) {
            showError('Please select at least one category to delete.');
            return;
        }

        const count = selectedIds.length;
        openDeleteModal({
            title: 'Delete Selected Categories',
            message: `Are you sure you want to delete ${count} selected categor${count === 1 ? 'y' : 'ies'}? This action cannot be undone.`,
            onConfirm: performBulkDelete,
        });
    });

    // 9. Event Delegation for Edit, Delete, Pagination, and Tree Toggle
    document.addEventListener('click', async (event) => {
        const editButton = event.target.closest('.edit-category');
        if (editButton) {
            const id = Number(editButton.dataset.id);
            await openEditModal(id);
            return;
        }

        const deleteButton = event.target.closest('.delete-category');
        if (deleteButton) {
            const id = Number(deleteButton.dataset.id);
            const name = deleteButton.dataset.name || 'this category';
            openDeleteModal({
                title: 'Delete Category',
                message: `Are you sure you want to delete "${name}"? This action cannot be undone.`,
                onConfirm: () => deleteSingleCategory(id),
            });
            return;
        }

        const paginationLink = event.target.closest('.pagination-link');
        if (paginationLink) {
            event.preventDefault();
            await handlePagination(paginationLink);
            return;
        }

        const treeToggle = event.target.closest('.tree-toggle');
        if (treeToggle) {
            const node = treeToggle.closest('.tree-node');
            const childrenContainer = node?.querySelector(':scope > .tree-children');
            const chevron = treeToggle.querySelector('.tree-chevron');

            if (childrenContainer) {
                childrenContainer.classList.toggle('hidden');
                chevron?.classList.toggle('rotate-90');
            }
            return;
        }
    });

    // 10. Event Delegation for Checkboxes
    document.addEventListener('change', (event) => {
        if (event.target.matches('.category-checkbox')) {
            handleCheckboxChange(event.target);
            return;
        }

        if (event.target.matches('#select-all')) {
            toggleSelectAll(event.target.checked);
        }
    });

    // 11. Close Modals when clicking outside
    const categoryModal = document.querySelector('#category-modal');
    categoryModal?.addEventListener('click', (event) => {
        if (event.target === categoryModal) {
            closeModal();
        }
    });

    const deleteModal = document.querySelector('#delete-modal');
    deleteModal?.addEventListener('click', (event) => {
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });

    // 12. Close Modals with Escape Key
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            if (categoryModal && !categoryModal.classList.contains('hidden')) {
                closeModal();
            }
            if (deleteModal && !deleteModal.classList.contains('hidden')) {
                closeDeleteModal();
            }
        }
    });

    // Initial state setup
    restoreSelectedCheckboxes();
    updateBulkDeleteButton();
}

// Auto init when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCategoryManagement);
} else {
    initCategoryManagement();
}
