<?php

namespace Modules\Stripe\Http\Requests;

use App\Abstracts\Http\FormRequest as Request;

class Card extends Request
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
            'cardNumber' => 'required',
            'cardName'   => 'required',
            'cardMonth' => 'required',
            'cardYear' => 'required',
            'cardCvv'    => 'required'
        ];
    }

    public function messages()
    {
        return [
            'card_expiry.min' => trans('stripe::general.error.card_expiry.min'),
        ];
    }
}
