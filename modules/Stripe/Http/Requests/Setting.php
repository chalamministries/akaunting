<?php

namespace Modules\Stripe\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class Setting extends Request
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'account_id' => 'required|integer',
            'category_id' => 'required|integer',
            'customer' => 'integer|boolean',
            'name' => 'required|string',
            'secret_key' => 'required|string',
            'sync' => 'integer|boolean',
            'order' => 'required|string',
        ];
    }
}
