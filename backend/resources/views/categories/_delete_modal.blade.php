{{-- Delete Confirmation Modal --}}
<div
    id="delete-modal"
    class="hidden fixed inset-0 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center z-50 p-4 transition-opacity duration-200"
>
    <div class="bg-white rounded-2xl w-full max-w-sm p-6 border border-slate-100 shadow-xl space-y-5 transform transition-all">
        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center shrink-0 border border-rose-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <div class="flex-1">
                <h3 id="delete-modal-title" class="text-base font-bold text-slate-900">
                    Delete Category
                </h3>
                <p id="delete-modal-message" class="text-xs text-slate-500 mt-1 leading-relaxed">
                    Are you sure you want to delete this category? This action cannot be undone.
                </p>
            </div>
        </div>

        <div class="flex items-center justify-end gap-2.5 pt-3 border-t border-slate-100">
            <button
                type="button"
                id="cancel-delete-btn"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors cursor-pointer"
            >
                Cancel
            </button>
            <button
                type="button"
                id="confirm-delete-btn"
                class="px-4 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white text-xs font-semibold rounded-xl shadow-xs shadow-rose-200 transition-colors cursor-pointer"
            >
                Delete Category
            </button>
        </div>
    </div>
</div>
