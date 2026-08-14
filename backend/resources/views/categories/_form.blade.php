{{-- Category Modal --}}
<div
    id="category-modal"
    class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center z-50 p-4 transition-opacity duration-200"
>
    <div class="bg-white rounded-2xl w-full max-w-md p-6 border border-slate-100 shadow-xl space-y-6 transform transition-all">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <div>
                <h2
                    id="modal-title"
                    class="text-lg font-bold text-slate-900 tracking-tight"
                >
                    Add Category
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">
                    Configure details for your category
                </p>
            </div>

            <button
                type="button"
                id="close-modal"
                class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-700 flex items-center justify-center transition-colors cursor-pointer"
            >
                ✕
            </button>
        </div>


        {{-- Form --}}
        <form id="category-form" class="space-y-5">

            <input
                type="hidden"
                id="category-id"
            >

            {{-- Category Name --}}
            <div>
                <label
                    for="category-name"
                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                >
                    Category Name <span class="text-rose-500">*</span>
                </label>

                <input
                    type="text"
                    id="category-name"
                    name="name"
                    placeholder="e.g. Electronics, Laptops..."
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 placeholder-slate-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all"
                >

                <p
                    id="name-error"
                    class="text-rose-500 text-xs font-medium mt-1.5 validation-error hidden"
                ></p>
            </div>


            {{-- Parent Category --}}
            <div>
                <label
                    for="category-parent"
                    class="block text-xs font-semibold text-slate-700 mb-1.5"
                >
                    Parent Category
                </label>

                <select
                    id="category-parent"
                    name="parent_id"
                    class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all cursor-pointer"
                >
                    <option value="">No Parent (Root Level)</option>
                    @foreach ($allCategories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>

                <p
                    id="parent-error"
                    class="text-rose-500 text-xs font-medium mt-1.5 validation-error hidden"
                ></p>
            </div>


            {{-- Actions --}}
            <div class="flex items-center justify-end gap-3 pt-2">
                <button
                    type="button"
                    id="cancel-modal"
                    class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl transition-all cursor-pointer"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    id="save-category-btn"
                    class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold rounded-xl shadow-xs shadow-indigo-200 hover:shadow-md transition-all cursor-pointer"
                >
                    Save Category
                </button>
            </div>

        </form>

    </div>
</div>
