const state = {
    selectedIds: new Set(),
    search: '',
    categoryId: '',
};

const csrfToken = document
    .querySelector('meta[name="csrf-token"]')
    .getAttribute('content');


async function apiRequest(url, options = {}) {
    const response = await fetch(url, {
        ...options,

        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,

            ...options.headers,
        },
    });

    const data = await response.json();

    if (!response.ok) {
        throw data;
    }

    return data;
}

async function createCategory(formData) {
    try {

        const response = await apiRequest('/categories', {
            method: 'POST',

            body: JSON.stringify({
                name: formData.name,
                parent_id: formData.parent_id || null,
            }),
        });

        alert(response.message);

        closeModal();

        await reloadCategories();

    } catch (error) {

        handleValidationErrors(error);

    }
}


async function createCategory(formData) {
    try {

        const response = await apiRequest('/categories', {
            method: 'POST',

            body: JSON.stringify({
                name: formData.name,
                parent_id: formData.parent_id || null,
            }),
        });

        alert(response.message);

        closeModal();

        await reloadCategories();

    } catch (error) {

        handleValidationErrors(error);

    }
}


async function updateCategory(id, formData) {
    try {

        const response = await apiRequest(
            `/categories/${id}`,
            {
                method: 'PUT',

                body: JSON.stringify({
                    name: formData.name,
                    parent_id: formData.parent_id || null,
                }),
            }
        );

        alert(response.message);

        closeModal();

        await reloadCategories();

    } catch (error) {

        handleValidationErrors(error);

    }
}


async function deleteCategory(id) {

    const confirmed = confirm(
        'Are you sure you want to delete this category?'
    );

    if (!confirmed) {
        return;
    }

    try {

        const response = await apiRequest(
            `/categories/${id}`,
            {
                method: 'DELETE',
            }
        );

        alert(response.message);

        state.selectedIds.delete(Number(id));

        await reloadCategories();

    } catch (error) {

        alert(
            error.message ??
            'Unable to delete category.'
        );
    }
}   


async function bulkDelete() {

    if (state.selectedIds.size === 0) {
        alert('Please select at least one category.');
        return;
    }

    const confirmed = confirm(
        `Are you sure you want to delete ${state.selectedIds.size} categories?`
    );

    if (!confirmed) {
        return;
    }

    try {

        const response = await apiRequest(
            '/categories/bulk-delete',
            {
                method: 'DELETE',

                body: JSON.stringify({
                    ids: Array.from(state.selectedIds),
                }),
            }
        );

        alert(response.message);

        state.selectedIds.clear();

        await reloadCategories();

    } catch (error) {

        alert(
            error.message ??
            'Unable to delete categories.'
        );
    }
}

async function searchCategories() {

    state.search = document
        .querySelector('#search')
        .value
        .trim();

    state.categoryId = document
        .querySelector('#category-filter')
        .value;

    await reloadCategories(1);
}


async function reloadCategories(page = 1) {

    const params = new URLSearchParams();

    params.set('page', page);

    if (state.search) {
        params.set('search', state.search);
    }

    if (state.categoryId) {
        params.set(
            'category_id',
            state.categoryId
        );
    }

    const response = await fetch(
        `/categories?${params.toString()}`,
        {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        }
    );

    const html = await response.text();

    // We'll improve this in the next step
    // to return only the table partial.
}