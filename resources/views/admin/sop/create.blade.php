@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="h3 mb-3">Create SOP</h1>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.sop.store') }}" enctype="multipart/form-data">
        @include('admin.sop._form', ['submitLabel' => 'Create'])
    </form>
</div>
@endsection
