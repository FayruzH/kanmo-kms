<p>Hello {{ $sop->pic?->name ?? 'PIC' }},</p>

<p>
    SOP berikut membutuhkan perhatian:
</p>

<ul>
    <li><strong>Title:</strong> {{ $sop->title }}</li>
    <li><strong>Status:</strong> {{ strtoupper($reminderType) }}</li>
    <li><strong>Expiry Date:</strong> {{ optional($sop->expiry_date)->format('Y-m-d') }}</li>
</ul>

<p>
    Silakan update SOP jika diperlukan.
</p>
