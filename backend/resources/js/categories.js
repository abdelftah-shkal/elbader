/**
 * Category Management
 *
 * Responsibilities:
 * - AJAX CRUD
 * - Search
 * - Category filtering
 * - Native PHP pagination through AJAX
 * - Cross-page checkbox selection
 * - Bulk deletion
 * - Modal handling
 * - Validation errors
 */

const state = {
    selectedIds: new Set(),
    search: '',
    categoryId: '',
    currentPage: 1,
    editingId: null,
};

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');


/* =========================================================
   DOM Helpers
========================================================= */

const elements = {
    modal: document.querySelector('#category-modal'),
    modalTitle: document.querySelector('#modal-title'),
    form: document.querySelector('#category-form'),

    categoryId: document.querySelector('#category-id'),
    categoryName: document.querySelector('#category-name'),
    categoryParent: document.querySelector('#category-parent'),

    addButton: document.querySelector('#add-category-btn'),
    closeModalButton: document.querySelector('#close-modal'),
    cancelModalButton: document.querySelector('#cancel-modal'),

    saveButton: document.querySelector('#save-category-btn'),

    search: document.querySelector('#search'),
    categoryFilter: document.querySelector('#category-filter'),
    resetFilters: document.querySelector('#reset-filters'),

    bulkDeleteButton: document.querySelector('#bulk-delete-btn'),

    tableContainer: document.querySelector(
        '#categories-table-container'
    ),

    treeContainer: document.querySelector(
        '#category-tree-container'
    ),
};


/* =========================================================
   API Helper
========================================================= */

async function apiRequest(
    url,
    {
        method = 'GET',
        data = null,
        headers = {},
    } = {}
) {
    const options = {
        method,

        headers: {
            Accept: 'application/json',

            'X-CSRF-TOKEN': csrfToken,

            'X-Requested-With': 'XMLHttpRequest',

            ...headers,
        },
    };

    if (data !== null) {
        options.headers['Content-Type'] =
            'application/json';

        options.body = JSON.stringify(data);
    }

    const response = await fetch(
        url,
        options
    );

    let responseData;

    try {
        responseData = await response.json();
    } catch {
        responseData = {
            success: false,
            message: 'Invalid server response.',
        };
    }

    if (!response.ok) {
        throw responseData;
    }

    return responseData;
}


/* =========================================================
   Loading State
========================================================= */

function setButtonLoading(
    button,
    loading,
    loadingText = 'Loading...'
) {
    if (!button) {
        return;
    }

    if (loading) {
        button.dataset.originalText =
            button.textContent;

        button.disabled = true;

        button.textContent = loadingText;

        return;
    }

    button.disabled = false;

    button.textContent =
        button.dataset.originalText ||
        button.textContent;
}


/* =========================================================
   Notifications
========================================================= */

function showSuccess(message) {
    // Replace this with Toast/SweetAlert later.
    alert(message);
}


function showError(message) {
    alert(message);
}


/* =========================================================
   Validation Errors
========================================================= */

function clearValidationErrors() {
    const errors = document.querySelectorAll(
        '.validation-error'
    );

    errors.forEach((element) => {
        element.textContent = '';
        element.classList.add('hidden');
    });

    elements.categoryName?.classList.remove(
        'border-red-500'
    );

    elements.categoryParent?.classList.remove(
        'border-red-500'
    );
}


function showValidationError(
    field,
    message
) {
    const errorElement =
        document.querySelector(
            `#${field}-error`
        );

    if (errorElement) {
        errorElement.textContent = message;

        errorElement.classList.remove(
            'hidden'
        );
    }

    const inputElement =
        document.querySelector(
            `#category-${field}`
        );

    if (inputElement) {
        inputElement.classList.add(
            'border-red-500'
        );
    }
}


function handleValidationErrors(error) {
    clearValidationErrors();

    if (
        error?.errors &&
        typeof error.errors === 'object'
    ) {
        Object.entries(error.errors)
            .forEach(
                ([field, messages]) => {
                    showValidationError(
                        field,
                        messages[0]
                    );
                }
            );

        return;
    }

    showError(
        error?.message ||
        'Something went wrong.'
    );
}


/* =========================================================
   Modal
========================================================= */

function openModal() {
    elements.modal?.classList.remove(
        'hidden'
    );

    document.body.classList.add(
        'overflow-hidden'
    );
}


function closeModal() {
    elements.modal?.classList.add(
        'hidden'
    );

    document.body.classList.remove(
        'overflow-hidden'
    );

    resetForm();
}


function resetForm() {
    elements.form?.reset();

    elements.categoryId.value = '';

    state.editingId = null;

    elements.modalTitle.textContent =
        'Add Category';

    elements.saveButton.textContent =
        'Save';

    clearValidationErrors();
}


/* =========================================================
   Parent Categories
========================================================= */

async function loadParentCategories(
    categoryId = null,
    selectedParentId = null
) {
    let url = '/categories/parents';

    if (categoryId) {
        url += `/${categoryId}`;
    }

    try {
        const response = await apiRequest(
            url
        );

        renderParentOptions(
            response.data,
            selectedParentId
        );
    } catch (error) {
        showError(
            error?.message ||
            'Unable to load parent categories.'
        );
    }
}


function renderParentOptions(
    categories,
    selectedParentId = null
) {
    elements.categoryParent.innerHTML = '';

    const rootOption =
        document.createElement('option');

    rootOption.value = '';

    rootOption.textContent =
        'No Parent';

    elements.categoryParent.appendChild(
        rootOption
    );

    categories.forEach((category) => {
        const option =
            document.createElement('option');

        option.value = category.id;

        option.textContent =
            category.name;

        if (
            selectedParentId !== null &&
            Number(selectedParentId) ===
                Number(category.id)
        ) {
            option.selected = true;
        }

        elements.categoryParent.appendChild(
            option
        );
    });
}


/* =========================================================
   Add Category
========================================================= */

async function createCategory() {
    const name =
        elements.categoryName.value.trim();

    const parentId =
        elements.categoryParent.value;

    try {
        setButtonLoading(
            elements.saveButton,
            true,
            'Saving...'
        );

        const response =
            await apiRequest(
                '/categories',
                {
                    method: 'POST',

                    data: {
                        name,

                        parent_id:
                            parentId ||
                            null,
                    },
                }
            );

        showSuccess(
            response.message
        );

        closeModal();

        await reloadCategories(
            state.currentPage
        );

        await reloadTree();

    } catch (error) {
        handleValidationErrors(error);

    } finally {
        setButtonLoading(
            elements.saveButton,
            false
        );
    }
}


/* =========================================================
   Update Category
========================================================= */

async function updateCategory() {
    const id =
        state.editingId;

    const name =
        elements.categoryName.value.trim();

    const parentId =
        elements.categoryParent.value;

    if (!id) {
        return;
    }

    try {
        setButtonLoading(
            elements.saveButton,
            true,
            'Updating...'
        );

        const response =
            await apiRequest(
                `/categories/${id}`,
                {
                    method: 'PUT',

                    data: {
                        name,

                        parent_id:
                            parentId ||
                            null,
                    },
                }
            );

        showSuccess(
            response.message
        );

        closeModal();

        await reloadCategories(
            state.currentPage
        );

        await reloadTree();

    } catch (error) {
        handleValidationErrors(error);

    } finally {
        setButtonLoading(
            elements.saveButton,
            false
        );
    }
}


/* =========================================================
   Open Create Modal
========================================================= */

async function openCreateModal() {
    resetForm();

    elements.modalTitle.textContent =
        'Add Category';

    elements.saveButton.textContent =
        'Save';

    await loadParentCategories();

    openModal();
}


/* =========================================================
   Open Edit Modal
========================================================= */

async function openEditModal(id) {
    try {
        clearValidationErrors();

        elements.modalTitle.textContent =
            'Edit Category';

        elements.saveButton.textContent =
            'Update';

        setButtonLoading(
            elements.saveButton,
            true,
            'Loading...'
        );

        const response =
            await apiRequest(
                `/categories/${id}`
            );

        const category =
            response.data;

        state.editingId =
            Number(category.id);

        elements.categoryId.value =
            category.id;

        elements.categoryName.value =
            category.name;

        const parentId =
            category.parent_id ??
            category.parent?.id ??
            null;

        await loadParentCategories(
            category.id,
            parentId
        );

        openModal();

    } catch (error) {
        showError(
            error?.message ||
            'Unable to load category.'
        );

    } finally {
        setButtonLoading(
            elements.saveButton,
            false
        );

        elements.saveButton.textContent =
            'Update';
    }
}


/* =========================================================
   Single Delete
========================================================= */

async function deleteCategory(id) {
    const confirmed = confirm(
        'Are you sure you want to delete this category?'
    );

    if (!confirmed) {
        return;
    }

    try {
        const response =
            await apiRequest(
                `/categories/${id}`,
                {
                    method: 'DELETE',
                }
            );

        showSuccess(
            response.message
        );

        state.selectedIds.delete(
            Number(id)
        );

        updateBulkDeleteButton();

        await reloadCategories(
            state.currentPage
        );

        await reloadTree();

    } catch (error) {
        showError(
            error?.message ||
            'Unable to delete category.'
        );
    }
}


/* =========================================================
   Bulk Delete
========================================================= */

async function bulkDelete() {
    if (
        state.selectedIds.size === 0
    ) {
        showError(
            'Please select at least one category.'
        );

        return;
    }

    const count =
        state.selectedIds.size;

    const confirmed = confirm(
        `Are you sure you want to delete ${count} selected categor${count === 1 ? 'y' : 'ies'}?`
    );

    if (!confirmed) {
        return;
    }

    try {
        setButtonLoading(
            elements.bulkDeleteButton,
            true,
            'Deleting...'
        );

        const ids =
            Array.from(
                state.selectedIds
            );

        const response =
            await apiRequest(
                '/categories/bulk-delete',
                {
                    method: 'DELETE',

                    data: {
                        ids,
                    },
                }
            );

        showSuccess(
            response.message
        );

        state.selectedIds.clear();

        updateBulkDeleteButton();

        await reloadCategories(
            state.currentPage
        );

        await reloadTree();

    } catch (error) {
        showError(
            error?.message ||
            'Unable to delete categories.'
        );

    } finally {
        setButtonLoading(
            elements.bulkDeleteButton,
            false
        );

        updateBulkDeleteButton();
    }
}


/* =========================================================
   Checkbox Management
========================================================= */

function handleCheckboxChange(
    checkbox
) {
    const id =
        Number(checkbox.value);

    if (checkbox.checked) {
        state.selectedIds.add(id);
    } else {
        state.selectedIds.delete(id);
    }

    updateBulkDeleteButton();

    updateSelectAllState();
}


function restoreSelectedCheckboxes() {
    const checkboxes =
        document.querySelectorAll(
            '.category-checkbox'
        );

    checkboxes.forEach(
        (checkbox) => {
            const id =
                Number(checkbox.value);

            checkbox.checked =
                state.selectedIds.has(id);
        }
    );

    updateSelectAllState();
}


function updateBulkDeleteButton() {
    const count =
        state.selectedIds.size;

    elements.bulkDeleteButton.disabled =
        count === 0;

    elements.bulkDeleteButton.textContent =
        count > 0
            ? `Delete Selected (${count})`
            : 'Delete Selected';
}


function updateSelectAllState() {
    const selectAll =
        document.querySelector(
            '#select-all'
        );

    if (!selectAll) {
        return;
    }

    const checkboxes =
        Array.from(
            document.querySelectorAll(
                '.category-checkbox'
            )
        );

    if (checkboxes.length === 0) {
        selectAll.checked = false;
        selectAll.indeterminate = false;

        return;
    }

    const checkedCount =
        checkboxes.filter(
            (checkbox) =>
                checkbox.checked
        ).length;

    selectAll.checked =
        checkedCount ===
        checkboxes.length;

    selectAll.indeterminate =
        checkedCount > 0 &&
        checkedCount <
            checkboxes.length;
}


/* =========================================================
   Select All - Current Page Only
========================================================= */

function toggleSelectAll(
    checked
) {
    const checkboxes =
        document.querySelectorAll(
            '.category-checkbox'
        );

    checkboxes.forEach(
        (checkbox) => {
            const id =
                Number(checkbox.value);

            checkbox.checked =
                checked;

            if (checked) {
                state.selectedIds.add(id);
            } else {
                state.selectedIds.delete(id);
            }
        }
    );

    updateBulkDeleteButton();

    updateSelectAllState();
}


/* =========================================================
   Search
========================================================= */

let searchTimeout = null;

function handleSearch() {
    clearTimeout(
        searchTimeout
    );

    searchTimeout =
        setTimeout(
            async () => {
                state.search =
                    elements.search.value.trim();

                state.currentPage = 1;

                await reloadCategories(1);
            },
            350
        );
}


/* =========================================================
   Category Filter
========================================================= */

async function handleCategoryFilter() {
    state.categoryId =
        elements.categoryFilter.value;

    state.currentPage = 1;

    await reloadCategories(1);
}


/* =========================================================
   Reset Filters
========================================================= */

async function resetFilters() {
    elements.search.value = '';

    elements.categoryFilter.value = '';

    state.search = '';

    state.categoryId = '';

    state.currentPage = 1;

    await reloadCategories(1);
}


/* =========================================================
   Reload Table
========================================================= */

async function reloadCategories(
    page = 1
) {
    const params =
        new URLSearchParams();

    params.set(
        'page',
        page
    );

    if (state.search) {
        params.set(
            'search',
            state.search
        );
    }

    if (state.categoryId) {
        params.set(
            'category_id',
            state.categoryId
        );
    }

    try {
        showTableLoading();

        const response =
            await fetch(
                `/categories?${params.toString()}`,
                {
                    method: 'GET',

                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',

                        Accept:
                            'text/html',
                    },
                }
            );

        if (!response.ok) {
            throw new Error(
                'Unable to load categories.'
            );
        }

        const html =
            await response.text();

        elements.tableContainer.innerHTML =
            html;

        state.currentPage =
            Number(page);

        restoreSelectedCheckboxes();

        updateBulkDeleteButton();

    } catch (error) {
        showError(
            error.message ||
            'Unable to load categories.'
        );
    }
}


/* =========================================================
   Table Loading
========================================================= */

function showTableLoading() {
    elements.tableContainer.innerHTML = `
        <div class="p-8 text-center text-gray-500">
            Loading categories...
        </div>
    `;
}


/* =========================================================
   Tree
========================================================= */

async function reloadTree() {
    try {
        const response =
            await apiRequest(
                '/categories/tree'
            );

        renderTree(
            response.data
        );

    } catch (error) {
        console.error(
            'Unable to load category tree:',
            error
        );
    }
}


function renderTree(categories) {
    if (
        !categories ||
        categories.length === 0
    ) {
        elements.treeContainer.innerHTML = `
            <p class="text-gray-500">
                No categories found.
            </p>
        `;

        return;
    }

    const tree =
        buildTreeHtml(
            categories
        );

    elements.treeContainer.innerHTML =
        tree;
}


function buildTreeHtml(
    categories
) {
    return `
        <ul class="space-y-2">
            ${categories
                .map(
                    (category) =>
                        buildTreeNode(
                            category
                        )
                )
                .join('')}
        </ul>
    `;
}


function buildTreeNode(
    category
) {
    const children =
        category.children || [];

    const hasChildren =
        children.length > 0;

    return `
        <li>
            <div class="flex items-center gap-2">

                <span class="text-gray-500">
                    ${
                        hasChildren
                            ? '▼'
                            : '└'
                    }
                </span>

                <span class="font-medium">
                    ${escapeHtml(
                        category.name
                    )}
                </span>

            </div>

            ${
                hasChildren
                    ? `
                        <div class="ml-6 mt-2">
                            ${buildTreeHtml(
                                children
                            )}
                        </div>
                    `
                    : ''
            }
        </li>
    `;
}


/* =========================================================
   HTML Escape
========================================================= */

function escapeHtml(value) {
    const div =
        document.createElement(
            'div'
        );

    div.textContent =
        value ?? '';

    return div.innerHTML;
}


/* =========================================================
   Pagination
========================================================= */

async function handlePagination(
    link
) {
    const url =
        new URL(
            link.href,
            window.location.origin
        );

    const page =
        Number(
            url.searchParams.get(
                'page'
            ) || 1
        );

    await reloadCategories(
        page
    );
}


/* =========================================================
   Form Submit
========================================================= */

async function handleFormSubmit(
    event
) {
    event.preventDefault();

    clearValidationErrors();

    if (state.editingId) {
        await updateCategory();

        return;
    }

    await createCategory();
}


/* =========================================================
   Event Listeners
========================================================= */

/*
 * Add category
 */
elements.addButton?.addEventListener(
    'click',
    openCreateModal
);


/*
 * Close modal
 */
elements.closeModalButton?.addEventListener(
    'click',
    closeModal
);

elements.cancelModalButton?.addEventListener(
    'click',
    closeModal
);


/*
 * Form submit
 */
elements.form?.addEventListener(
    'submit',
    handleFormSubmit
);


/*
 * Search
 */
elements.search?.addEventListener(
    'input',
    handleSearch
);


/*
 * Category filter
 */
elements.categoryFilter?.addEventListener(
    'change',
    handleCategoryFilter
);


/*
 * Reset
 */
elements.resetFilters?.addEventListener(
    'click',
    resetFilters
);


/*
 * Bulk delete
 */
elements.bulkDeleteButton?.addEventListener(
    'click',
    bulkDelete
);


/*
 * Event delegation
 *
 * Important:
 * The table gets replaced by AJAX,
 * so we cannot attach listeners directly
 * to table buttons once on page load.
 */
document.addEventListener(
    'click',
    async (event) => {

        /*
         * Edit
         */
        const editButton =
            event.target.closest(
                '.edit-category'
            );

        if (editButton) {
            const id =
                Number(
                    editButton.dataset.id
                );

            await openEditModal(id);

            return;
        }


        /*
         * Delete
         */
        const deleteButton =
            event.target.closest(
                '.delete-category'
            );

        if (deleteButton) {
            const id =
                Number(
                    deleteButton.dataset.id
                );

            await deleteCategory(id);

            return;
        }


        /*
         * Pagination
         */
        const paginationLink =
            event.target.closest(
                '.pagination-link'
            );

        if (paginationLink) {
            event.preventDefault();

            await handlePagination(
                paginationLink
            );

            return;
        }

    }
);


/*
 * Checkbox delegation
 *
 * This also works after the table
 * is replaced through AJAX.
 */
document.addEventListener(
    'change',
    (event) => {

        /*
         * Individual checkbox
         */
        if (
            event.target.matches(
                '.category-checkbox'
            )
        ) {
            handleCheckboxChange(
                event.target
            );

            return;
        }


        /*
         * Select all checkbox
         */
        if (
            event.target.matches(
                '#select-all'
            )
        ) {
            toggleSelectAll(
                event.target.checked
            );
        }
    }
);


/*
 * Close modal when clicking outside it
 */
elements.modal?.addEventListener(
    'click',
    (event) => {

        if (
            event.target ===
            elements.modal
        ) {
            closeModal();
        }
    }
);


/*
 * Close modal with Escape
 */
document.addEventListener(
    'keydown',
    (event) => {

        if (
            event.key === 'Escape' &&
            !elements.modal.classList.contains(
                'hidden'
            )
        ) {
            closeModal();
        }
    }
);


/* =========================================================
   Initial State
========================================================= */

restoreSelectedCheckboxes();

updateBulkDeleteButton();