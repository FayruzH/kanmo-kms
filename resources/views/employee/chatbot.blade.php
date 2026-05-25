@extends('layouts.app')

@section('page_title', 'Chatbot')

@php
    $n8nChatEnabled = filter_var(config('services.n8n.chat_enabled', true), FILTER_VALIDATE_BOOLEAN);
    $n8nChatWebhookUrl = config('services.n8n.chat_webhook_url');
    $n8nChatTitle = config('services.n8n.chat_title', 'KMS Assistant');
    $n8nChatSubtitle = config('services.n8n.chat_subtitle', 'Ask anything about SOP and KMS.');
    $n8nChatWelcomeMessage = config('services.n8n.chat_welcome_message', 'Hi! Need help with KMS today?');
@endphp

@if($n8nChatEnabled && filled($n8nChatWebhookUrl))
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet">
        <style>
            #n8n-chat-page {
                width: 100%;
                height: calc(100vh - 200px);
                min-height: 620px;
                --chat--color--primary: #f26a21;
                --chat--color--primary-shade-50: #dc5f1d;
                --chat--color--primary--shade-100: #c9561a;
                --chat--color--secondary: #0f1642;
                --chat--color-secondary-shade-50: #101c58;
                --chat--header--background: #f26a21;
                --chat--header--color: #ffffff;
                --chat--body--background: #f3f5f9;
                --chat--footer--background: #ffffff;
                --chat--message--bot--background: #ffffff;
                --chat--message--bot--color: #0f1f33;
                --chat--message--user--background: #f26a21;
                --chat--message--user--color: #ffffff;
                --chat--textarea--height: 36px;
                --chat--input--background: #ffffff;
                --chat--input--text-color: #0f1f33;
                --chat--input--padding: 0.35rem 0.75rem;
                --chat--input--line-height: 1.2;
                --chat--input--send--button--background: #ffffff;
                --chat--input--send--button--color: #f26a21;
                --chat--input--send--button--background-hover: #fff3eb;
                --chat--input--send--button--color-hover: #dc5f1d;
                --chat--window--width: 100%;
                --chat--window--height: 100%;
                --chat--window--right: 0;
                --chat--window--bottom: 0;
                --chat--window--border-radius: 16px;
                --chat--window--border: 1px solid var(--kms-border);
            }

            @media (max-width: 992px) {
                #n8n-chat-page {
                    height: calc(100vh - 180px);
                    min-height: 540px;
                }
            }

            #n8n-chat-page .chat-footer {
                padding-top: 6px !important;
                padding-bottom: 6px !important;
            }

            #n8n-chat-page textarea {
                min-height: 36px !important;
                height: 36px !important;
                max-height: 36px !important;
                padding: 7px 12px !important;
                line-height: 20px !important;
                overflow-y: hidden !important;
                resize: none !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script type="module">
            import { createChat } from 'https://cdn.jsdelivr.net/npm/@n8n/chat/dist/chat.bundle.es.js';

            createChat({
                webhookUrl: @json($n8nChatWebhookUrl),
                target: '#n8n-chat-page',
                mode: 'fullscreen',
                showWelcomeScreen: false,
                loadPreviousSession: false,
                initialMessages: [@json($n8nChatWelcomeMessage)],
                i18n: {
                    en: {
                        title: @json($n8nChatTitle),
                        subtitle: @json($n8nChatSubtitle),
                        inputPlaceholder: 'Type your question...',
                    },
                },
                metadata: {
                    path: @json(request()->path()),
                    userId: @json(optional(auth()->user())->id),
                },
            });
        </script>
    @endpush
@endif

@section('content')
<div class="container-fluid px-0">
    @if($n8nChatEnabled && filled($n8nChatWebhookUrl))
        <div id="n8n-chat-page"></div>
    @else
        <div class="alert alert-warning mb-0">
            Chatbot belum aktif. Cek kembali konfigurasi <code>N8N_CHAT_ENABLED</code> dan <code>N8N_CHAT_WEBHOOK_URL</code>.
        </div>
    @endif
</div>
@endsection
