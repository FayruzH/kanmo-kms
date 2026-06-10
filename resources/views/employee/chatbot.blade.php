@extends('layouts.app')

@section('page_title', 'Chatbot')

@php
    $n8nChatEnabled = filter_var(config('services.n8n.chat_enabled', true), FILTER_VALIDATE_BOOLEAN);
    $n8nChatWebhookUrl = config('services.n8n.chat_webhook_url');
    $n8nChatTitle = config('services.n8n.chat_title', 'KMS Assistant');
    $n8nChatSubtitle = config('services.n8n.chat_subtitle', 'Ask anything about SOP and KMS.');
    $n8nChatWelcomeMessage = config('services.n8n.chat_welcome_message', 'Hi! Need help with KMS today?');
    $dashboardQuestion = trim((string) request('question'));
@endphp

@if($n8nChatEnabled && filled($n8nChatWebhookUrl))
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/@n8n/chat/dist/style.css" rel="stylesheet">
        <style>
            .kms-chatbot-page {
                max-width: 1180px;
                margin: 0 auto;
            }

            .kms-chatbot-shell {
                overflow: hidden;
                border: 1px solid var(--kms-border);
                border-radius: 18px;
                background: #ffffff;
                box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
            }

            #n8n-chat-page {
                width: 100%;
                height: min(720px, calc(100vh - 150px));
                min-height: 560px;
                --chat--spacing: 1rem;
                --chat--border-radius: 12px;
                --chat--color-primary: #f26a21;
                --chat--color-primary-shade-50: #dc5f1d;
                --chat--color-primary-shade-100: #c9561a;
                --chat--color-secondary: #0f1642;
                --chat--color-secondary-shade-50: #101c58;
                --chat--header--background: #f26a21;
                --chat--header--color: #ffffff;
                --chat--header--padding: 22px 26px;
                --chat--heading--font-size: 1.55rem;
                --chat--subtitle--font-size: 0.95rem;
                --chat--subtitle--line-height: 1.4;
                --chat--body--background: #f3f5f9;
                --chat--footer--background: #ffffff;
                --chat--footer--color: #0f1f33;
                --chat--messages-list--padding: 20px 24px;
                --chat--message--font-size: 0.95rem;
                --chat--message--padding: 12px 14px;
                --chat--message--border-radius: 14px;
                --chat--message--bot--background: #ffffff;
                --chat--message--bot--color: #0f1f33;
                --chat--message--bot--border: 1px solid #e6eaf0;
                --chat--message--user--background: #f26a21;
                --chat--message--user--color: #ffffff;
                --chat--textarea--height: 44px;
                --chat--textarea--max-height: 120px;
                --chat--input--background: #ffffff;
                --chat--input--text-color: #0f1f33;
                --chat--input--padding: 10px 14px;
                --chat--input--line-height: 1.35;
                --chat--input--send--button--background: transparent;
                --chat--input--send--button--color: #f26a21;
                --chat--input--send--button--background-hover: #fff3eb;
                --chat--input--send--button--color-hover: #dc5f1d;
                --chat--window--width: 100%;
                --chat--window--height: 100%;
                --chat--window--right: 0;
                --chat--window--bottom: 0;
                --chat--window--border-radius: 0;
                --chat--window--border: 0;
            }

            #n8n-chat-page .chat-window,
            #n8n-chat-page .chat-layout {
                border: 0 !important;
                border-radius: 0 !important;
                box-shadow: none !important;
                height: 100% !important;
            }

            #n8n-chat-page .chat-messages-list {
                justify-content: flex-start !important;
                gap: 10px;
            }

            #n8n-chat-page .chat-message {
                max-width: min(760px, 78%) !important;
            }

            #n8n-chat-page .chat-message.is-bot,
            #n8n-chat-page .chat-message.from-bot {
                align-self: flex-start;
            }

            #n8n-chat-page .chat-message.is-user,
            #n8n-chat-page .chat-message.from-user {
                align-self: flex-end;
            }

            #n8n-chat-page .chat-footer {
                border-top: 1px solid #dfe5ee;
                padding: 12px 16px !important;
            }

            .kms-chatbot-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 12px;
            }

            @media (max-width: 992px) {
                .kms-chatbot-page {
                    max-width: 100%;
                }

                #n8n-chat-page {
                    height: calc(100vh - 148px);
                    min-height: 500px;
                    --chat--header--padding: 18px 20px;
                    --chat--heading--font-size: 1.35rem;
                    --chat--messages-list--padding: 16px;
                }

                #n8n-chat-page .chat-message {
                    max-width: 88% !important;
                }
            }

            @media (max-width: 640px) {
                #n8n-chat-page {
                    height: calc(100vh - 132px);
                    min-height: 460px;
                }

                .kms-chatbot-shell {
                    border-radius: 14px;
                }

                .kms-chatbot-actions {
                    justify-content: stretch;
                }

                .kms-chatbot-actions .btn {
                    width: 100%;
                }
            }

            #n8n-chat-page textarea {
                min-height: 44px !important;
                padding: 10px 14px !important;
                line-height: 20px !important;
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

            const dashboardQuestion = @json($dashboardQuestion);
            if (dashboardQuestion) {
                const removeQuestionFromUrl = () => {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('question');
                    window.history.replaceState({}, '', url.toString());
                };

                const setInputValue = (element, value) => {
                    const prototype = Object.getPrototypeOf(element);
                    const descriptor = Object.getOwnPropertyDescriptor(prototype, 'value');
                    if (descriptor && descriptor.set) {
                        descriptor.set.call(element, value);
                    } else {
                        element.value = value;
                    }

                    element.dispatchEvent(new Event('input', { bubbles: true }));
                    element.dispatchEvent(new Event('change', { bubbles: true }));
                };

                const sendDashboardQuestion = (attempt = 0) => {
                    const textarea = document.querySelector('#n8n-chat-page textarea');
                    const sendButton = document.querySelector('#n8n-chat-page button[type="submit"]')
                        || document.querySelector('#n8n-chat-page button[aria-label*="Send" i]')
                        || document.querySelector('#n8n-chat-page button:last-of-type');

                    if (!textarea || !sendButton) {
                        if (attempt < 40) {
                            window.setTimeout(() => sendDashboardQuestion(attempt + 1), 150);
                        }

                        return;
                    }

                    textarea.focus();
                    setInputValue(textarea, dashboardQuestion);
                    window.setTimeout(() => {
                        sendButton.click();
                        removeQuestionFromUrl();
                    }, 100);
                };

                window.setTimeout(sendDashboardQuestion, 300);
            }
        </script>
    @endpush
@endif

@section('content')
<div class="container-fluid px-0">
    @if($n8nChatEnabled && filled($n8nChatWebhookUrl))
        <div class="kms-chatbot-page">
            <div class="kms-chatbot-shell">
                <div id="n8n-chat-page"></div>
            </div>
            @if(\Illuminate\Support\Facades\Route::has('employee.feedback.create'))
                <div class="kms-chatbot-actions">
                    <a href="{{ route('employee.feedback.create') }}" class="btn btn-outline-secondary btn-sm">
                        Chatbot belum bisa jawab? Kirim Feedback
                    </a>
                </div>
            @endif
        </div>
    @else
        <div class="alert alert-warning mb-0">
            Chatbot belum aktif. Cek kembali konfigurasi <code>N8N_CHAT_ENABLED</code> dan <code>N8N_CHAT_WEBHOOK_URL</code>.
        </div>
    @endif
</div>
@endsection
