<div class="overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-50 border-b border-slate-100 text-xs font-bold uppercase tracking-wider text-slate-400">
                <th class="px-5 py-3.5 w-12 text-center">
                    <input
                        type="checkbox"
                        id="select-all"
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer"
                    >
                </th>
                <th class="px-4 py-3.5 w-16">#</th>
                <th class="px-5 py-3.5">Category Name</th>
                <th class="px-5 py-3.5">Parent Category</th>
                <th class="px-5 py-3.5 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 text-sm">
            @forelse ($categories->items() as $category)
                <tr
                    class="hover:bg-slate-50/80 transition-colors group"
                    data-category-id="{{ $category->id }}"
                >
                    {{-- Checkbox --}}
                    <td class="px-5 py-4 text-center">
                        <input
                            type="checkbox"
                            class="category-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/20 cursor-pointer"
                            value="{{ $category->id }}"
                        >
                    </td>

                    {{-- ID --}}
                    <td class="px-4 py-4 text-xs font-semibold text-slate-400 font-mono">
                        #{{ $category->id }}
                    </td>

                    {{-- Name --}}
                    <td class="px-5 py-4">
                        <span class="font-semibold text-slate-900 group-hover:text-indigo-600 transition-colors">
                            {{ $category->name }}
                        </span>
                    </td>

                    {{-- Parent --}}
                    <td class="px-5 py-4">
                        @if ($category->parent)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
                                </svg>
                                {{ $category->parent->name }}
                            </span>
                        @else
                            <span class="text-xs font-medium text-slate-400 italic">
                                — Root
                            </span>
                        @endif
                    </td>

                    {{-- Actions --}}
                    <td class="px-5 py-4 text-right">
                        <div class="inline-flex items-center gap-1.5">
                            <button
                                type="button"
                                class="edit-category inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-indigo-50 text-slate-700 hover:text-indigo-600 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                                data-id="{{ $category->id }}"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                                Edit
                            </button>

                            <button
                                type="button"
                                class="delete-category inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-rose-50 text-slate-700 hover:text-rose-600 rounded-lg text-xs font-semibold transition-all cursor-pointer"
                                data-id="{{ $category->id }}"
                                data-name="{{ $category->name }}"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                Delete
                            </button>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-5 py-12 text-center">
                        <div class="flex flex-col items-center justify-center space-y-2">
                            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                            </div>
                            <p class="text-sm font-medium text-slate-600">No categories found</p>
                            <p class="text-xs text-slate-400">Try adjusting your search or filters.</p>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if ($categories->lastPage() > 1)
    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-center gap-1.5">
        {{-- Previous --}}
        @if ($categories->currentPage() > 1)
            <a
                href="{{ $categories->url($categories->currentPage() - 1) }}"
                class="pagination-link px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition-all cursor-pointer"
            >
                &larr; Prev
            </a>
        @endif

        {{-- Pages --}}
        @for ($page = 1; $page <= $categories->lastPage(); $page++)
            <a
                href="{{ $categories->url($page) }}"
                class="pagination-link px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all cursor-pointer
                    {{ $page === $categories->currentPage()
                        ? 'bg-indigo-600 border-indigo-600 text-white shadow-xs'
                        : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-100'
                    }}"
            >
                {{ $page }}
            </a>
        @endfor

        {{-- Next --}}
        @if ($categories->currentPage() < $categories->lastPage())
            <a
                href="{{ $categories->url($categories->currentPage() + 1) }}"
                class="pagination-link px-3 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition-all cursor-pointer"
            >
                Next &rarr;
            </a>
        @endif
    </div>
@endif