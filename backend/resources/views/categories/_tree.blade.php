@if ($categories->isEmpty())
    <div class="flex flex-col items-center justify-center py-8 text-center">
        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>
            </svg>
        </div>
        <p class="text-xs font-medium text-slate-500">No category tree available</p>
    </div>
@else
    <ul class="space-y-1 text-sm">
        @foreach ($categories as $category)
            <li class="tree-node">
                @if ($category->children->isNotEmpty())
                    <div class="tree-toggle flex items-center gap-2 py-1.5 px-2.5 rounded-xl hover:bg-slate-100/80 transition-colors cursor-pointer select-none">
                        <div class="w-5 h-5 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center text-xs font-bold shrink-0">
                            <svg class="tree-chevron w-3.5 h-3.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                            </svg>
                        </div>

                        <span class="font-medium text-slate-800 text-sm hover:text-indigo-600 transition-colors">
                            {{ $category->name }}
                        </span>

                        <span class="ml-auto text-[10px] font-bold text-indigo-600 bg-indigo-50 border border-indigo-100/80 rounded-full px-2 py-0.5">
                            {{ $category->children->count() }} {{ Str::plural('sub', $category->children->count()) }}
                        </span>
                    </div>

                    <div class="tree-children ml-4 pl-3.5 border-l-2 border-slate-200/80 mt-1 space-y-1 hidden">
                        @include('categories._tree', [
                            'categories' => $category->children
                        ])
                    </div>
                @else
                    <div class="flex items-center gap-2 py-1.5 px-2.5 rounded-xl text-slate-700 select-none">
                        <div class="w-5 h-5 rounded-md bg-slate-100 text-slate-400 flex items-center justify-center text-xs shrink-0">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" />
                            </svg>
                        </div>
                        <span class="font-medium text-slate-700 text-sm">
                            {{ $category->name }}
                        </span>
                    </div>
                @endif
            </li>
        @endforeach
    </ul>
@endif