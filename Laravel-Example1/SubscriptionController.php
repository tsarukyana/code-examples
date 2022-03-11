<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Refefile;
use Stripe\StripeObject;
use App\Models\Subscription;
use Illuminate\Http\Request;
use App\Models\ManageAccount;
use App\Models\PaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPrice;
use Illuminate\Http\JsonResponse;
use App\Models\SubscriptionContent;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\SubscriptionFailed;
use Illuminate\Contracts\View\Factory;
use App\Exceptions\InvalidPromotionCode;
use Stripe\Exception\ApiErrorException;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Requests\ValidateInputRequest;
use Illuminate\Contracts\Foundation\Application;


class SubscriptionController extends Controller
{
    /**
     * The ID of promotion code object.
     *
     * @var string|null
     */
    protected $promoCodeId;

    /**
     * The promotion code object.
     *
     * @var StripeObject
     */
    protected $promoCode;

    /**
     * The selected price model.
     *
     * @var SubscriptionPrice
     */
    protected $priceModel;

    /**
     * Set promotion code id.
     *
     * @param string $id
     */
    private function setPromoCodeId(string $id)
    {
        $this->promoCodeId = $id;
    }

    /**
     * Return promotion code id.
     *
     * @return string|null
     */
    private function getPromoCodeId(): ?string
    {
        return $this->promoCodeId;
    }

    /**
     * Set promo code object.
     *
     * @param StripeObject $object
     */
    private function setPromoCodeObject(StripeObject $object)
    {
        $this->promoCode = $object;
    }

    /**
     * Return promo code object.
     *
     * @return StripeObject
     */
    private function getPromoCodeObject(): StripeObject
    {
        return $this->promoCode;
    }

    /**
     * Initialization of the price model.
     *
     * @param SubscriptionPrice $model
     */
    private function setPriceModel(SubscriptionPrice $model)
    {
        $this->priceModel = $model;
    }

    /**
     * @return array|false|Application|Factory|View|mixed
     */
    public function manageSubscription()
    {
        return view('frontend-new.subscription.manage-subscription');
    }

    /**
     * Get all available subscription plans.
     *
     * @return JsonResponse
     */
    public function getAllPlans(): JsonResponse
    {
        $subscriptionPlans = SubscriptionPlan::where('status', '1')->with('subscriptionPrices')->get();
        return response()->json(['status' => true, 'plans' => $subscriptionPlans]);
    }

    /**
     * @return JsonResponse
     */
    public function getSubscriptionPlans(): JsonResponse
    {
        $plans = [];
        $subscriptionPlans = SubscriptionPlan::where('status', '1')->with('subscriptionPrices')->get();
        $subscribedPlan = Subscription::where('user_id', Auth::id())->where('stripe_status', 'active')->first();
        if (!$subscriptionPlans->count()) return response()->json(['status' => true, 'plans' => []]);
        $usageMemory = Refefile::where('created_by', Auth::id())->sum('content_length');
        $subscriptionContent = SubscriptionContent::where('subscription_name', $subscribedPlan->name)->first();
        $usedIntegrationsCount = ManageAccount::where('user_id', Auth::id())->count();

        foreach ($subscriptionPlans as $plan) {
            $plans[] = [
                'name' => $plan->name,
                'status' => $plan->name === $subscribedPlan->name,
                'interval' => $plan->subscriptionPrices[0]->interval,
                'price' => '$' . $plan->subscriptionPrices[0]->price,
                'expires' => $plan->name === $subscribedPlan->name  ? Carbon::parse($subscribedPlan->created_at)->addMonth()->format('F d, Y h:ia') : '',
                'closed' => !!$subscribedPlan->ends_at,
                'auto_subscribe' => (int)$subscribedPlan->auto_subscribe,
                'promo_code' => $subscribedPlan->coupon,
                'details' => [
                    'subscription_id' => $subscribedPlan->id,
                    'metadata' => $plan->metadata,
                    'last_payment' => Carbon::parse($subscribedPlan->created_at)->format('F d, Y h:ia'),
                    'next_payment' => Carbon::parse($subscribedPlan->created_at)->addMonth()->format('F d, Y h:ia'),
                    'payment_details' => [
                        'pm_type' => Auth::user()->pm_type,
                        'last_four' =>  Auth::user()->pm_last_four
                    ],
                    'used_content' => [
                        'memory' => $usageMemory <= $subscriptionContent->memory ? ceil(($usageMemory * 100) / $subscriptionContent->memory) : 100,
                        'integration' => $usedIntegrationsCount <= $subscriptionContent->integrations ? ceil(($usedIntegrationsCount * 100) / $subscriptionContent->integrations): 100,
                        'team_members' => 15,
                        'brand' => 35
                    ]
                ]
            ];
        }

        return response()->json(['status' => true, 'plans' => $plans]);
    }

    /**
     * Creates an intent for payment, so we can capture the payment
     * method for the user.
     *
     * @param Request $request
     * @return mixed
     */
    public function loadIntent(Request $request)
    {
        return $request->user()->createSetupIntent();
    }

    /**
     * Adds a payment method to the current user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePaymentMethods(Request $request): JsonResponse
    {
        $paymentMethodID = $request->get('payment_method');
        $user = $request->user();
        $user->createOrGetStripeCustomer();
        $user->addPaymentMethod($paymentMethodID);
        return response()->json(['status' => true, 'message' => 'Payment method added successfully.']);
    }

    /**
     * @param SubscriptionRequest $request
     * @return JsonResponse
     * @throws SubscriptionFailed
     * @throws InvalidPromotionCode
     */
    public function subscribe(SubscriptionRequest $request): JsonResponse
    {
        $inputData = $request->validated();

        if (isset($request['time_zone']) && !is_null($request['time_zone'])) date_default_timezone_set($request['time_zone']);

        try {
            if ($inputData['promo_code'] === config('constant.free_promo_code')) {
                User::where('id', Auth::id())->update(['free_promo_code' => $inputData['promo_code']]);
            } else {
                $this->validatePromoCode($inputData['promo_code'], $inputData['selected_price_id']);
            }

            $user = $request->user();
            $user->createOrGetStripeCustomer();

            $subscription = $user->newSubscription($inputData['selected_plan_name'], $inputData['selected_price_id'])
                ->withPromotionCode($this->getPromoCodeId()) // Promo code may be empty.
                ->create($inputData['payment_method_id']);

            $user->updateDefaultPaymentMethod($inputData['payment_method_id']);
            $this->savePaymentMethod($inputData['payment_method_id']);

            Subscription::whereId($subscription->id)
                ->update(
                    [
                        'auto_subscribe' => '1',
                        'ends_at' => Carbon::parse($subscription->created_at)->addMonth()
                    ]
                );
        } catch (\Exception | InvalidPromotionCode $e) {
            switch ($e) {
                case $e instanceof InvalidPromotionCode:
                    throw new InvalidPromotionCode($e->getMessage());
                default:
                    throw new SubscriptionFailed('Subscription Failed:');
            }
        }

        return response()->json(['message' => 'Subscription plan selected successfully.']);
    }

    /**
     * Get promo code ID.
     * Return false if promo code does not exist.
     *
     * @param $code
     * @param $priceId
     * @return void
     * @throws ApiErrorException
     * @throws InvalidPromotionCode
     */
    private function validatePromoCode($code, $priceId)
    {
        if ($code) {
            $promoCodes = $this->getPromoCode($code, $priceId);
            $this->setPromoCodeId($promoCodes->id);
        }
    }

    /**
     * Get promotion code id using code.
     *
     * @param $code
     * @param $priceId
     * @return StripeObject|null
     * @throws ApiErrorException
     * @throws InvalidPromotionCode
     */
    private function getPromoCode($code, $priceId): ?StripeObject
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $this->isPromoCodeExist(array_shift($stripe->promotionCodes->all(['active' => true, 'code' => $code])->data));
        $this->setPriceModel(SubscriptionPrice::where('price_id', $priceId)->first(['price', 'product_id']));
        $this->isPromoCodeAppliedToSpecificPlan($stripe);
        $this->checkMinimumPriceForPromoCode();
        $this->isPromoCodeAppliedToSpecificCustomer();
        return $this->getPromoCodeObject();
    }

    /**
     * Check if promo code exist.
     * Null will be returned even if the promo code is inactive.
     *
     * @throws InvalidPromotionCode
     */
    private function isPromoCodeExist($object)
    {
        if (!$object) throw new InvalidPromotionCode('No such promotion code.');
        $this->setPromoCodeObject($object);
    }

    /**
     * The coupon can be applied to a specific subscription plan.
     *
     * @param $stripe
     * @throws InvalidPromotionCode
     */
    private function isPromoCodeAppliedToSpecificPlan($stripe)
    {
        $coupon = $stripe->coupons->retrieve($this->getPromoCodeObject()->coupon['id'], ['expand' => ['applies_to']]);

        if (isset($coupon->applies_to) && count($coupon->applies_to['products']) && !in_array($this->priceModel->product_id, $coupon->applies_to['products']))
            throw new InvalidPromotionCode('This promotion code applied for another plan.');
    }

    /**
     * Checking the possibility of using a promotional code at the current price.
     *
     * @throws InvalidPromotionCode
     */
    private function checkMinimumPriceForPromoCode()
    {
        if ($this->priceModel && (floatval($this->priceModel->price) < ($this->getPromoCodeObject()->restrictions->minimum_amount / 100)))
            throw new InvalidPromotionCode(
                'The required minimum subscription price for this promo code is $'
                . ($this->getPromoCodeObject()->restrictions->minimum_amount / 100) . '.'
            );
    }

    /**
     * The promotion code can be applied to a specific customer.
     *
     * @throws InvalidPromotionCode
     */
    private function isPromoCodeAppliedToSpecificCustomer()
    {
        if ($this->getPromoCodeObject()->customer && $this->getPromoCodeObject()->customer !== Auth::user()->stripe_id)
            throw new InvalidPromotionCode('This promotion code is specified for another user.');
    }

    /**
     * Update or create payment method for user using fingerprint.
     * The fingerprint is always the same for the same payment method.
     *
     * @param $id
     * @param string $setDefault
     * @throws ApiErrorException
     */
    private function savePaymentMethod($id, string $setDefault = '1')
    {
        $paymentMethod = $this->getPaymentMethod($id);

        PaymentMethod::updateOrCreate(
            ['fingerprint' => $paymentMethod->card['fingerprint']],
            $this->buildPayloadForPaymentMethod($paymentMethod, $setDefault)
        );
    }

    /**
     * Get payment method object using payment method ID.
     *
     * @param $id
     * @return \Stripe\PaymentMethod
     * @throws ApiErrorException
     */
    private function getPaymentMethod($id): \Stripe\PaymentMethod
    {
        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        return $stripe->paymentMethods->retrieve($id, []);
    }

    /**
     * Create array from payment method object.
     *
     * @param $payload
     * @param $setDefault
     * @return array
     */
    private function buildPayloadForPaymentMethod($payload, $setDefault): array
    {
        return [
            'user_id' => Auth::id(),
            'payment_method_id' => $payload->id,
            'valid' => '1',
            'brand' => ucfirst($payload->card['brand']),
            'last4' => $payload->card['last4'],
            'exp_month' => $payload->card['exp_month'],
            'exp_year' => $payload->card['exp_year'],
            'default' => $setDefault
        ];
    }

    /**
     * @param ValidateInputRequest $request
     * @return JsonResponse
     */
    public function cancelSubscription(ValidateInputRequest $request): JsonResponse
    {
        $input = $request->validated();
        Subscription::where('user_id', Auth::id())->where('name', $input['plan_name'])->update(['auto_subscribe' => '0']);
        return response()->json(['status' => true, 'message' => 'Subscription canceled successfully.']);
    }

    /**
     * @param ValidateInputRequest $request
     * @return JsonResponse
     */
    public function resumeSubscription(ValidateInputRequest $request): JsonResponse
    {
        $input = $request->validated();
        Subscription::where('user_id', Auth::id())->where('name', $input['plan_name'])->update(['auto_subscribe' => '1']);
        return response()->json(['status' => true, 'message' => 'Subscription resumed successfully.']);
    }

    /**
     * @param ValidateInputRequest $request
     * @return JsonResponse
     */
    public function savePromoCode(ValidateInputRequest $request): JsonResponse
    {
        $input = $request->validated();
        Subscription::where('user_id', Auth::id())->where('name', $input['plan_name'])->update(['coupon' => $input['promo_code']]);
        return response()->json(['status' => true, 'message' => 'Promotion code saved successfully']);
    }

    /**
     * Get all payment methods.
     *
     * @return JsonResponse
     */
    public function getPaymentMethods(): JsonResponse
    {
        $paymentMethods = PaymentMethod::where('user_id', Auth::id())->orderBy('id', 'asc')->get(
            ['id', 'exp_month', 'exp_year', 'last4', 'brand', 'valid', 'default']
        );
        return response()->json(['payment_methods' => $paymentMethods]);
    }

    /**
     * @throws ApiErrorException
     */
    public function addPaymentMethods(Request $request): JsonResponse
    {
        $timeZone = $request->input('time_zone');
        if (isset($timeZone) && !is_null($request['time_zone'])) date_default_timezone_set($timeZone);
        $paymentMethodId = $request->input('payment_method_id');
        $request->user()->addPaymentMethod($paymentMethodId);
        $this->savePaymentMethod($paymentMethodId, '0');
        $newPaymentMethod = PaymentMethod::where('user_id', Auth::id())->where('payment_method_id', $paymentMethodId)->first(
            ['id', 'exp_month', 'exp_year', 'last4', 'brand', 'valid', 'default']
        );
        return response()->json(['status' => true, 'new_payment_method' => $newPaymentMethod, 'message' => 'Payment method added successfully']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updateDefaultPaymentMethods(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $paymentMethod = PaymentMethod::find($id);
        $request->user()->updateDefaultPaymentMethod($paymentMethod->payment_method_id);
        PaymentMethod::where('id', '!=', $id)->update(['default' => '0']);
        PaymentMethod::where('id', $id)->update(['default' => '1']);
        return response()->json(['status' => true, 'message' => 'Default payment method updated successfully']);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deletePaymentMethod(Request $request): JsonResponse
    {
        $id = $request->input('id');
        $paymentMethod = PaymentMethod::find($id);
        $paymentMethod = $request->user()->findPaymentMethod($paymentMethod->payment_method_id);
        $paymentMethod->delete();
        PaymentMethod::where('id', $id)->delete();
        return response()->json(['status' => true, 'message' => 'Payment method deleted successfully']);
    }

    /**
     * Get and show all invoices.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getInvoices(Request $request): JsonResponse
    {
        $user = $request->user();
        $invoice = $user->invoices();
        $invoices = [];

        foreach ($invoice as $item) {
            $invoices[] = [
                'invoice_number' => $item->number,
                'created' => Carbon::parse($item->created)->format('F d, Y'),
                'subtotal' => '$' . $item->subtotal / 100,
                'discount_percent' => ($item->subtotal - $item->total) * 100 / $item->subtotal,
                'discount_amount' => ($item->subtotal - $item->total) / 100,
                'total' => '$' . $item->total / 100,
                'invoice_url' => $item->hosted_invoice_url,
                'invoice_pdf' => $item->invoice_pdf
            ];
        }

        return response()->json(['invoices' => $invoices]);
    }
}
