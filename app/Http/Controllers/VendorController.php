<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\RolesEnum;
use App\VendorStatusEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VendorController extends Controller
{
    public function profile(Vendor $vendor)
    {
        
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $request->validate([
            'store_name' => ['required', 
                             'regex:/[a-z0-9-]+$/', 
                             Rule::unique('vendors', 'store_name')->ignore($user->id, 'user_id')
                            ],
            'store_address' => 'nullable'
        ],[
            'store_name.regex' => 'Store Name must only contains lowercase alphanumeric characters and dashes.'
        ]);
        $vendor = $user->vendor ?: new Vendor();
        $vendor->user_id = $user->id;
        $vendor->status = VendorStatusEnum::Approved->value;
        $vendor->store_name = $request->store_name;
        $vendor->store_address = $request->store_address;
        $vendor->save();

        $user->assignRole(RolesEnum::Vendor);
    }
}
