<?php

namespace Modules\Stripe\Http\Controllers;

use App\Abstracts\Http\Controller;
use App\Models\Banking\Account;
use App\Models\Setting\Category;
use Illuminate\Http\Response;
use Modules\Stripe\Http\Requests\Setting as Request;

class Settings extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @return Response
     */
    public function edit()
    {
        $accounts = Account::enabled()->pluck('name', 'id');
        $categories = Category::enabled()->type('income')->pluck('name', 'id');

        return view('stripe::edit', compact('accounts', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  Request $request
     *
     * @return Response
     */
    public function update(Request $request)
    {
        foreach ($request->request as $key => $value) {
            if ($key == '_token' || $key == 'company_id' || $key == '_method') {
                continue;
            }

            setting()->set('stripe.' . $key, $value);
        }

        setting()->save();

        flash(trans('messages.success.updated', ['type' => trans_choice('general.settings', 2)]))->success();

        return response()->json([
            'success' => true,
            'error' => false,
            'redirect' => route('stripe.settings.edit'),
        ]);
    }
}
