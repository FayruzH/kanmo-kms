@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h3 mb-3">Edit SOP</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.sop.update', $sop) }}" enctype="multipart/form-data">
        @method('PUT')
        @include('admin.sop._form', ['submitLabel' => 'Update'])
    </form>
</div>
@endsection
