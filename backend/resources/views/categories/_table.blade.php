<div class="overflow-x-auto">

    <table class="w-full text-left">

        <thead class="bg-gray-100">

            <tr>

                <th class="px-4 py-3">
                    <input
                        type="checkbox"
                        id="select-all"
                    >
                </th>

                <th class="px-4 py-3">
                    #
                </th>

                <th class="px-4 py-3">
                    Category
                </th>

                <th class="px-4 py-3">
                    Parent
                </th>

                <th class="px-4 py-3">
                    Actions
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse ($categories->items() as $category)

                <tr
                    class="border-b hover:bg-gray-50"
                    data-category-id="{{ $category->id }}"
                >

                    {{-- Checkbox --}}
                    <td class="px-4 py-3">

                        <input
                            type="checkbox"
                            class="category-checkbox"
                            value="{{ $category->id }}"
                        >

                    </td>


                    {{-- ID --}}
                    <td class="px-4 py-3">
                        {{ $category->id }}
                    </td>


                    {{-- Name --}}
                    <td class="px-4 py-3">
                        {{ $category->name }}
                    </td>


                    {{-- Parent --}}
                    <td class="px-4 py-3">

                        {{ $category->parent?->name ?? '—' }}

                    </td>


                    {{-- Actions --}}
                    <td class="px-4 py-3">

                        <div class="flex gap-2">

                            <button
                                type="button"
                                class="edit-category px-3 py-1 bg-yellow-500 text-white rounded"
                                data-id="{{ $category->id }}"
                            >
                                Edit
                            </button>

                            <button
                                type="button"
                                class="delete-category px-3 py-1 bg-red-500 text-white rounded"
                                data-id="{{ $category->id }}"
                            >
                                Delete
                            </button>

                        </div>

                    </td>

                </tr>

            @empty

                <tr>

                    <td
                        colspan="5"
                        class="px-4 py-8 text-center text-gray-500"
                    >
                        No categories found.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>

</div>


{{-- Pagination --}}

@if ($categories->lastPage() > 1)

    <div class="flex justify-center gap-2 p-4">

        {{-- Previous --}}
        @if ($categories->currentPage() > 1)

            <a
                href="{{ $categories->url($categories->currentPage() - 1) }}"
                class="pagination-link px-3 py-2 border rounded"
            >
                Previous
            </a>

        @endif


        {{-- Pages --}}
        @for (
            $page = 1;
            $page <= $categories->lastPage();
            $page++
        )

            <a
                href="{{ $categories->url($page) }}"
                class="pagination-link px-3 py-2 border rounded
                    {{ $page === $categories->currentPage()
                        ? 'bg-blue-600 text-white'
                        : ''
                    }}"
            >
                {{ $page }}
            </a>

        @endfor


        {{-- Next --}}
        @if ($categories->currentPage() < $categories->lastPage())

            <a
                href="{{ $categories->url($categories->currentPage() + 1) }}"
                class="pagination-link px-3 py-2 border rounded"
            >
                Next
            </a>

        @endif

    </div>

@endif