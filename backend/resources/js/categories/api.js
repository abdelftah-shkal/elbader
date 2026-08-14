/**
 * API Module - Communication with Laravel Backend
 */

function getCsrfToken() {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content") || ""
    );
}

export async function apiRequest(
    url,
    { method = "GET", data = null, headers = {} } = {},
) {
    const options = {
        method,
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": getCsrfToken(),
            "X-Requested-With": "XMLHttpRequest",
            ...headers,
        },
    };

    if (data !== null) {
        options.headers["Content-Type"] = "application/json";
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);

    let responseData;
    try {
        responseData = await response.json();
    } catch {
        responseData = {
            success: false,
            message: "Invalid server response.",
        };
    }

    if (!response.ok) {
        throw responseData;
    }

    return responseData;
}

export async function getCategory(id) {
    return apiRequest(`/categories/${id}`);
}

export async function createCategory(data) {
    return apiRequest("/categories", {
        method: "POST",
        data,
    });
}

export async function updateCategory(id, data) {
    return apiRequest(`/categories/${id}`, {
        method: "PUT",
        data,
    });
}

export async function deleteCategory(id) {
    return apiRequest(`/categories/${id}`, {
        method: "DELETE",
    });
}

export async function bulkDeleteCategories(ids) {
    return apiRequest("/categories/bulk-delete", {
        method: "DELETE",
        data: { ids },
    });
}

export async function getParentCategories(categoryId = null) {
    let url = "/categories/parents";
    if (categoryId) {
        url += `/${categoryId}`;
    }
    return apiRequest(url);
}

export async function getCategoryTree() {
    return apiRequest("/categories/tree");
}

export async function fetchCategoriesTable(params = {}) {
    const queryParams = new URLSearchParams();
    if (params.page) queryParams.set("page", params.page);
    if (params.search) queryParams.set("search", params.search);
    if (params.category_id) queryParams.set("category_id", params.category_id);

    const response = await fetch(`/categories?${queryParams.toString()}`, {
        method: "GET",
        headers: {
            "X-Requested-With": "XMLHttpRequest",
            Accept: "text/html",
        },
    });

    if (!response.ok) {
        throw new Error("Unable to load categories.");
    }

    return response.text();
}
