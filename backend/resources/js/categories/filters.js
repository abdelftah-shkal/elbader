/**
 * Filters Module - Search, Category Filter, and Reset
 */
import { state } from './state.js';
import { reloadCategories } from './table.js';
import { debounce } from './utils.js';

export const debouncedSearch = debounce(async (searchValue) => {
    state.search = searchValue.trim();
    state.currentPage = 1;
    await reloadCategories(1);
}, 350);

export function handleSearch(event) {
    debouncedSearch(event.target.value);
}

export async function handleCategoryFilter(event) {
    state.categoryId = event.target.value;
    state.currentPage = 1;
    await reloadCategories(1);
}

export async function resetFilters() {
    const searchInput = document.querySelector('#search');
    const categoryFilterSelect = document.querySelector('#category-filter');

    if (searchInput) searchInput.value = '';
    if (categoryFilterSelect) categoryFilterSelect.value = '';

    state.search = '';
    state.categoryId = '';
    state.currentPage = 1;

    await reloadCategories(1);
}
