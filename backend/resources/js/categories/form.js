/**
 * Form & Modal Module - Create / Edit Modals and Submissions
 */
import { state } from './state.js';
import {
    getCategory,
    createCategory as apiCreateCategory,
    updateCategory as apiUpdateCategory,
    getParentCategories,
} from './api.js';
import { reloadCategories } from './table.js';
import { reloadTree } from './tree.js';
import {
    setButtonLoading,
    showSuccess,
    showError,
    clearValidationErrors,
    handleValidationErrors,
} from './utils.js';

export function openModal() {
    const modal = document.querySelector('#category-modal');
    modal?.classList.remove('hidden');
    document.body.classList.add('overflow-hidden');
}

export function closeModal() {
    const modal = document.querySelector('#category-modal');
    modal?.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
    resetForm();
}

export function resetForm() {
    const form = document.querySelector('#category-form');
    const categoryIdInput = document.querySelector('#category-id');
    const modalTitle = document.querySelector('#modal-title');
    const saveButton = document.querySelector('#save-category-btn');

    form?.reset();
    if (categoryIdInput) categoryIdInput.value = '';
    state.editingId = null;

    if (modalTitle) modalTitle.textContent = 'Add Category';
    if (saveButton) saveButton.textContent = 'Save';

    clearValidationErrors();
}

export async function loadParentCategories(categoryId = null, selectedParentId = null) {
    const categoryParentSelect = document.querySelector('#category-parent');
    if (!categoryParentSelect) return;

    try {
        const response = await getParentCategories(categoryId);
        renderParentOptions(response.data, selectedParentId);
    } catch (error) {
        showError(error?.message || 'Unable to load parent categories.');
    }
}

export function renderParentOptions(categories, selectedParentId = null) {
    const categoryParentSelect = document.querySelector('#category-parent');
    if (!categoryParentSelect) return;

    categoryParentSelect.innerHTML = '';

    const rootOption = document.createElement('option');
    rootOption.value = '';
    rootOption.textContent = 'No Parent';
    categoryParentSelect.appendChild(rootOption);

    categories.forEach((category) => {
        const option = document.createElement('option');
        option.value = category.id;
        option.textContent = category.name;

        if (selectedParentId !== null && Number(selectedParentId) === Number(category.id)) {
            option.selected = true;
        }

        categoryParentSelect.appendChild(option);
    });
}

export async function openCreateModal() {
    resetForm();
    const modalTitle = document.querySelector('#modal-title');
    const saveButton = document.querySelector('#save-category-btn');

    if (modalTitle) modalTitle.textContent = 'Add Category';
    if (saveButton) saveButton.textContent = 'Save';

    await loadParentCategories();
    openModal();
}

export async function openEditModal(id) {
    const saveButton = document.querySelector('#save-category-btn');
    const modalTitle = document.querySelector('#modal-title');
    const categoryIdInput = document.querySelector('#category-id');
    const categoryNameInput = document.querySelector('#category-name');

    try {
        clearValidationErrors();
        if (modalTitle) modalTitle.textContent = 'Edit Category';
        if (saveButton) saveButton.textContent = 'Update';

        setButtonLoading(saveButton, true, 'Loading...');

        const response = await getCategory(id);
        const category = response.data;

        state.editingId = Number(category.id);
        if (categoryIdInput) categoryIdInput.value = category.id;
        if (categoryNameInput) categoryNameInput.value = category.name;

        const parentId = category.parent_id ?? category.parent?.id ?? null;

        await loadParentCategories(category.id, parentId);
        openModal();
    } catch (error) {
        showError(error?.message || 'Unable to load category.');
    } finally {
        setButtonLoading(saveButton, false);
        if (saveButton) saveButton.textContent = 'Update';
    }
}

export async function createCategory() {
    const categoryNameInput = document.querySelector('#category-name');
    const categoryParentSelect = document.querySelector('#category-parent');
    const saveButton = document.querySelector('#save-category-btn');

    const name = categoryNameInput?.value.trim() || '';
    const parentId = categoryParentSelect?.value || null;

    try {
        setButtonLoading(saveButton, true, 'Saving...');

        const response = await apiCreateCategory({
            name,
            parent_id: parentId || null,
        });

        showSuccess(response.message);
        closeModal();

        await reloadCategories(state.currentPage);
        await reloadTree();
    } catch (error) {
        handleValidationErrors(error);
    } finally {
        setButtonLoading(saveButton, false);
    }
}

export async function updateCategory() {
    const id = state.editingId;
    const categoryNameInput = document.querySelector('#category-name');
    const categoryParentSelect = document.querySelector('#category-parent');
    const saveButton = document.querySelector('#save-category-btn');

    if (!id) return;

    const name = categoryNameInput?.value.trim() || '';
    const parentId = categoryParentSelect?.value || null;

    try {
        setButtonLoading(saveButton, true, 'Updating...');

        const response = await apiUpdateCategory(id, {
            name,
            parent_id: parentId || null,
        });

        showSuccess(response.message);
        closeModal();

        await reloadCategories(state.currentPage);
        await reloadTree();
    } catch (error) {
        handleValidationErrors(error);
    } finally {
        setButtonLoading(saveButton, false);
    }
}

export async function handleFormSubmit(event) {
    event.preventDefault();
    clearValidationErrors();

    if (state.editingId) {
        await updateCategory();
        return;
    }

    await createCategory();
}
