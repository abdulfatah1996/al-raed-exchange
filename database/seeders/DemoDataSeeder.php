<?php

namespace Database\Seeders;

use App\Enums\AnnouncementType;
use App\Enums\TransferStatus;
use App\Models\Announcement;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\DeliveryMethod;
use App\Models\PaymentAccount;
use App\Models\Receipt;
use App\Models\Service;
use App\Models\Transfer;
use App\Models\TransferRequest;
use App\Models\User;
use App\Support\Permissions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Realistic demo data for trying the system. Every record is created through the
 * same model rules staff use (state machine, receipt checks, locks), so the status
 * history, verification and delivery records are all consistent.
 *
 * Phone numbers, accounts and transaction IDs are fictitious and marked as demo.
 * Run via `php artisan alraed:demo-data`, never on its own.
 */
class DemoDataSeeder
{
    protected User $admin;

    /** @var array<string, User> */
    protected array $staff = [];

    /** @var array<string, Customer> */
    protected array $customers = [];

    /** @var array<string, PaymentAccount> */
    protected array $accounts = [];

    protected Carbon $clock;

    protected int $ref = 1000;

    protected const PATH = [
        TransferStatus::PendingPricing, TransferStatus::PendingCustomerApproval, TransferStatus::PendingTransfer,
        TransferStatus::PendingVerification, TransferStatus::UnderReview, TransferStatus::Approved,
        TransferStatus::InProgress, TransferStatus::Delivered,
    ];

    /** @return array<string, int|string> */
    public function runFor(User $admin): array
    {
        $this->admin = $admin;
        $realNow = now()->toImmutable();

        try {
            Auth::setUser($admin);
            $this->staff();
            $this->customers();
            $this->accounts();
            $this->transfers($realNow);
            $this->announcements($realNow);
            Carbon::setTestNow();
            $this->websiteRequests($realNow);
        } finally {
            Carbon::setTestNow();
            Auth::setUser($admin);
        }

        return [
            'Demo staff (inactive)' => count($this->staff),
            'Customers' => Customer::count(),
            'Receiving accounts' => PaymentAccount::count(),
            'Transfers' => Transfer::count(),
            'Receipts' => Receipt::count(),
            'Website requests' => TransferRequest::count(),
            'Announcements' => Announcement::count(),
        ];
    }

    // ── People ───────────────────────────────────────────────

    protected function staff(): void
    {
        foreach ([
            'supervisor' => ['سامي خالد (تجريبي)', Permissions::ROLE_SUPERVISOR],
            'employee' => ['رنا يوسف (تجريبي)', Permissions::ROLE_EMPLOYEE],
            'cashier' => ['محمود علي (تجريبي)', Permissions::ROLE_CASHIER],
            'viewer' => ['لينا حسن (تجريبي)', Permissions::ROLE_VIEWER],
        ] as $key => [$name, $role]) {
            $user = User::query()->where('email', "demo-{$key}@alraed.local")->first();
            if (! $user) {
                $user = new User;
                $user->forceFill([
                    'name' => $name, 'email' => "demo-{$key}@alraed.local",
                    'password' => Str::password(32), 'is_active' => false, 'locale' => 'ar',
                ])->save();
            }
            $user->syncRoles([$role]);
            $this->staff[$key] = $user;
        }
    }

    protected function customers(): void
    {
        $this->at(now()->subDays(20));

        foreach ([
            'ahmad' => ['أحمد محمود الشريف', '+970599001101', 'PS', 'vip', 'verified', 'تاجر، يحوّل USDT أسبوعيًا بمبالغ كبيرة.'],
            'sara' => ['سارة خليل', '+970599001102', 'PS', 'active', 'verified', null],
            'yousef' => ['يوسف عبد الله', '+962790001103', 'JO', 'active', 'pending', 'يرسل من الأردن عبر CliQ.'],
            'mona' => ['منى حسن', '+201000001104', 'EG', 'active', 'none', null],
            'karim' => ['كريم نصار', '+4915100001105', 'DE', 'active', 'verified', 'يرسل باليورو عبر SEPA.'],
            'omar' => ['عمر درويش', '+905300001106', 'TR', 'active', 'pending', null],
            'huda' => ['هدى سليمان', '+963930001107', 'SY', 'active', 'none', null],
            'tarek' => ['طارق منصور', '+12025550108', 'US', 'active', 'verified', null],
            'layla' => ['ليلى عيسى', '+970599001109', 'PS', 'active', 'none', null],
            'nader' => ['نادر الحلو', '+970599001110', 'PS', 'blocked', 'none', 'موقوف: محاولة استخدام إيصال سبق اعتماده.'],
        ] as $key => [$name, $phone, $country, $status, $kyc, $note]) {
            $this->tick(37);
            $customer = Customer::create([
                'name' => $name, 'phone' => $phone, 'country_id' => $this->country($country),
                'status' => $status, 'kyc_status' => $kyc, 'notes' => 'بيانات تجريبية',
            ]);
            if ($note) {
                CustomerNote::create(['customer_id' => $customer->id, 'user_id' => $this->admin->id, 'note' => $note]);
            }
            $this->customers[$key] = $customer;
        }
    }

    protected function accounts(): void
    {
        foreach ([
            'paypal' => ['paypal', 'PayPal رئيسي (تجريبي)', 'paypal_receive', null, 'USD', ['email' => 'demo-paypal@example.com', 'paypal_me' => 'https://paypal.me/example'], 20, 2000],
            'usdt' => ['crypto_wallet', 'محفظة USDT TRC20 (تجريبي)', 'usdt_withdraw', null, 'USDT', ['network' => 'TRC20', 'address' => 'TDEMO000000000000000000000000000AR'], 50, 50000],
            'cliq' => ['cliq', 'CliQ الأردن (تجريبي)', 'jordan_cliq', 'JO', 'JOD', ['alias' => 'DEMOALRAED', 'phone' => '+962790000000'], 10, 3000],
            'vodafone' => ['vodafone_cash', 'Vodafone Cash 1 (تجريبي)', 'egypt_vodafone_cash', 'EG', 'EGP', ['phone' => '+201000000000'], 500, 60000],
            'instapay' => ['instapay', 'InstaPay (تجريبي)', 'egypt_instapay', 'EG', 'EGP', ['address' => 'demo@instapay', 'phone' => '+201000000001'], 500, 60000],
            'sepa' => ['bank', 'حساب يورو SEPA (تجريبي)', 'eu_sepa', 'DE', 'EUR', ['bank_name' => 'Demo Bank', 'iban' => 'DE00000000000000000000', 'swift' => 'DEMODEFF'], 50, 10000],
            'turkey' => ['bank', 'حساب تركي (تجريبي)', 'turkey_bank', 'TR', 'TRY', ['bank_name' => 'Demo Bankası', 'iban' => 'TR000000000000000000000000'], 1000, 300000],
        ] as $key => [$type, $name, $service, $country, $currency, $details, $min, $max]) {
            $this->accounts[$key] = PaymentAccount::create([
                'type' => $type, 'internal_name' => $name, 'service_id' => $this->service($service),
                'country_id' => $country ? $this->country($country) : null, 'currency_id' => $this->currency($currency),
                'beneficiary_name' => 'الرائد للصرافة (تجريبي)', 'details' => $details, 'status' => 'active',
                'min_amount' => $min, 'max_amount' => $max, 'instructions_ar' => 'حساب تجريبي — لا تحوّل إليه.',
            ]);
        }

        // A stopped number, to show how a replaced receiving account looks.
        PaymentAccount::create([
            'type' => 'vodafone_cash', 'internal_name' => 'Vodafone Cash القديم (موقوف، تجريبي)', 'service_id' => $this->service('egypt_vodafone_cash'),
            'country_id' => $this->country('EG'), 'currency_id' => $this->currency('EGP'), 'details' => ['phone' => '+201000000099'],
            'status' => 'retired', 'notes' => 'استُبدل بالرقم الجديد.',
        ]);
    }

    // ── Transfers ────────────────────────────────────────────

    protected function transfers(\DateTimeInterface $realNow): void
    {
        $specs = [
            ['ahmad', 4, 'usdt_withdraw', null, 'usdt_withdraw', [1000, 'USDT'], [3620, 'ILS'], 3.62, 'bank_of_palestine_ils', 'usdt', 'Delivered', 'employee', [[1000, 'USDT']]],
            ['tarek', 2, 'paypal_receive', 'US', 'receive', [250, 'USD'], [230, 'USD'], 0.92, 'bank_of_palestine_usd', 'paypal', 'Delivered', 'employee', [[150, 'USD'], [100, 'USD']]],
            ['yousef', 1, 'jordan_cliq', 'JO', 'receive', [300, 'JOD'], [1560, 'ILS'], 5.2, 'ewallet_ps', 'cliq', 'InProgress', 'cashier', [[200, 'JOD'], [100, 'JOD']], 1],
            ['mona', 1, 'egypt_vodafone_cash', 'EG', 'receive', [5000, 'EGP'], [98, 'USD'], 0.0196, 'cash', 'vodafone', 'Approved', 'cashier', [[5000, 'EGP']]],
            ['karim', 0, 'eu_sepa', 'DE', 'receive', [800, 'EUR'], [3240, 'ILS'], 4.05, 'bank_of_palestine_ils', 'sepa', 'UnderReview', 'supervisor', [[800, 'EUR']]],
            ['omar', 0, 'turkey_bank', 'TR', 'receive', [15000, 'TRY'], [1590, 'ILS'], 0.106, 'bank_of_palestine_ils', 'turkey', 'PendingVerification', 'employee', [[10000, 'TRY'], [5000, 'TRY']]],
            ['sara', 0, 'usdt_deposit', null, 'usdt_deposit', [2000, 'ILS'], [540, 'USDT'], 0.27, 'usdt', 'usdt', 'PendingTransfer', 'employee', []],
            ['tarek', 0, 'intl_western_union', 'US', 'receive', [400, 'USD'], [385, 'USD'], 0.9625, 'cash', null, 'PendingCustomerApproval', 'employee', []],
            ['huda', 0, 'syria_shamcash', 'SY', 'send', [150, 'USD'], null, null, null, null, 'PendingPricing', 'employee', []],
            ['mona', 3, 'egypt_instapay', 'EG', 'receive', [3000, 'EGP'], [59, 'USD'], 0.0196, 'cash', 'instapay', 'Rejected', 'employee', [[3000, 'EGP']]],
            ['layla', 2, 'intl_moneygram', null, 'receive', [600, 'USD'], [580, 'USD'], 0.9667, 'cash', null, 'OnHold', 'employee', [], 'PendingTransfer'],
            ['nader', 5, 'intl_ria', null, 'receive', [900, 'USD'], [870, 'USD'], 0.9667, 'cash', null, 'Cancelled', null, [], 'PendingCustomerApproval'],
            ['ahmad', 0, 'usdt_withdraw', null, 'usdt_withdraw', [2500, 'USDT'], [2475, 'USD'], 0.99, 'bank_of_palestine_usd', 'usdt', 'Delivered', 'cashier', [[2500, 'USDT']]],
        ];

        foreach ($specs as $i => $spec) {
            $this->transfer($i, $spec, $realNow);
        }
    }

    protected function transfer(int $i, array $s, \DateTimeInterface $realNow): void
    {
        [$customer, $daysAgo, $service, $country, $type, $send, $deliver, $rate, $method, $account, $targetName, $assignee, $receipts] = $s;
        $extra = $s[13] ?? null;
        $target = constant(TransferStatus::class . '::' . $targetName);

        $steps = match ($target) {
            TransferStatus::Rejected => 5, TransferStatus::OnHold, TransferStatus::Cancelled => array_search(constant(TransferStatus::class . '::' . $extra), self::PATH, true) + 2,
            default => array_search($target, self::PATH, true) + 1,
        };
        $start = $daysAgo > 0
            ? Carbon::parse($realNow)->subDays($daysAgo)->setTime(9 + $i % 5, 5 + 7 * ($i % 6))
            : Carbon::parse($realNow)->subMinutes(55 * ($steps + 1) + 9 * $i);
        $this->at($start);

        $sendCurrency = $this->currency($send[1]);
        $transfer = Transfer::create([
            'customer_id' => $this->customers[$customer]->id, 'service_id' => $this->service($service),
            'country_id' => $country ? $this->country($country) : null, 'type' => $type,
            'send_amount' => $send[0], 'send_currency_id' => $sendCurrency,
            'deliver_amount' => $deliver[0] ?? null, 'deliver_currency_id' => isset($deliver[1]) ? $this->currency($deliver[1]) : null,
            'rate' => $rate, 'fee_amount' => $deliver ? 0 : null,
            'delivery_method_id' => $method ? DeliveryMethod::where('code', $method)->value('id') : null,
            'payment_account_id' => $account ? $this->accounts[$account]->id : null,
            'sender_name' => $this->customers[$customer]->name,
            'beneficiary_name' => $this->customers[$customer]->name,
            'beneficiary_details' => $method === 'usdt' ? ['wallet' => 'TDEMO-WALLET-' . $i] : ['bank_name' => 'بنك فلسطين', 'account' => 'DEMO-' . (40000 + $i)],
            'assigned_user_id' => $assignee ? $this->staff[$assignee]->id : null,
            'internal_notes' => 'حوالة تجريبية لعرض النظام.',
        ]);

        $walk = match ($target) {
            TransferStatus::Rejected => array_slice(self::PATH, 0, 4),
            TransferStatus::OnHold, TransferStatus::Cancelled => array_slice(self::PATH, 0, array_search(constant(TransferStatus::class . '::' . $extra), self::PATH, true) + 1),
            default => array_slice(self::PATH, 0, array_search($target, self::PATH, true) + 1),
        };

        foreach ($walk as $to) {
            if ($to === TransferStatus::PendingVerification) {
                foreach ($receipts as $n => [$amount, $code]) {
                    $this->tick(12);
                    $this->receipt($transfer, $amount, $code, $rate, $deliver, $account, $method, $i, $n);
                }
            }

            if ($to === TransferStatus::Approved) {
                foreach ($transfer->receipts()->get() as $receipt) {
                    $this->tick(8);
                    $receipt->verify('تم التأكد من وصول المبلغ في كشف الحساب.');
                }
            }

            $this->tick(25 + 11 * ($i % 4));
            $transfer->transitionTo($to, $this->noteFor($to));

            if ($to === TransferStatus::InProgress) {
                $deliverCount = $extra === 1 ? 1 : PHP_INT_MAX;
                foreach ($transfer->receipts()->get()->take($deliverCount) as $receipt) {
                    $this->tick(15);
                    $receipt->markDelivered();
                }
                if ($target === TransferStatus::Delivered) {
                    $transfer->update(['delivery_proof_file' => $this->file('proofs', "DELIVERY PROOF {$transfer->reference}")]);
                }
            }
        }

        if ($target === TransferStatus::Rejected) {
            $this->tick(30);
            $transfer->receipts()->first()?->reject('لم يصل المبلغ إلى الحساب، ورقم المعاملة غير موجود في الكشف.');
            $this->tick(5);
            $transfer->transitionTo(TransferStatus::Rejected, 'رُفض الوصل: المبلغ غير موجود في كشف الحساب.');
        }

        if (in_array($target, [TransferStatus::OnHold, TransferStatus::Cancelled], true)) {
            $this->tick(90);
            $transfer->transitionTo($target, $target === TransferStatus::OnHold ? 'العميل طلب تأجيل التحويل يومين.' : 'العميل ألغى الطلب بعد عرض السعر.');
        }
    }

    protected function receipt(Transfer $transfer, float $amount, string $code, ?float $rate, ?array $deliver, ?string $account, ?string $method, int $i, int $n): void
    {
        $txid = $code === 'USDT'
            ? 'DEMO' . strtoupper(substr(hash('sha256', "tx-$i-$n"), 0, 40))
            : 'DEMO-' . $code . '-' . (++$this->ref);

        Receipt::create([
            'transfer_id' => $transfer->id, 'amount' => $amount, 'currency_id' => $this->currency($code),
            'payment_account_id' => $account ? $this->accounts[$account]->id : null,
            'rate' => $rate, 'deliver_amount' => $rate ? round($amount * $rate, 2) : null,
            'deliver_currency_id' => isset($deliver[1]) ? $this->currency($deliver[1]) : null,
            'delivery_method_id' => $method ? DeliveryMethod::where('code', $method)->value('id') : null,
            'external_ref' => $txid, 'sender_name' => $transfer->sender_name,
            'receipt_file' => $this->file('receipts', "RECEIPT {$transfer->reference} #" . ($n + 1)),
        ]);
    }

    protected function noteFor(TransferStatus $to): ?string
    {
        return match ($to) {
            TransferStatus::PendingCustomerApproval => 'أُرسل عرض السعر للعميل على واتساب.',
            TransferStatus::PendingTransfer => 'العميل وافق على السعر، أُرسل حساب الاستقبال.',
            TransferStatus::PendingVerification => 'وصلت الإيصالات من العميل.',
            TransferStatus::Approved => 'كل الوصولات مُشيّكة.',
            TransferStatus::Delivered => 'سُلّمت وأُرفق إثبات التسليم.',
            default => null,
        };
    }

    // ── Website side ─────────────────────────────────────────

    protected function websiteRequests(\DateTimeInterface $realNow): void
    {
        $requests = [
            ['فادي عودة', '+970599002201', 'withdraw', 1500, 'USDT', 'usdt_withdraw', 'bank_of_palestine_ils', 'بنك فلسطين', 'وصلان بمبلغين مختلفين', 'new', 35],
            ['ريم الأسطل', '+962790002202', 'receive', 200, 'JOD', 'jordan_cliq', 'ewallet_ps', 'محفظة جوال باي', null, 'new', 80],
            ['خالد شعث', '+201000002203', 'receive', 8000, 'EGP', 'egypt_vodafone_cash', 'cash', null, null, 'new', 150],
            ['إياد صيام', '+905300002204', 'receive', 20000, 'TRY', 'turkey_bank', 'bank_of_palestine_ils', 'بنك فلسطين', null, 'contacted', 400],
            ['هبة النجار', '+12025550205', 'receive', 300, 'USD', 'us_zelle', 'cash', null, 'أول تعامل', 'converted', 600],
            ['سمير غانم', '+970599002206', 'send', 100, 'USD', 'syria_shamcash', null, null, 'تجربة', 'closed', 2000],
        ];

        foreach ($requests as [$name, $phone, $op, $amount, $currency, $service, $method, $via, $notes, $status, $minutesAgo]) {
            Carbon::setTestNow(Carbon::parse($realNow)->subMinutes($minutesAgo));
            Auth::forgetUser(); // created as the public website would: staff get notified

            $request = TransferRequest::create([
                'name' => $name, 'phone' => $phone, 'operation' => $op, 'amount' => $amount,
                'currency_id' => $this->currency($currency), 'service_id' => $this->service($service),
                'delivery_method_id' => $method ? DeliveryMethod::where('code', $method)->value('id') : null,
                'receive_via' => $via, 'notes' => $notes, 'locale' => 'ar',
            ]);

            Auth::setUser($this->admin);
            Carbon::setTestNow(Carbon::parse($realNow)->subMinutes(max(1, $minutesAgo - 20)));
            match ($status) {
                'contacted' => $request->markContacted(),
                'converted' => $request->convertToTransfer(),
                'closed' => $request->close(),
                default => null,
            };
        }
    }

    protected function announcements(\DateTimeInterface $realNow): void
    {
        $this->at(Carbon::parse($realNow)->subDays(3));
        Announcement::create([
            'type' => AnnouncementType::AccountChange, 'is_published' => true, 'is_pinned' => true,
            'title_ar' => 'تفعيل رقم استقبال جديد لـ Vodafone Cash', 'title_en' => 'New Vodafone Cash receiving number',
            'body_ar' => "تم إيقاف رقم الاستقبال السابق.\nاطلب الرقم الجديد من واتساب الرسمي فقط قبل أي تحويل.",
            'body_en' => "The previous receiving number is stopped.\nAsk for the new one on our official WhatsApp before paying.",
        ]);

        $this->at(Carbon::parse($realNow)->subDays(2));
        Announcement::create([
            'type' => AnnouncementType::Offer, 'is_published' => true,
            'title_ar' => 'أسعار خاصة للمبالغ الكبيرة', 'title_en' => 'Special rates for large amounts',
            'body_ar' => 'للمبالغ فوق 5,000 USDT تواصل معنا لعرض سعر خاص.', 'body_en' => 'For amounts above 5,000 USDT, contact us for a special rate.',
        ]);

        $this->at(Carbon::parse($realNow)->subDay());
        Announcement::create([
            'type' => AnnouncementType::Warning, 'is_published' => true,
            'title_ar' => 'احذر الحسابات المنتحلة', 'title_en' => 'Beware of impersonators',
            'body_ar' => 'رقمنا الرسمي الوحيد هو المذكور في الموقع. لا تحوّل لأي حساب يصلك من رقم آخر.',
            'body_en' => 'Our only official number is the one on this website. Never pay into an account sent from another number.',
        ]);

        Announcement::create([
            'type' => AnnouncementType::Info, 'is_published' => true, 'published_at' => Carbon::parse($realNow)->addDays(2)->setTime(9, 0),
            'title_ar' => 'تحديث ساعات العمل (مجدول)', 'body_ar' => 'إعلان مجدول يظهر تلقائيًا في موعده.',
        ]);

        Announcement::create([
            'type' => AnnouncementType::Info, 'is_published' => false,
            'title_ar' => 'مسودة: خدمة جديدة قريبًا', 'body_ar' => 'مسودة لم تُنشر بعد.',
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────

    /** A small labelled PNG standing in for a receipt photo, stored on the private disk. */
    protected function file(string $dir, string $label): string
    {
        $path = $dir . '/demo-' . Str::lower(Str::random(12)) . '.png';

        if (function_exists('imagecreatetruecolor')) {
            $img = imagecreatetruecolor(640, 360);
            imagefill($img, 0, 0, imagecolorallocate($img, 248, 247, 243));
            $navy = imagecolorallocate($img, 11, 31, 58);
            $gold = imagecolorallocate($img, 201, 162, 39);
            imagefilledrectangle($img, 0, 0, 640, 56, $navy);
            imagestring($img, 5, 24, 20, 'AL RAED EXCHANGE - DEMO FILE', $gold);
            imagestring($img, 5, 24, 120, $label, $navy);
            imagestring($img, 4, 24, 160, 'Not a real payment. For testing only.', $navy);
            imagestring($img, 3, 24, 320, now()->format('Y-m-d H:i'), $navy);
            ob_start();
            imagepng($img);
            $png = (string) ob_get_clean();
        } else {
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        }

        Storage::disk('local')->put($path, $png);

        return $path;
    }

    protected function at(\DateTimeInterface $time): void
    {
        $this->clock = Carbon::parse($time);
        Carbon::setTestNow($this->clock);
    }

    protected function tick(int $minutes): void
    {
        $this->at($this->clock->copy()->addMinutes($minutes));
    }

    protected function country(string $code): ?int
    {
        return Country::where('code', $code)->value('id');
    }

    protected function currency(string $code): ?int
    {
        return Currency::where('code', $code)->value('id');
    }

    protected function service(string $code): ?int
    {
        return Service::where('code', $code)->value('id');
    }
}
