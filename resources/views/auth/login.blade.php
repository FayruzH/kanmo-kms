<x-guest-layout>
    <div class="container-fluid h-100">
        <div class="row h-100">
            <div class="col-lg-6 d-none d-lg-flex align-items-center">
                <div class="kms-auth-hero w-100">
                    <div class="kms-auth-brand mb-4">
                        <div class="kms-auth-logo">K</div>
                        <div>
                            <div class="kms-auth-title">Kanmo KMS</div>
                            <div class="kms-auth-sub">Knowledge Management System</div>
                        </div>
                    </div>
                    <h1 class="display-5 fw-bold mb-3">One Portal for All SOP Knowledge</h1>
                    <p class="fs-5 mb-0 opacity-75">
                        Search procedures faster, track SOP lifecycle, and collaborate with AI-powered answers.
                    </p>
                </div>
            </div>

            <div class="col-12 col-lg-6 d-flex align-items-center justify-content-center py-5">
                <div class="kms-auth-card">
                    <div class="mb-4">
                        <h2 class="h3 fw-bold mb-2">Welcome Back</h2>
                        <p class="text-secondary mb-0">Sign in to access Kanmo KMS.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success py-2">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <div class="mb-3">
                            <label for="nip" class="form-label fw-semibold">NIP / Access ID</label>
                            <input id="nip" type="text" name="nip" class="form-control @error('nip') is-invalid @enderror" value="{{ old('nip') }}" required autofocus autocomplete="username" placeholder="Contoh: 21619 atau 0000021619">
                            @error('nip')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Gunakan NIP/Access ID numerik (akan dibaca sebagai 10 digit).</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input id="password" type="password" name="password" class="form-control @error('password') is-invalid @enderror" required autocomplete="current-password" placeholder="Enter your password">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                                <label for="remember_me" class="form-check-label">Remember me</label>
                            </div>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-decoration-none small">Forgot password?</a>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
