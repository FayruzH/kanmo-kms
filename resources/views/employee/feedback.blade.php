@extends('layouts.app')

@section('page_title', 'Feedback Chatbot')

@section('content')
<div class="container-fluid px-0">
    <div class="kms-feedback-page">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('warning'))
                <div class="alert alert-warning">{{ session('warning') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 ps-3">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="kms-hero mb-4">
                <h2>Feedback Chatbot</h2>
                <p class="fs-5 mb-0 opacity-75">
                    Kalau chatbot belum bisa jawab pertanyaan kamu, kirim pertanyaannya di sini. Tim admin akan review dan follow up.
                </p>
            </section>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('employee.feedback.store') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="question" class="form-label">Pertanyaan yang belum terjawab <span class="text-danger">*</span></label>
                            <textarea
                                id="question"
                                name="question"
                                rows="4"
                                class="form-control"
                                placeholder="Contoh: Bagaimana proses approval dokumen SOP untuk divisi retail?"
                                required
                            >{{ old('question') }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="detail" class="form-label">Detail tambahan (opsional)</label>
                            <textarea
                                id="detail"
                                name="detail"
                                rows="4"
                                class="form-control"
                                placeholder="Bisa isi konteks tambahan, jawaban chatbot terakhir, atau kebutuhan spesifik."
                            >{{ old('detail') }}</textarea>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">
                                Kirim Feedback
                            </button>
                        </div>
                    </form>
                </div>
            </div>
    </div>
</div>
@endsection
