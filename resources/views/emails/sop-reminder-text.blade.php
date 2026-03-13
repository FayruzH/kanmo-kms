Hello {{ $sop->pic?->name ?? 'PIC' }},

This is an automated reminder from {{ $appName }}.

Status: {{ strtoupper($statusLabel) }}
SOP Code: {{ $sopCode }}
Title: {{ $sop->title }}
Category: {{ $sop->category?->name ?? '-' }}
Department: {{ $sop->department?->name ?? '-' }}
Expiry Date: {{ $expiryDateLabel }}
Timeline: {{ $dayNote }}

Open SOP: {{ $sopUrl }}

Please review and update this SOP if needed.
