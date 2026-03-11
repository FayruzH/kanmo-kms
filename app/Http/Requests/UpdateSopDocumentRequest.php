<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSopDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:sop_categories,id'],
            'department_id' => ['required', 'exists:sop_departments,id'],
            'entity' => ['nullable', 'string', 'max:255'],
            'source_app_id' => ['nullable', 'exists:sop_source_apps,id'],
            'source_name' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'in:url,file'],
            'url' => ['nullable', 'url', 'required_if:type,url'],
            'file' => ['nullable', 'file', 'max:20480', 'required_if:type,file'],
            'version' => ['required', 'string', 'max:50'],
            'effective_date' => ['required', 'date'],
            'expiry_date' => ['required', 'date'],
            'status' => ['nullable', 'in:active,expiring_soon,expired,archived'],
            'pic_user_id' => ['required', 'exists:users,id'],
            'summary' => ['nullable', 'string'],
            'tags' => ['nullable', 'string'],
        ];
    }
}
