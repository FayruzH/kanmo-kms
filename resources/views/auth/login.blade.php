<x-guest-layout>
    <div class="container kms-auth-container">
        <div class="row align-items-center g-4 g-xl-5">
            <div class="col-lg-7 d-none d-lg-block">
                <div class="kms-auth-hero">
                    <div class="kms-auth-brand mb-4">
                        <div class="kms-auth-logo">K</div>
                        <div>
                            <div class="kms-auth-title">Kanmo KMS</div>
                            <div class="kms-auth-sub">Knowledge Management System</div>
                        </div>
                    </div>

                    <h1 class="kms-auth-headline mb-3">One Portal for All SOP Knowledge</h1>
                    <p class="kms-auth-lead mb-0">
                        Search procedures faster, track SOP lifecycle, and collaborate with AI-powered answers.
                    </p>

                    <div class="kms-auth-points mt-4">
                        <div class="kms-auth-point">
                            <i class="bi bi-search"></i>
                            <span>Find SOP in seconds with smart and relevant search results.</span>
                        </div>
                        <div class="kms-auth-point">
                            <i class="bi bi-shield-check"></i>
                            <span>Keep each update validated, structured, and easy to audit.</span>
                        </div>
                        <div class="kms-auth-point">
                            <i class="bi bi-robot"></i>
                            <span>Get AI-assisted answers without leaving your SOP workspace.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-5">
                <div class="kms-auth-card">
                    <div class="kms-auth-brand kms-auth-brand-mobile d-lg-none mb-4">
                        <div class="kms-auth-logo">K</div>
                        <div>
                            <div class="kms-auth-title">Kanmo KMS</div>
                            <div class="kms-auth-sub">Knowledge Management System</div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <h2 class="kms-auth-card-title mb-1">Welcome Back</h2>
                        <p class="kms-auth-card-subtitle mb-0">Sign in to access Kanmo KMS.</p>
                    </div>

                    @if (session('status'))
                        <div class="alert alert-success py-2 mb-3">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="kms-auth-form">
                        @csrf

                        <div class="mb-3">
                            <label for="nip" class="form-label fw-semibold">NIP / Access ID</label>
                            <div class="kms-auth-input-wrap @error('nip') is-invalid @enderror">
                                <span class="kms-auth-input-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                                <input id="nip" type="text" name="nip" class="form-control kms-auth-input @error('nip') is-invalid @enderror" value="{{ old('nip') }}" required autofocus autocomplete="username" placeholder="Contoh: 21619 atau 0000021619">
                            </div>
                            @error('nip')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Gunakan NIP/Access ID numerik (akan dibaca sebagai 10 digit).</div>
                        </div>

                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="kms-auth-input-wrap @error('password') is-invalid @enderror">
                                <span class="kms-auth-input-icon" aria-hidden="true"><i class="bi bi-lock"></i></span>
                                <input id="password" type="password" name="password" class="form-control kms-auth-input @error('password') is-invalid @enderror" required autocomplete="current-password" placeholder="Enter your password">
                            </div>
                            @error('password')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4 kms-auth-meta">
                            <div class="form-check">
                                <input id="remember_me" type="checkbox" class="form-check-input" name="remember">
                                <label for="remember_me" class="form-check-label">Remember me</label>
                            </div>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" class="text-decoration-none small">Forgot password?</a>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-primary w-100 fw-semibold kms-auth-submit">Login</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
