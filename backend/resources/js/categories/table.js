/**
 * Table Module - Loading, Reloading, and AJAX Pagination
 */
import { state } from './state.js';
import { fetchCategoriesTable } from './api.js';
import { restoreSelectedCheckboxes, updateBulkDeleteButton } from './selection.js';
import { showError } from './utils.js';

export function showTableLoading() {
    const tableContainer = document.querySelector('#categories-table-container');
    if (tableContainer) {
        tableContainer.innerHTML = `
            <div class="p-8 text-center text-gray-500">
                Loading categories...
            </div>
        `;
    }
}

export async function reloadCategories(page = 1) {
    const tableContainer = document.querySelector('#categories-table-container');
    if (!tableContainer) return;

    try {
        showTableLoading();

        const html = await fetchCategoriesTable({
            page,
            search: state.search,
            category_id: state.categoryId,
        });

        tableContainer.innerHTML = html;
        state.currentPage = Number(page);

        restoreSelectedCheckboxes();
        updateBulkDeleteButton();
    } catch (error) {
        showError(error.message || 'Unable to load categories.');
    }
}

export async function handlePagination(link) {
    const url = new URL(link.href, window.location.origin);
    const page = Number(url.searchParams.get('page') || 1);
    await reloadCategories(page);
}
