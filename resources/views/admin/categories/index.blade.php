@extends('layouts.admin')

@section('title', 'Kategori')
@section('page-title', 'Kategori')

@section('content')

    <div class="card">

        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                Daftar Kategori
            </h3>

            <div>
                <a href="{{ route('admin.categories.trash') }}" class="btn btn-secondary btn-sm">
                    Trash
                </a>

                <a href="{{ route('admin.categories.create') }}" class="btn btn-primary btn-sm">
                    Tambah Kategori
                </a>
            </div>
        </div>

        <div class="card-body">

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">

                    <thead>
                        <tr>
                            <th width="5%">ID</th>
                            <th>Name</th>
                            <th>Created By</th>
                            <th>Updated By</th>
                            <th>Created At</th>
                            <th>Updated At</th>
                            <th width="15%">Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        @forelse($categories as $category)
                            <tr>
                                <td>
                                    {{ $category->id }}
                                </td>

                                <td>
                                    <strong>{{ $category->name }}</strong>
                                </td>

                                <td>
                                    {{ $category->createdBy?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $category->updatedBy?->name ?? '-' }}
                                </td>

                                <td>
                                    {{ $category->created_at?->format('d M Y H:i') ?? '-' }}
                                </td>

                                <td>
                                    {{ $category->updated_at?->format('d M Y H:i') ?? '-' }}
                                </td>

                                <td>
                                    <a href="{{ route('admin.categories.edit', $category) }}"
                                        class="btn btn-warning btn-sm">
                                        Edit
                                    </a>

                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST"
                                        class="d-inline" onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">

                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="btn btn-danger btn-sm">
                                            Delete
                                        </button>

                                    </form>
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="7" class="text-center">
                                    No Data
                                </td>
                            </tr>
                        @endforelse

                    </tbody>

                </table>
            </div>

            <div class="mt-3">
                {{ $categories->links() }}
            </div>

        </div>

    </div>

@endsection
