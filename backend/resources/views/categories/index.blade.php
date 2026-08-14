@extends('layouts.app')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-2 border-b border-slate-200/60">
        <div>
            <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
                Categories
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Manage your product hierarchy, search records, and organize categories cleanly.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <button
                type="button"
                id="add-category-btn"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold rounded-xl shadow-xs shadow-indigo-200 hover:shadow-md transition-all duration-200 cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Add Category
            </button>
        </div>
    </div>


    {{-- Filter Card --}}
    <div class="bg-white rounded-2xl p-5 border border-slate-200/80 shadow-xs space-y-4">
        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-slate-400">
            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
            </svg>
            Filter & Search
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            {{-- Search --}}
            <div class="md:col-span-6 lg:col-span-5">
                <label for="search" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Search Name
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Search category by name..."
                        class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                    >
                </div>
            </div>

            {{-- Category Filter --}}
            <div class="md:col-span-4 lg:col-span-5">
                <label for="category-filter" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    Parent Category
                </label>
                <select
                    id="category-filter"
                    class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer"
                >
                    <option value="">All Categories</option>
                    @foreach ($allCategories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Reset Button --}}
            <div class="md:col-span-2 lg:col-span-2">
                <button
                    type="button"
                    id="reset-filters"
                    class="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 bg-slate-100 hover:bg-slate-200 active:bg-slate-300 text-slate-700 text-sm font-semibold rounded-xl transition-all cursor-pointer"
                >
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Reset
                </button>
            </div>
        </div>
    </div>


    {{-- Main Content Grid: Table + Tree --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        {{-- Table Section (Left 8 cols on desktop) --}}
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">
                        Category List
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Overview of current categories and relationships
                    </p>
                </div>

                <button
                    type="button"
                    id="bulk-delete-btn"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 disabled:opacity-40 disabled:hover:bg-rose-50 disabled:cursor-not-allowed border border-rose-200/60 rounded-xl text-xs font-semibold transition-all cursor-pointer"
                    disabled
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Delete Selected
                </button>
            </div>

            {{-- Dynamic Table Container --}}
            <div id="categories-table-container">
                @include('categories._table')
            </div>
        </div>


        {{-- Tree View Section (Right 4 cols on desktop) --}}
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="p-1.5 bg-indigo-50 text-indigo-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11" />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">
                            Hierarchy Tree
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Visual category structure
                        </p>
                    </div>
                </div>
            </div>

            <div id="category-tree-container" class="p-5">
                @include('categories._tree', [
                    'categories' => $tree
                ])
            </div>
        </div>

    </div>

</div>

@include('categories._form')
@include('categories._delete_modal')

@vite(['resources/js/categories/index.js'])

@endsection