@extends('layouts.admin')

@section('title', 'Trash Categories')
@section('page-title', 'Trash Categories')

@section('content')

<div class="card">

    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">
            Deleted Categories
        </h3>

        <a href="{{ route('admin.categories.index') }}"
           class="btn btn-secondary btn-sm">
            Back to Categories
        </a>
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
                        <th>Deleted At</th>
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
                                {{ $category->deleted_at?->format('d M Y H:i') ?? '-' }}
                            </td>

                            <td>
                                <form
                                    action="{{ route('admin.categories.restore', $category->id) }}"
                                    method="POST"
                                    onsubmit="return confirm('Restore kategori ini?')">

                                    @csrf
                                    @method('PUT')

                                    <button type="submit"
                                            class="btn btn-success btn-sm">
                                        Restore
                                    </button>
                                </form>
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="6" class="text-center">
                                Trash kosong
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