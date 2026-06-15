<?php

namespace App\Http\Controllers\Api;

use App\Features\Payments\Controllers\ManualPaymentMethodController;
use App\Features\Payments\Controllers\MemberMembershipController;
use App\Features\Payments\Controllers\MemberPaymentController;
use App\Http\Requests\Common\PublicListRequest;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends MemberPaymentController
{
    public function manualMethods(PublicListRequest $request)
    {
        return app(ManualPaymentMethodController::class)->index($request);
    }

    public function pay(Request $request)
    {
        return $this->simulatePay($request);
    }

    public function pendingRenewals(Request $request)
    {
        return app(MemberMembershipController::class)->pendingRenewals($request);
    }

    public function confirmRenewal(Request $request, Payment $payment)
    {
        return app(MemberMembershipController::class)->confirmRenewal($request, $payment);
    }

    public function rejectRenewal(Request $request, Payment $payment)
    {
        return app(MemberMembershipController::class)->rejectRenewal($request, $payment);
    }
}
