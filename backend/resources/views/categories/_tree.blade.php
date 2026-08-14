@if ($categories->isEmpty())

    <p class="text-gray-500">
        No categories found.
    </p>

@else

    <ul class="space-y-2">

        @foreach ($categories as $category)

            <li>

                <div class="flex items-center gap-2">

                    @if ($category->children->isNotEmpty())

                        <span class="text-gray-500">
                            ▼
                        </span>

                    @else

                        <span class="text-gray-500">
                            └
                        </span>

                    @endif

                    <span class="font-medium">
                        {{ $category->name }}
                    </span>

                </div>


                @if ($category->children->isNotEmpty())

                    <div class="ml-6 mt-2">

                        @include('categories._tree', [
                            'categories' => $category->children
                        ])

                    </div>

                @endif

            </li>

        @endforeach

    </ul>

@endif