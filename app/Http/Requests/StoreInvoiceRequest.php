<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'business_profile_id' => ['required', 'string'],
            'customer_id' => ['required', 'string'],
            'transaction_type' => ['required', 'string', 'in:sales,purchase'],
            'invoice_date' => ['required', 'date'],
            'status' => ['nullable', 'string', 'in:draft,issued,paid,cancelled,deleted'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.rate' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
