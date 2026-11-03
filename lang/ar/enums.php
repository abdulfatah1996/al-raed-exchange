<?php

return array (
  'service_direction' => 
  array (
    'receive' => 'استقبال',
    'send' => 'إرسال',
    'both' => 'استقبال وإرسال',
  ),
  'currency_type' => 
  array (
    'fiat' => 'عملة تقليدية',
    'crypto' => 'عملة رقمية',
  ),
  'payment_account_type' => 
  array (
    'paypal' => 'PayPal',
    'bank' => 'حساب بنكي',
    'ewallet' => 'محفظة إلكترونية',
    'crypto_wallet' => 'محفظة USDT / كريبتو',
    'cliq' => 'CliQ',
    'instapay' => 'InstaPay',
    'vodafone_cash' => 'Vodafone Cash',
    'other' => 'أخرى',
  ),
  'payment_account_status' => 
  array (
    'active' => 'نشط',
    'paused' => 'متوقف مؤقتًا',
    'retired' => 'مُلغى',
  ),
  'customer_status' => 
  array (
    'active' => 'نشط',
    'vip' => 'VIP',
    'blocked' => 'محظور',
  ),
  'kyc_status' => 
  array (
    'none' => 'بدون تحقق',
    'pending' => 'قيد التحقق',
    'verified' => 'موثّق',
  ),
  'transfer_type' => 
  array (
    'receive' => 'استقبال حوالة',
    'send' => 'إرسال حوالة',
    'usdt_withdraw' => 'سحب USDT',
    'usdt_deposit' => 'إيداع USDT',
    'exchange' => 'تحويل بين عملتين',
    'bank_delivery' => 'تسليم بنكي',
    'wallet_delivery' => 'تسليم محفظة',
    'international' => 'حوالة دولية',
    'adjustment' => 'تسوية / تعديل موثّق',
  ),
  'transfer_status' => 
  array (
    'new' => 'جديدة',
    'pending_pricing' => 'بانتظار التسعير',
    'pending_customer_approval' => 'بانتظار موافقة العميل',
    'pending_transfer' => 'بانتظار التحويل',
    'pending_verification' => 'بانتظار التشييك',
    'under_review' => 'قيد المراجعة',
    'approved' => 'تم الاعتماد',
    'in_progress' => 'قيد التنفيذ',
    'delivered' => 'تم التسليم',
    'cancelled' => 'ملغاة',
    'rejected' => 'مرفوضة',
    'on_hold' => 'معلّقة',
  ),
);
