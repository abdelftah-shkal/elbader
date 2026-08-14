# Category Management API

## 1. Overview

The Category Management API provides a complete set of endpoints to create, retrieve, update, filter, paginate, and delete categories in a hierarchical tree structure. 

Categories support self-referencing parent/child relationships of arbitrary depth. For instance:

```text
Electronics
├── Phones
│   ├── Android
│   └── iPhone
└── Computers
    └── Laptops
```

Key features of this API include:
- **Hierarchical Tree Resolution**: Efficient in-memory parent-child tree construction and available parent dropdown options.
- **Recursive Descendant Filtering**: Filtering categories by a parent category (`category_id`) includes that category and all of its nested descendants.
- **Conflict & Cycle Prevention**: Strict validation preventing duplicate category names under the same parent, self-parenting, circular parent relationships, and deletion of categories that possess child subcategories.
- **Native/Manual Pagination**: Custom clamped pagination (`per_page` up to 100) retaining query parameters.

---

## 2. Base URL

The default base URL for local development is:

```text
http://127.0.0.1:8000
```

Throughout this documentation and the associated Postman collection, the base URL is represented as the variable:

```text
{{base_url}}
```

In Postman, set the environment or collection variable:
- `base_url`: `http://127.0.0.1:8000`

---

## 3. Authentication & Middleware

- **Authentication**: Endpoints currently do not require user authentication (`auth` middleware is not applied). All routes are publicly accessible in this application version.
- **Middleware**: Routes pass through the `web` middleware group (`EncryptCookies`, `AddQueuedCookiesToResponse`, `StartSession`, `ShareErrorsFromSession`, `ValidateCsrfToken`, `SubstituteBindings`).
- **CSRF Token**: State-modifying requests (`POST`, `PUT`, `PATCH`, `DELETE`) submitted from web sessions require a valid CSRF token header (`X-CSRF-TOKEN`) or cookie.
- **Content-Type & Accept Headers**:
  - Send `Accept: application/json` for API JSON responses.
  - Send `Content-Type: application/json` when submitting JSON request bodies.
  - For `GET /categories`, sending `X-Requested-With: XMLHttpRequest` (or an AJAX header) returns the rendered HTML partial string (`categories._table`) instead of the full HTML page view.

---

## 4. Response Format

The API standardizes JSON responses into structured envelopes across JSON-returning endpoints.

### Standard JSON Success Response
```json
{
    "success": true,
    "message": "Category created successfully.",
    "data": {
        "id": 2,
        "name": "Phones",
        "parent_id": 1,
        "created_at": "2026-08-14T16:35:49.000000Z",
        "updated_at": "2026-08-14T16:35:49.000000Z",
        "parent": {
            "id": 1,
            "name": "Electronics"
        }
    }
}
```

### Standard JSON Validation Error Response (HTTP 422)
```json
{
    "success": false,
    "message": "Unable to create category.",
    "errors": {
        "name": [
            "Category name is required."
        ]
    }
}
```

### Standard JSON Not Found Response (HTTP 404)
When requesting a non-existent category ID via `GET /categories/{id}`:
```json
{
    "message": "No query results for model [App\\Models\\Category] 999"
}
```

### HTML Partial Response (`GET /categories`)
When `X-Requested-With: XMLHttpRequest` is provided in the request headers, `GET /categories` returns the rendered HTML string of `categories._table` for dynamic AJAX DOM replacement.

---

## 5. Endpoint Documentation

### List Categories

- **HTTP Method**: `GET`
- **URL**: `{{base_url}}/categories`
- **Purpose**: Retrieve paginated categories with optional keyword searching and category descendant filtering.
- **Headers**:
  - `Accept`: `application/json` (or `text/html`)
  - `X-Requested-With`: `XMLHttpRequest` (Optional, returns HTML partial if present)
- **Query Parameters**:
  | Parameter | Type | Required | Default | Description |
  | :--- | :--- | :--- | :--- | :--- |
  | `page` | Integer | No | `1` | Page number for pagination. |
  | `per_page` | Integer | No | `10` | Number of records per page (clamped between 1 and 100). |
  | `search` | String | No | `null` | Filters categories whose name matches `%search%` (case-insensitive search). |
  | `category_id` | Integer | No | `null` | Filters results to include the specified category ID and all its recursive descendants. |

#### Pagination Details
The endpoint utilizes a native/manual paginator (`App\Utils\Paginator`) that:
1. Calculates total matches with a `COUNT(*)` query.
2. Clamps `per_page` requests within `[1, 100]`.
3. Computes offset `($page - 1) * $per_page`.
4. Appends active search and filter parameters to pagination link URLs via `withQueryString()`.

#### HTTP Response (HTML View / Partial)
- Status: `200 OK`
- Body: Rendered Blade HTML view or table partial string.

---

### Get Category

- **HTTP Method**: `GET`
- **URL**: `{{base_url}}/categories/{category}`
- **Purpose**: Retrieve details of a specific category by ID, including its parent relation.
- **Path Parameters**:
  - `category` (Integer, Required): The unique ID of the category.
- **Headers**:
  - `Accept`: `application/json`

#### Example Request
`GET {{base_url}}/categories/2`

#### Successful Response (HTTP 200)
```json
{
    "success": true,
    "data": {
        "id": 2,
        "name": "Phones",
        "parent_id": 1,
        "created_at": "2026-08-14T16:35:49.000000Z",
        "updated_at": "2026-08-14T16:35:49.000000Z",
        "parent": {
            "id": 1,
            "name": "Electronics"
        }
    }
}
```

#### Error Response (HTTP 404)
```json
{
    "message": "No query results for model [App\\Models\\Category] 999"
}
```

---

### Create Category

- **HTTP Method**: `POST`
- **URL**: `{{base_url}}/categories`
- **Purpose**: Create a new category (either a root category or a child category under a parent).
- **Headers**:
  - `Content-Type`: `application/json`
  - `Accept`: `application/json`
  - `X-CSRF-TOKEN`: `<csrf-token>` (Required for web browser requests)
- **Request Body**:
  ```json
  {
      "name": "Phones",
      "parent_id": 1
  }
  ```

#### Validation Rules (`StoreCategoryRequest` & `CategoryService`)
- `name` (Required, String, Max 255 chars): Name of the category. Trimmed automatically.
- `parent_id` (Nullable, Integer, Exists in `categories.id`): Parent category ID. Omit or set to `null` to create a root category.
- **Unique Name Constraint**: Category names must be unique within the same parent (`parent_id`). Re-using an existing name under the same parent triggers a validation error (`HTTP 422`).

#### Successful Response (HTTP 201 Created)
```json
{
    "success": true,
    "message": "Category created successfully.",
    "data": {
        "id": 2,
        "name": "Phones",
        "parent_id": 1,
        "created_at": "2026-08-14T16:35:49.000000Z",
        "updated_at": "2026-08-14T16:35:49.000000Z",
        "parent": {
            "id": 1,
            "name": "Electronics"
        }
    }
}
```

---

### Update Category

- **HTTP Method**: `PUT` or `PATCH`
- **URL**: `{{base_url}}/categories/{category}`
- **Purpose**: Update an existing category's name or parent ID.
- **Path Parameters**:
  - `category` (Integer, Required): The category ID to update.
- **Headers**:
  - `Content-Type`: `application/json`
  - `Accept`: `application/json`
  - `X-CSRF-TOKEN`: `<csrf-token>`
- **Request Body**:
  ```json
  {
      "name": "Smartphones",
      "parent_id": 1
  }
  ```

#### Validation & Guard Rules (`UpdateCategoryRequest` & `CategoryService`)
- `name` (Required, String, Max 255 chars).
- `parent_id` (Nullable, Integer, Exists in `categories.id`).
- **Self-Parent Prevention**: A category cannot be set as its own parent (`parent_id == category.id`).
- **Circular Parent Prevention**: A category's parent cannot be updated to one of its own descendants.
- **Unique Name Constraint**: Name must remain unique under the selected parent (excluding the current category record).

#### Successful Response (HTTP 200 OK)
```json
{
    "success": true,
    "message": "Category updated successfully.",
    "data": {
        "id": 2,
        "name": "Smartphones",
        "parent_id": 1,
        "created_at": "2026-08-14T16:35:49.000000Z",
        "updated_at": "2026-08-14T16:35:50.000000Z",
        "parent": {
            "id": 1,
            "name": "Electronics"
        },
        "children": []
    }
}
```

---

### Delete Category

- **HTTP Method**: `DELETE`
- **URL**: `{{base_url}}/categories/{category}`
- **Purpose**: Delete a single leaf category.
- **Path Parameters**:
  - `category` (Integer, Required): The ID of the category to delete.
- **Headers**:
  - `Accept`: `application/json`
  - `X-CSRF-TOKEN`: `<csrf-token>`

#### Child Safeguard Behavior
If the target category has child categories (`$category->children()->exists()`), the deletion is **blocked** and returns an HTTP 422 validation response.

#### Successful Response (HTTP 200 OK)
```json
{
    "success": true,
    "message": "Category deleted successfully."
}
```

#### Error Response (HTTP 422 - Category Has Children)
```json
{
    "success": false,
    "message": "Cannot delete 'Electronics' because it has child categories.",
    "errors": {
        "category": [
            "Cannot delete 'Electronics' because it has child categories."
        ]
    }
}
```

---

### Bulk Delete Categories

- **HTTP Method**: `DELETE`
- **URL**: `{{base_url}}/categories/bulk-delete`
- **Purpose**: Delete multiple leaf categories in a single atomic operation.
- **Headers**:
  - `Content-Type`: `application/json`
  - `Accept`: `application/json`
  - `X-CSRF-TOKEN`: `<csrf-token>`
- **Request Body**:
  ```json
  {
      "ids": [3, 4]
  }
  ```

#### Validation Rules (`BulkDeleteCategoryRequest` & `CategoryService`)
- `ids` (Required, Array, Min 1 element).
- `ids.*` (Required, Integer, Distinct, Exists in `categories.id`).
- **Atomic Transaction & Child Check**: Executed within a database transaction using row locks (`lockForUpdate()`). Before deleting, a single query verifies if any category ID in `ids` is referenced as a `parent_id` by any category in the database.
- **All-or-Nothing Behavior**: If **any** selected category has children, or if any selected ID does not exist, the entire bulk delete operation fails and rolls back. No categories are deleted.

#### Successful Response (HTTP 200 OK)
```json
{
    "success": true,
    "message": "Categories deleted successfully."
}
```

#### Error Response (HTTP 422 - Contains Children or Non-Existent ID)
```json
{
    "success": false,
    "message": "Cannot delete categories that have child categories.",
    "errors": {
        "ids": [
            "Cannot delete categories that have child categories."
        ]
    }
}
```

---

### Parent Categories

- **HTTP Method**: `GET`
- **URL**: `{{base_url}}/categories/parents/{category?}`
- **Purpose**: Retrieve valid parent options for forms/dropdowns.
- **Path Parameters**:
  - `category` (Integer, Optional): Category ID being edited.
- **Headers**:
  - `Accept`: `application/json`

#### Filtering Logic
- When `category` is **omitted** (`GET /categories/parents`): Returns all categories in the database.
- When `category` is **provided** (`GET /categories/parents/2`): Excludes category #2 and **all** of its descendants to prevent cycle creation in the UI.

#### Successful Response (HTTP 200 OK)
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Electronics",
            "parent_id": null
        },
        {
            "id": 5,
            "name": "Computers",
            "parent_id": null
        }
    ]
}
```

---

### Category Tree

- **HTTP Method**: `GET`
- **URL**: `{{base_url}}/categories/tree`
- **Purpose**: Retrieve the full hierarchical tree structure of root categories with nested `children` relationships.
- **Headers**:
  - `Accept`: `application/json`

#### Successful Response (HTTP 200 OK)
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "Electronics",
            "parent_id": null,
            "children": [
                {
                    "id": 2,
                    "name": "Phones",
                    "parent_id": 1,
                    "children": [
                        {
                            "id": 3,
                            "name": "Android",
                            "parent_id": 2,
                            "children": []
                        },
                        {
                            "id": 4,
                            "name": "iPhone",
                            "parent_id": 2,
                            "children": []
                        }
                    ]
                }
            ]
        }
    ]
}
```

---

## 6. Search

Category search is executed via the `search` query parameter on the listing endpoint:

```text
GET {{base_url}}/categories?search=phone
```

- **Matching Behavior**: Case-insensitive SQL `LIKE %search%` comparison on the `name` column.
- **Empty Search**: When `search` is empty or omitted, search filtering is ignored.
- **Pagination Interaction**: Search results are paginated using the active `per_page` setting. Search parameters are maintained across pagination links (`withQueryString()`).

---

## 7. Category Descendant Filter

The `category_id` parameter on `GET /categories` filters categories by an ancestor category:

```text
GET {{base_url}}/categories?category_id=1
```

### Hierarchy Execution Example
Given hierarchy:
```text
Electronics (ID: 1)
├── Phones (ID: 2)
│   ├── Android (ID: 3)
│   └── iPhone (ID: 4)
└── Computers (ID: 5)
    └── Laptops (ID: 6)
```

- Querying `category_id=1` (Electronics):
  Returns IDs `[1, 2, 3, 4, 5, 6]` (Electronics + all descendants).
- Querying `category_id=2` (Phones):
  Returns IDs `[2, 3, 4]` (Phones + Android + iPhone).
- Non-Existent `category_id`:
  Returns an empty paginated result set (`where 1 = 0`).

---

## 8. Search + Descendant Filter

Both filters can be combined in a single request:

```text
GET {{base_url}}/categories?search=android&category_id=1&page=1
```

- **Combined Behavior**: Calculates the descendant ID set for `category_id=1` (`[1, 2, 3, 4, 5, 6]`), then applies the name search `LIKE '%android%'` within that subset.
- Result: Returns only the `Android` category record.

---

## 9. Validation Errors

Validation failures return HTTP status code `422 Unprocessable Content` with standard message and error fields.

### Missing Name (`POST /categories`)
```json
{
    "success": false,
    "message": "Unable to create category.",
    "errors": {
        "name": [
            "Category name is required."
        ]
    }
}
```

### Invalid Parent ID (`POST /categories`)
```json
{
    "success": false,
    "message": "Unable to create category.",
    "errors": {
        "parent_id": [
            "The selected parent category does not exist."
        ]
    }
}
```

### Duplicate Category Name Under Same Parent
```json
{
    "success": false,
    "message": "Unable to create category.",
    "errors": {
        "name": [
            "A category with this name already exists under the selected parent."
        ]
    }
}
```

### Self-Parent Violation (`PUT /categories/2`)
```json
{
    "success": false,
    "message": "Unable to update category.",
    "errors": {
        "parent_id": [
            "A category cannot be its own parent."
        ]
    }
}
```

### Circular Parent Violation (`PUT /categories/1`)
```json
{
    "success": false,
    "message": "Unable to update category.",
    "errors": {
        "parent_id": [
            "You cannot select one of this category's descendants as its parent."
        ]
    }
}
```

### Invalid Bulk Delete Payload (`DELETE /categories/bulk-delete`)
```json
{
    "success": false,
    "message": "Please select at least one category.",
    "errors": {
        "ids": [
            "Please select at least one category."
        ]
    }
}
```

---

## 10. HTTP Status Codes

| Status | Meaning | Actual Application Usage |
| :--- | :--- | :--- |
| `200` | OK | Returned on successful `GET` queries, `PUT/PATCH` category updates, single `DELETE`, and `DELETE /bulk-delete`. |
| `201` | Created | Returned on successful category creation via `POST /categories`. |
| `404` | Not Found | Returned when attempting to view (`GET /categories/{id}`), update, or delete a non-existent category record. |
| `422` | Unprocessable Content | Returned on FormRequest validation failure, name duplication, self-parenting, circular relations, or deletion of category with children. |
| `500` | Server Error | Returned on unhandled database connection or server exceptions. |

---

## 11. cURL Examples

### 1. List Categories with Search & Filtering
```bash
curl -X GET "{{base_url}}/categories?search=phone&category_id=1&per_page=10&page=1" \
  -H "Accept: application/json"
```

### 2. Get Single Category Details
```bash
curl -X GET "{{base_url}}/categories/2" \
  -H "Accept: application/json"
```

### 3. Create Root Category
```bash
curl -X POST "{{base_url}}/categories" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
      "name": "Electronics",
      "parent_id": null
  }'
```

### 4. Create Child Category
```bash
curl -X POST "{{base_url}}/categories" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
      "name": "Phones",
      "parent_id": 1
  }'
```

### 5. Update Category
```bash
curl -X PUT "{{base_url}}/categories/2" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
      "name": "Smartphones",
      "parent_id": 1
  }'
```

### 6. Delete Single Leaf Category
```bash
curl -X DELETE "{{base_url}}/categories/3" \
  -H "Accept: application/json"
```

### 7. Bulk Delete Leaf Categories
```bash
curl -X DELETE "{{base_url}}/categories/bulk-delete" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
      "ids": [3, 4]
  }'
```

### 8. Get Available Parents for Category Edit
```bash
curl -X GET "{{base_url}}/categories/parents/2" \
  -H "Accept: application/json"
```

### 9. Get Category Tree
```bash
curl -X GET "{{base_url}}/categories/tree" \
  -H "Accept: application/json"
```
