<?php

namespace App\Http\Controllers;

use App\Customer;
use \GuzzleHttp\Client;
use Illuminate\Http\Request;

class CustomersController extends Controller
{

    public function index()
    {
        return view('customers');
    }

    public function getShopifyCustomers()
    {
        $response['customers'] = [];
        $response['errors'] = [];
        try{
            $url = 'https://' . env('SHOPIFY_KEY') . ':' . env('SHOPIFY_SECRET') . '@' . env('SHOPIFY_DOMAIN') . '/admin/customers.json';
            $client = new Client();
            $res = $client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-type' => 'application/json'
                ]]);
            $res = $res->getBody()->getContents();
            if (!empty($res)) {
                $res = json_decode($res);

                foreach ($res->customers as $customer){
                    $response['customers'][] = [
                        'id' => $customer->id,
                        'email' => $customer->email,
                        'name' => $customer->first_name . ' ' . $customer->last_name,
                        'phone' => isset($customer->addresses[0]->phone) ? $customer->addresses[0]->phone : null,
                        'country' => isset($customer->addresses[0]->country) ? $customer->addresses[0]->country : null,
                    ];
                }
            }
        }catch (\Exception $exception){
            $response['errors'] = $exception->getMessage();
        }

        return response()->json($response);
    }

    public function getCustomers()
    {
        return response()->json(['customers' => Customer::all()]);
    }

    public function store(Request $request)
    {
        $response['success'] = false;
        $response['errors'] = [];
        try{
            $existedCustomerIds = Customer::pluck('customer_id')->all();
            if (!empty($request->customers)) {
                $bulkInsert = [];
                $bulkUpdate = [];
                foreach ($request->customers as $customer){
                    $customerData = [
                        'customer_id' => $customer['customer_id'],
                        'name' => $customer['name'],
                        'email' => $customer['email'],
                        'phone' => $customer['phone'],
                        'country' => $customer['country'],
                    ];
                    if (empty($existedCustomerIds)) {
                        $bulkInsert[] = $customerData;
                    }else{
                        if (!in_array($customer['customer_id'], $existedCustomerIds)) {
                            $bulkInsert[] = $customerData;
                        }else{
                            $bulkUpdate[$customer['customer_id']] = $customerData;
                        }
                    }
                }
                if (!empty($bulkInsert)) {
                    Customer::insert($bulkInsert);
                }

                if (!empty($bulkUpdate)) {
                    foreach ($bulkUpdate as $customer_id => $customerItem) {
                        $customer = Customer::firstOrNew([
                           'customer_id' => $customer_id
                        ]);
                        $customer->name = $customerItem['name'];
                        $customer->email = $customerItem['email'];
                        $customer->phone = $customerItem['phone'];
                        $customer->country = $customerItem['country'];
                        $customer->save();
                    }
                }
            }
            $response['success'] = true;

        }catch (\Exception $exception){
            $response['errors'] = $exception->getMessage();
        }

        return response()->json($response);
    }

    public function remove(Request $request)
    {
        $response['success'] = false;
        $response['errors'] = [];
        try{
            $customer_id = $request->customer_id;
            if (!empty($customer_id)) {
                $customer = Customer::where('customer_id', '=', $customer_id)->first();
                if (!empty($customer)) {
                    if ($customer->delete()) {
                        $response['success'] = true;
                    }
                } else {
                    $response['errors'][] = "Oops... Something went wrong.";
                }
            }
        }catch (\Exception $exception){
            $response['errors'][] = $exception->getMessage();
        }

        return response()->json($response);
    }
}
