<?php

namespace App\Features\Orders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'symbol' => 'required|string|in:BTC-USD,ETH-USD',
            'side' => 'required|string|in:buy,sell',
            'price' => 'required|numeric|min:0.00000001',
            'amount' => 'required|numeric|min:0.00000001',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'symbol.required' => 'Trading symbol is required',
            'symbol.in' => 'Invalid trading symbol',
            'side.required' => 'Order side is required',
            'side.in' => 'Order side must be either buy or sell',
            'price.required' => 'Price is required',
            'price.min' => 'Price must be greater than 0',
            'amount.required' => 'Amount is required',
            'amount.min' => 'Amount must be greater than 0',
        ];
    }
}
