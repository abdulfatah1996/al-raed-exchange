<?php

return array (
  'service_direction' => 
  array (
    'receive' => 'Receive',
    'send' => 'Send',
    'both' => 'Receive & send',
  ),
  'currency_type' => 
  array (
    'fiat' => 'Fiat',
    'crypto' => 'Crypto',
  ),
  'payment_account_type' => 
  array (
    'paypal' => 'PayPal',
    'bank' => 'Bank account',
    'ewallet' => 'E-wallet',
    'crypto_wallet' => 'USDT / crypto wallet',
    'cliq' => 'CliQ',
    'instapay' => 'InstaPay',
    'vodafone_cash' => 'Vodafone Cash',
    'other' => 'Other',
  ),
  'payment_account_status' => 
  array (
    'active' => 'Active',
    'paused' => 'Paused',
    'retired' => 'Retired',
  ),
  'customer_status' => 
  array (
    'active' => 'Active',
    'vip' => 'VIP',
    'blocked' => 'Blocked',
  ),
  'kyc_status' => 
  array (
    'none' => 'Not verified',
    'pending' => 'Pending',
    'verified' => 'Verified',
  ),
  'transfer_type' => 
  array (
    'receive' => 'Receive transfer',
    'send' => 'Send transfer',
    'usdt_withdraw' => 'USDT withdrawal',
    'usdt_deposit' => 'USDT deposit',
    'exchange' => 'Currency exchange',
    'bank_delivery' => 'Bank delivery',
    'wallet_delivery' => 'Wallet delivery',
    'international' => 'International transfer',
    'adjustment' => 'Adjustment',
  ),
  'transfer_status' => 
  array (
    'new' => 'New',
    'pending_pricing' => 'Pending pricing',
    'pending_customer_approval' => 'Pending customer approval',
    'pending_transfer' => 'Pending transfer',
    'pending_verification' => 'Pending verification',
    'under_review' => 'Under review',
    'approved' => 'Approved',
    'in_progress' => 'In progress',
    'delivered' => 'Delivered',
    'cancelled' => 'Cancelled',
    'rejected' => 'Rejected',
    'on_hold' => 'On hold',
  ),
);
