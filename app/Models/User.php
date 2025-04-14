<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Exception;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Stripe;
use Stripe\Transfer;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function vendor()
    {
        return $this->hasOne(Vendor::class, 'user_id');
    }

    public function createStripeAccount($type = 'express')
    {
        Stripe::setApiKey(config('app.stripe_secret_key'));

        $account = Account::create([
            'type' => $type,
            'email' => $this->email,
        ]);
        $this->stripe_account_id = $account->id;
        $this->save();
        return $account;
    }

    public function getStripeAccount()
    {
        Stripe::setApiKey(config('services.stripe.secret'));

        return Account::retrieve($this->stripe_account_id);
    }
    public function getStripeAccountLink()
    {
        Stripe::setApiKey(config('app.stripe_secret_key'));

        $accountLink = AccountLink::create([
            'account' => $this->stripe_account_id,
            'refresh_url' => route('stripe.connect'),
            'return_url' => route('stripe.callback'),
            'type' => 'account_onboarding'
        ]);

        return $accountLink;
    }

    public function transfer($amount, $currency = 'usd')
    {
        if (!$this->stripe_account_id)
        {
            throw new Exception("Vendor doesn't have a connected stripe account.");
        }
        Stripe::setApiKey(config('app.stripe_secret_key'));
        return Transfer::create([
            'amount' => $amount,
            'currency' => $currency,
            'destination' => $this->stripe_account_id,
            'description' => 'Monthly payout',
            'metadata' => [
                'vendor_name' => $this->vendor->store_name
            ]
        ]);
    }
}
