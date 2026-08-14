@extends('layouts.app')

@section('content')

<div class="container mx-auto px-4 py-8">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">
            Category Management
        </h1>

        <button
            type="button"
            id="add-category-btn"
            class="px-4 py-2 bg-blue-600 text-white rounded"
        >
            Add Category
        </button>
    </div>


    {{-- Filters --}}
    <div class="bg-white p-4 rounded shadow mb-6">

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

            {{-- Search --}}
            <div>
                <label
                    for="search"
                    class="block mb-1 font-medium"
                >
                    Search
                </label>

                <input
                    type="text"
                    id="search"
                    name="search"
                    placeholder="Search category..."
                    class="w-full border rounded px-3 py-2"
                >
            </div>


            {{-- Category Filter --}}
            <div>
                <label
                    for="category-filter"
                    class="block mb-1 font-medium"
                >
                    Category
                </label>

                <select
                    id="category-filter"
                    class="w-full border rounded px-3 py-2"
                >
                    <option value="">
                        All Categories
                    </option>

                    @foreach ($allCategories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach

                </select>
            </div>


            {{-- Reset --}}
            <div class="flex items-end">
                <button
                    type="button"
                    id="reset-filters"
                    class="px-4 py-2 border rounded"
                >
                    Reset
                </button>
            </div>

        </div>

    </div>


    {{-- Table Section --}}
    <div class="bg-white rounded shadow mb-8">

        <div class="p-4 border-b flex items-center justify-between">

            <h2 class="text-lg font-semibold">
                Categories
            </h2>

            <button
                type="button"
                id="bulk-delete-btn"
                class="px-4 py-2 bg-red-600 text-white rounded disabled:opacity-50"
                disabled
            >
                Delete Selected
            </button>

        </div>


        {{-- Table --}}
        <div id="categories-table-container">
            @include('categories._table')
        </div>

    </div>


    {{-- Tree Section --}}
    <div class="bg-white rounded shadow">

        <div class="p-4 border-b">
            <h2 class="text-lg font-semibold">
                Category Tree
            </h2>
        </div>

        <div
            id="category-tree-container"
            class="p-6"
        >
            @include('categories._tree', [
                'categories' => $tree
            ])
        </div>

    </div>

</div>


{{-- Category Modal --}}
<div
    id="category-modal"
    class="hidden fixed inset-0 bg-black/50 flex items-center justify-center"
>
    <div class="bg-white rounded-lg w-full max-w-md p-6">

        <div class="flex justify-between mb-6">

            <h2
                id="modal-title"
                class="text-xl font-semibold"
            >
                Add Category
            </h2>

            <button
                type="button"
                id="close-modal"
                class="text-gray-500"
            >
                ✕
            </button>

        </div>


        <form id="category-form">

            <input
                type="hidden"
                id="category-id"
            >


            {{-- Name --}}
            <div class="mb-4">

                <label
                    for="category-name"
                    class="block mb-1 font-medium"
                >
                    Category Name
                </label>

                <input
                    type="text"
                    id="category-name"
                    name="name"
                    class="w-full border rounded px-3 py-2"
                >

                <p
                    id="name-error"
                    class="text-red-500 text-sm mt-1 hidden"
                ></p>

            </div>


            {{-- Parent --}}
            <div class="mb-6">

                <label
                    for="category-parent"
                    class="block mb-1 font-medium"
                >
                    Parent Category
                </label>

                <select
                    id="category-parent"
                    name="parent_id"
                    class="w-full border rounded px-3 py-2"
                >

                    <option value="">
                        No Parent
                    </option>

                    @foreach ($allCategories as $category)

                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>

                <p
                    id="parent-error"
                    class="text-red-500 text-sm mt-1 hidden"
                ></p>

            </div>


            {{-- Buttons --}}
            <div class="flex justify-end gap-3">

                <button
                    type="button"
                    id="cancel-modal"
                    class="px-4 py-2 border rounded"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="save-category-btn"
                    class="px-4 py-2 bg-blue-600 text-white rounded"
                >
                    Save
                </button>

            </div>

        </form>

    </div>
</div>


<script src="/js/categories.js" type="module"></script>

@endsection