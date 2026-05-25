Hello {{ $picName }},

The following SOP documents require your attention:

@foreach ($sopRows as $index => $row)
{{ $index + 1 }}. {{ $row['title'] }}
   - Status: {{ $row['status_label'] }}
   - Expiry Date: {{ $row['expiry_date_label'] }}
   - Timeline: {{ $row['timeline'] }}
   - Link: {{ $row['sop_url'] }}

@endforeach
Summary:
- Total: {{ $totalCount }}
- Expired: {{ $expiredCount }}
- Expiring Soon: {{ $expiringCount }}

Please review and update these SOPs as needed.

Batch ID: {{ $batchId }}
Generated at: {{ $generatedAt }}
--
{{ $appName }}
