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
                height: min(920px, calc(100vh - 78px));
                min-height: 760px;
                font-family: var(--bs-body-font-family, "Segoe UI", sans-serif);
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
                --chat--message--font-size: 0.96rem;
                --chat--message--padding: 12px 15px;
                --chat--message--border-radius: 16px;
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
                gap: 12px;
                background: #f3f5f9 !important;
            }

            #n8n-chat-page .chat-message {
                width: fit-content !important;
                max-width: min(760px, 74%) !important;
                border-radius: 18px !important;
                font-size: 0.96rem !important;
                line-height: 1.45 !important;
                letter-spacing: 0.005em;
                overflow-wrap: anywhere;
                word-break: normal;
                box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
            }

            #n8n-chat-page .chat-message.is-bot,
            #n8n-chat-page .chat-message.from-bot,
            #n8n-chat-page .chat-message.chat-message-from-bot {
                align-self: flex-start !important;
                margin-right: auto !important;
                border: 1px solid #e6eaf0 !important;
                border-top-left-radius: 6px !important;
            }

            #n8n-chat-page .chat-message.is-user,
            #n8n-chat-page .chat-message.from-user,
            #n8n-chat-page .chat-message.chat-message-from-user {
                align-self: flex-end !important;
                margin-left: auto !important;
                max-width: min(680px, 68%) !important;
                border-bottom-right-radius: 6px !important;
                box-shadow: 0 12px 28px rgba(242, 106, 33, 0.18);
            }

            #n8n-chat-page .chat-message-markdown,
            #n8n-chat-page .chat-message-text,
            #n8n-chat-page .message {
                line-height: 1.45 !important;
                white-space: normal;
            }

            #n8n-chat-page .chat-message-markdown::first-line,
            #n8n-chat-page .chat-message-text::first-line {
                font-weight: 700;
            }

            #n8n-chat-page .chat-message-markdown p,
            #n8n-chat-page .chat-message-text p {
                margin: 0 0 0.45rem;
            }

            #n8n-chat-page .chat-message-markdown p:last-child,
            #n8n-chat-page .chat-message-text p:last-child {
                margin-bottom: 0;
            }

            #n8n-chat-page .chat-message-markdown br,
            #n8n-chat-page .chat-message-text br {
                display: none;
            }

            #n8n-chat-page .chat-message-markdown ul,
            #n8n-chat-page .chat-message-markdown ol,
            #n8n-chat-page .chat-message-text ul,
            #n8n-chat-page .chat-message-text ol {
                margin: 0.25rem 0 0.55rem;
                padding-left: 1.2rem;
            }

            #n8n-chat-page .chat-message-markdown li + li,
            #n8n-chat-page .chat-message-text li + li {
                margin-top: 0.15rem;
            }

            #n8n-chat-page .chat-message-markdown strong,
            #n8n-chat-page .chat-message-text strong {
                font-weight: 700;
            }

            #n8n-chat-page .chat-footer {
                border-top: 1px solid #dfe5ee;
                padding: 14px 16px !important;
                background: rgba(255, 255, 255, 0.94) !important;
            }

            .kms-chatbot-actions {
                display: flex;
                justify-content: flex-end;
                margin-top: 10px;
                padding-bottom: 18px;
            }

            @media (max-width: 992px) {
                .kms-chatbot-page {
                    max-width: 100%;
                }

                #n8n-chat-page {
                    height: min(700px, calc(100svh - 170px));
                    min-height: 500px;
                    --chat--header--padding: 18px 20px;
                    --chat--heading--font-size: 1.35rem;
                    --chat--messages-list--padding: 16px;
                }

                #n8n-chat-page .chat-message {
                    max-width: 88% !important;
                }

                #n8n-chat-page .chat-message.chat-message-from-user,
                #n8n-chat-page .chat-message.from-user,
                #n8n-chat-page .chat-message.is-user {
                    max-width: 84% !important;
                }
            }

            @media (max-width: 640px) {
                #n8n-chat-page {
                    height: calc(100svh - 184px);
                    min-height: 390px;
                    --chat--header--padding: 16px 16px;
                    --chat--heading--font-size: 1.2rem;
                    --chat--subtitle--font-size: 0.88rem;
                    --chat--messages-list--padding: 12px;
                }

                .kms-chatbot-shell {
                    border-radius: 14px;
                }

                #n8n-chat-page .chat-message {
                    max-width: 94% !important;
                    font-size: 0.92rem !important;
                }

                #n8n-chat-page .chat-message.chat-message-from-user,
                #n8n-chat-page .chat-message.from-user,
                #n8n-chat-page .chat-message.is-user {
                    max-width: 90% !important;
                }

                #n8n-chat-page .chat-footer {
                    padding: 10px 12px !important;
                }

                .kms-chatbot-actions {
                    justify-content: stretch;
                }

                .kms-chatbot-actions .btn {
                    width: 100%;
                }

                .kms-chatbot-page.is-keyboard-open #n8n-chat-page {
                    height: max(300px, calc(var(--kms-chatbot-visual-height, 100svh) - 96px));
                    min-height: 300px;
                }

                .kms-chatbot-page.is-keyboard-open .kms-chatbot-actions {
                    display: none;
                }

                .kms-chatbot-page.is-keyboard-open #n8n-chat-page .chat-footer {
                    padding-bottom: max(10px, env(safe-area-inset-bottom)) !important;
                }
            }

            #n8n-chat-page textarea {
                min-height: 44px !important;
                padding: 10px 14px !important;
                line-height: 20px !important;
                border: 1px solid #d8dee8 !important;
                border-radius: 12px !important;
                font-family: inherit !important;
                font-size: 0.95rem !important;
                box-shadow: none !important;
            }

            #n8n-chat-page textarea:focus {
                border-color: rgba(242, 106, 33, 0.7) !important;
                outline: 0 !important;
                box-shadow: 0 0 0 4px rgba(242, 106, 33, 0.12) !important;
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

            const chatbotPage = document.querySelector('.kms-chatbot-page');
            const mobileKeyboardQuery = window.matchMedia('(max-width: 640px)');
            const updateVisualHeight = () => {
                const visualHeight = window.visualViewport?.height || window.innerHeight;
                document.documentElement.style.setProperty('--kms-chatbot-visual-height', `${visualHeight}px`);
            };
            const setKeyboardMode = (open) => {
                if (!chatbotPage) {
                    return;
                }

                const shouldOpen = open && mobileKeyboardQuery.matches;
                chatbotPage.classList.toggle('is-keyboard-open', shouldOpen);
                updateVisualHeight();

                if (shouldOpen) {
                    window.setTimeout(() => {
                        document.querySelector('#n8n-chat-page textarea')?.scrollIntoView({
                            block: 'center',
                            behavior: 'smooth',
                        });
                    }, 120);
                }
            };

            updateVisualHeight();
            window.visualViewport?.addEventListener('resize', updateVisualHeight);
            window.visualViewport?.addEventListener('scroll', updateVisualHeight);
            window.addEventListener('orientationchange', () => {
                window.setTimeout(updateVisualHeight, 250);
            });
            document.addEventListener('focusin', (event) => {
                if (event.target instanceof HTMLTextAreaElement && event.target.closest('#n8n-chat-page')) {
                    setKeyboardMode(true);
                }
            });
            document.addEventListener('focusout', (event) => {
                if (!(event.target instanceof HTMLTextAreaElement) || !event.target.closest('#n8n-chat-page')) {
                    return;
                }

                window.setTimeout(() => {
                    const activeElement = document.activeElement;
                    const stillTyping = activeElement instanceof HTMLTextAreaElement
                        && activeElement.closest('#n8n-chat-page');
                    setKeyboardMode(stillTyping);
                }, 120);
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
