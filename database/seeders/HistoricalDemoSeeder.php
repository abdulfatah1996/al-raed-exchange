<?php

namespace Database\Seeders;

use App\Enums\AnnouncementType;
use App\Enums\RequestStatus;
use App\Enums\TransferStatus;
use App\Models\Announcement;
use App\Models\Customer;
use App\Models\CustomerNote;
use App\Models\DeliveryMethod;
use App\Models\Receipt;
use App\Models\Transfer;
use App\Models\TransferRequest;
use App\Models\User;
use App\Notifications\StaffNotifier;
use App\Support\Permissions;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * A believable multi-month history for demos and screen recordings.
 *
 * Volume grows over time, Fridays are quiet, salary days, Ramadan and the weeks
 * before Eid are busier. USDT and PayPal dominate, customers come back, and every
 * step is performed by the role that owns it (employee prices and records,
 * supervisor verifies and approves, cashier delivers). Everything goes through
 * the real model rules, so status history, verification and delivery records are
 * consistent. Deterministic: the same seed gives the same history.
 */
class HistoricalDemoSeeder extends DemoDataSeeder
{
    private const SEED = 20250101;

    private CarbonImmutable $now;

    private CarbonImmutable $since;

    /** @var list<Customer> loyal customers first */
    private array $pool = [];

    private int $phoneSeq = 1000;

    /** @var array<string, int> */
    private array $methods = [];

    private const FIRST = ['أحمد', 'محمد', 'محمود', 'خالد', 'يوسف', 'عمر', 'إبراهيم', 'علي', 'حسن', 'سامي', 'رامي', 'فادي', 'نادر', 'طارق', 'كريم', 'إياد', 'معاذ', 'أنس', 'بلال', 'حمزة', 'سارة', 'منى', 'هدى', 'ليلى', 'رنا', 'هبة', 'ريم', 'دعاء', 'آية', 'نور', 'سلمى', 'ياسمين', 'أسماء', 'إسراء', 'لينا', 'مريم'];

    private const LAST = ['الشريف', 'خليل', 'عبد الله', 'حسن', 'نصار', 'درويش', 'سليمان', 'منصور', 'عيسى', 'الحلو', 'شعث', 'الأسطل', 'صيام', 'النجار', 'غانم', 'أبو شمالة', 'الفرا', 'أبو عيدة', 'حلس', 'الكحلوت', 'أبو سيدو', 'المصري', 'الغول', 'أبو حصيرة', 'السقا', 'قديح', 'البطش', 'العطار', 'الزعانين', 'أبو ندى'];

    /**
     * code => [weight, type, send currency, [min, max], round, deliveries[[currency, rate, method]], account, countries]
     */
    private const SERVICES = [
        'usdt_withdraw' => [30, 'usdt_withdraw', 'USDT', [100, 9000], 10, [['ILS', 3.58, 'bank_of_palestine_ils'], ['USD', 0.988, 'bank_of_palestine_usd'], ['ILS', 3.56, 'ewallet_ps']], 'usdt', ['PS']],
        'paypal_receive' => [13, 'receive', 'USD', [40, 1500], 5, [['USD', 0.915, 'bank_of_palestine_usd'], ['ILS', 3.32, 'bank_of_palestine_ils']], 'paypal', ['US', 'GB', 'DE', 'CA']],
        'jordan_cliq' => [8, 'receive', 'JOD', [50, 2000], 5, [['ILS', 5.08, 'ewallet_ps'], ['ILS', 5.1, 'bank_of_palestine_ils']], 'cliq', ['JO']],
        'jordan_bank' => [3, 'receive', 'JOD', [200, 5000], 50, [['ILS', 5.1, 'bank_of_palestine_ils']], null, ['JO']],
        'egypt_vodafone_cash' => [6, 'receive', 'EGP', [1000, 40000], 100, [['USD', 0.0198, 'cash'], ['ILS', 0.072, 'ewallet_ps']], 'vodafone', ['EG']],
        'egypt_instapay' => [3, 'receive', 'EGP', [2000, 60000], 100, [['USD', 0.0198, 'bank_of_palestine_usd']], 'instapay', ['EG']],
        'eu_sepa' => [8, 'receive', 'EUR', [100, 5000], 10, [['ILS', 3.98, 'bank_of_palestine_ils'], ['USD', 1.07, 'bank_of_palestine_usd']], 'sepa', ['DE', 'NL', 'BE', 'FR', 'SE']],
        'turkey_bank' => [6, 'receive', 'TRY', [2000, 80000], 100, [['ILS', 0.104, 'bank_of_palestine_ils']], 'turkey', ['TR']],
        'usdt_deposit' => [5, 'usdt_deposit', 'ILS', [500, 15000], 50, [['USDT', 0.272, 'usdt']], 'usdt', ['PS']],
        'intl_western_union' => [3, 'receive', 'USD', [100, 2000], 10, [['USD', 0.96, 'cash']], null, ['US', 'AE', 'SA']],
        'intl_moneygram' => [3, 'receive', 'USD', [100, 2000], 10, [['USD', 0.96, 'cash']], null, ['US', 'AE', 'CA']],
        'intl_ria' => [2, 'receive', 'USD', [100, 1500], 10, [['USD', 0.962, 'cash']], null, ['US', 'GB']],
        'us_zelle' => [3, 'receive', 'USD', [50, 1500], 5, [['USD', 0.93, 'bank_of_palestine_usd']], null, ['US']],
        'us_cashapp' => [2, 'receive', 'USD', [30, 800], 5, [['USD', 0.92, 'cash']], null, ['US']],
        'syria_shamcash' => [2, 'send', 'USD', [50, 600], 5, [['USD', 0.97, null]], null, ['SY']],
        'uae_transfer' => [2, 'receive', 'AED', [300, 8000], 50, [['ILS', 0.965, 'bank_of_palestine_ils']], null, ['AE']],
        'canada_transfer' => [1, 'receive', 'CAD', [100, 3000], 10, [['ILS', 2.6, 'bank_of_palestine_ils']], null, ['CA']],
    ];

    private const DIAL = ['PS' => '+97059', 'JO' => '+96279', 'EG' => '+20100', 'US' => '+1202', 'GB' => '+4479', 'DE' => '+49151', 'CA' => '+1416', 'NL' => '+3161', 'BE' => '+32470', 'FR' => '+3361', 'SE' => '+4670', 'TR' => '+90530', 'AE' => '+97150', 'SA' => '+96650', 'SY' => '+96393'];

    /** @param callable(string):void|null $progress */
    public function runHistory(User $admin, CarbonImmutable $since, ?callable $progress = null): array
    {
        mt_srand(self::SEED);
        $this->admin = $admin;
        $this->now = CarbonImmutable::now();
        $this->since = $since->startOfDay();
        $this->methods = DeliveryMethod::pluck('id', 'code')->all();

        try {
            $this->at($this->since->setTime(8, 30));
            Auth::setUser($admin);
            $this->staff();
            $this->extraStaff();
            $this->accounts();

            StaffNotifier::muted(function () use ($progress) {
                $month = null;
                $batch = [];
                foreach ($this->schedule() as $time) {
                    if ($month !== null && $time->format('Y-m') !== $month) {
                        $this->flush($batch, $month, $progress);
                        $batch = [];
                    }
                    $month = $time->format('Y-m');
                    $batch[] = $time;
                }
                $this->flush($batch, (string) $month, $progress);

                $this->finishCustomers();
                $this->historicAnnouncements();
                $this->historicRequests();
            });

            Carbon::setTestNow();
            Auth::setUser($admin);
            $this->liveRequests();
        } finally {
            Carbon::setTestNow();
            Auth::setUser($admin);
        }

        return [
            'Period' => $this->since->toDateString() . ' → ' . $this->now->toDateString(),
            'Transfers' => Transfer::count(),
            'Delivered' => Transfer::where('status', TransferStatus::Delivered)->count(),
            'Open now' => Transfer::whereNotIn('status', [TransferStatus::Delivered, TransferStatus::Cancelled])->count(),
            'Receipts' => Receipt::count(),
            'Customers' => Customer::count(),
            'Website requests' => TransferRequest::count(),
            'Announcements' => Announcement::count(),
            'Staff (demo, inactive)' => count($this->staff),
        ];
    }

    // ── Calendar ─────────────────────────────────────────────

    /** @return list<CarbonImmutable> creation times, ascending */
    private function schedule(): array
    {
        $times = [];
        $totalDays = max(1, $this->since->diffInDays($this->now));

        for ($day = $this->since; $day->lte($this->now); $day = $day->addDay()) {
            $t = $this->since->diffInDays($day) / $totalDays;
            $factor = $this->dayFactor($day);
            $lambda = (1.5 + 3.3 * $t) * $factor;
            // An ordinary working day is never empty; only Fridays and Eid days can be.
            $n = max($this->poisson($lambda), $factor >= 0.8 ? 2 : 0);

            if ($day->isSameDay($this->now)) {
                // Today: a lively morning so far, spread from opening time to a few minutes ago.
                $open = $day->setTime(9, 0);
                $span = max(0, $open->diffInMinutes($this->now->subMinutes(25), false));
                for ($k = 0, $m = $span > 0 ? max(6, $n) : 0; $k < $m; $k++) {
                    $times[] = $open->addMinutes((int) ($span * ($k + mt_rand(0, 90) / 100) / $m));
                }

                continue;
            }

            for ($k = 0; $k < $n; $k++) {
                $time = $day->setTime($this->businessHour(), mt_rand(0, 59), mt_rand(0, 59));
                if ($time->lt($this->now->subMinutes(20))) {
                    $times[] = $time;
                }
            }
        }

        usort($times, fn ($a, $b) => $a <=> $b);

        return $times;
    }

    private function dayFactor(CarbonImmutable $day): float
    {
        $f = match ($day->dayOfWeek) {
            5 => 0.35,          // Friday
            6 => 0.85,          // Saturday
            4 => 1.1,           // Thursday
            default => 1.0,
        };

        $f *= match (true) {
            $day->day <= 5 => 1.35,     // salaries and month-start remittances
            $day->day >= 26 => 1.1,
            default => 1.0,
        };

        $d = $day->toDateString();
        $in = fn (string $a, string $b) => $d >= $a && $d <= $b;
        $f *= match (true) {
            $in('2025-03-24', '2025-03-29'), $in('2026-03-13', '2026-03-19') => 1.7,   // days before Eid al-Fitr
            $in('2025-03-01', '2025-03-23'), $in('2026-02-18', '2026-03-12') => 1.3,   // Ramadan
            $in('2025-03-30', '2025-04-01'), $in('2026-03-20', '2026-03-22') => 0.3,   // Eid al-Fitr
            $in('2025-05-30', '2025-06-05'), $in('2026-05-20', '2026-05-26') => 1.45,  // before Eid al-Adha
            $in('2025-06-06', '2025-06-08'), $in('2026-05-27', '2026-05-29') => 0.3,   // Eid al-Adha
            default => 1.0,
        };

        return $f;
    }

    private function businessHour(): int
    {
        return $this->weighted([9 => 4, 10 => 8, 11 => 10, 12 => 10, 13 => 8, 14 => 7, 15 => 7, 16 => 8, 17 => 9, 18 => 9, 19 => 7, 20 => 5, 21 => 2]);
    }

    /** Next working moment after $minutes: nights roll over to the next morning, Fridays start later. */
    private function after(CarbonImmutable $from, int $minutes): CarbonImmutable
    {
        $t = $from->addMinutes($minutes);
        if ($t->hour >= 22 || $t->hour < 9) {
            $next = $t->hour >= 22 ? $t->addDay() : $t;
            $t = $next->setTime($next->dayOfWeek === 5 ? 14 : 9, mt_rand(0, 50));
        }

        return $t;
    }

    // ── Transfers ────────────────────────────────────────────

    private function flush(array $times, string $month, ?callable $progress): void
    {
        if ($times === []) {
            return;
        }

        DB::transaction(function () use ($times) {
            foreach ($times as $time) {
                $this->historicTransfer($time);
            }
        });

        $progress && $progress($month . ' · ' . count($times));
    }

    private function historicTransfer(CarbonImmutable $created): void
    {
        $code = $this->weighted(array_map(fn ($s) => $s[0], self::SERVICES));
        [, $type, $sendCode, [$min, $max], $round, $deliveries, $account, $countries] = self::SERVICES[$code];
        [$deliverCode, $baseRate, $method] = $deliveries[array_rand($deliveries)];

        $t = $this->since->diffInDays($created) / max(1, $this->since->diffInDays($this->now));
        $rate = round($baseRate * (1 + 0.025 * sin($t * 7.5)) * (1 + (mt_rand(-80, 80) / 10000)), $baseRate < 0.5 ? 6 : 4);
        $amount = max($min, round(exp(log($min) + (log($max) - log($min)) * (mt_rand() / mt_getrandmax()) ** 1.8) / $round) * $round);
        $deliver = round($amount * $rate, 2);

        $employee = $this->pick(['employee', 'employee2']);
        $cashier = $this->pick(['cashier', 'cashier2']);
        $this->at($created);
        Auth::setUser($employee);

        $customer = $this->customerFor($countries[array_rand($countries)]);

        $transfer = Transfer::create([
            'customer_id' => $customer->id, 'service_id' => $this->service($code),
            'country_id' => $customer->country_id, 'type' => $type,
            'send_amount' => $amount, 'send_currency_id' => $this->currency($sendCode),
            'deliver_amount' => $deliver, 'deliver_currency_id' => $this->currency($deliverCode),
            'rate' => $rate, 'fee_amount' => 0,
            'delivery_method_id' => $method ? ($this->methods[$method] ?? null) : null,
            'payment_account_id' => $account ? $this->accounts[$account]->id : null,
            'sender_name' => $customer->name, 'beneficiary_name' => $customer->name,
            'beneficiary_details' => $method === 'usdt' ? ['wallet' => 'TDEMO' . Str::upper(Str::random(12))] : ['bank_name' => 'بنك فلسطين', 'account' => 'DEMO-' . mt_rand(100000, 999999)],
            'assigned_user_id' => $employee->id,
        ]);

        $ageHours = $created->diffInHours($this->now);
        $r = mt_rand(1, 1000);
        $ending = match (true) {
            $r <= 25 => 'declined',                        // customer declined the quote
            $r <= 40 => 'rejected',                        // receipt not found on the statement
            $r <= 120 && $ageHours < 96 => 'hold',
            default => 'deliver',
        };

        $clock = $created;
        $steps = [
            [TransferStatus::PendingPricing, 'employee', [2, 20]],
            [TransferStatus::PendingCustomerApproval, 'employee', [5, 35], 'أُرسل عرض السعر للعميل على واتساب.'],
            [TransferStatus::PendingTransfer, 'employee', [5, 60], 'العميل وافق، أُرسل حساب الاستقبال.'],
            [TransferStatus::PendingVerification, 'employee', [10, 150], 'وصلت الإيصالات من العميل.'],
            [TransferStatus::UnderReview, 'supervisor', [5, 60]],
            [TransferStatus::Approved, 'supervisor', [5, 40], 'كل الوصولات مُشيّكة.'],
            [TransferStatus::InProgress, 'cashier', [5, 60]],
            [TransferStatus::Delivered, 'cashier', [10, 90], 'سُلّمت وأُرفق إثبات التسليم.'],
        ];

        foreach ($steps as $step) {
            [$to, $role, [$lo, $hi]] = $step;
            $note = $step[3] ?? null;

            if ($ending === 'declined' && $to === TransferStatus::PendingTransfer) {
                $when = $this->after($clock, mt_rand(60, 600));
                if ($when->lt($this->now)) {
                    $this->move($transfer, $when, $employee, TransferStatus::Cancelled, 'العميل لم يوافق على السعر.');
                }

                return;
            }
            if ($ending === 'hold' && $to === TransferStatus::PendingVerification) {
                $when = $this->after($clock, mt_rand(30, 240));
                if ($when->lt($this->now)) {
                    $this->move($transfer, $when, $employee, TransferStatus::OnHold, 'العميل طلب تأجيل التحويل.');
                }

                return;
            }

            $next = $this->after($clock, mt_rand($lo, $hi));
            if ($next->gte($this->now)) {
                return; // still in progress today
            }

            $actor = match ($role) {
                'supervisor' => $this->staff['supervisor'],
                'cashier' => $cashier,
                default => $employee,
            };

            if ($to === TransferStatus::PendingVerification) {
                $this->historicReceipts($transfer, $amount, $sendCode, $rate, $deliverCode, $account, $method, $clock, $next, $employee);
            }

            if ($to === TransferStatus::Approved) {
                $this->at($clock->addMinutes(2));
                Auth::setUser($actor);
                foreach ($transfer->receipts()->get() as $receipt) {
                    $receipt->verify('تم التأكد من وصول المبلغ في كشف الحساب.');
                }
            }

            if ($to === TransferStatus::Delivered) {
                $this->at($next->subMinutes(5));
                Auth::setUser($actor);
                foreach ($transfer->receipts()->get() as $receipt) {
                    $receipt->markDelivered();
                }
                $transfer->update(['delivery_proof_file' => $this->file('proofs', "DELIVERY PROOF {$transfer->reference}")]);
            }

            $this->move($transfer, $next, $actor, $to, $note);
            $clock = $next;

            if ($ending === 'rejected' && $to === TransferStatus::UnderReview) {
                $later = $this->after($clock, mt_rand(10, 60));
                if ($later->addMinute()->gte($this->now)) {
                    return;
                }
                $this->at($later);
                Auth::setUser($this->staff['supervisor']);
                $transfer->receipts()->first()?->reject('المبلغ غير موجود في كشف الحساب.');
                $this->move($transfer, $later->addMinute(), $this->staff['supervisor'], TransferStatus::Rejected, 'رُفض الوصل: المبلغ غير موجود في الكشف.');
                $final = $this->after($later, mt_rand(120, 1440));
                if ($final->lt($this->now)) {
                    $this->move($transfer, $final, $employee, TransferStatus::Cancelled, 'تعذّر إثبات التحويل، أُغلقت الحوالة.');
                }

                return;
            }
        }
    }

    private function move(Transfer $transfer, CarbonImmutable $at, User $actor, TransferStatus $to, ?string $note = null): void
    {
        $this->at($at);
        Auth::setUser($actor);
        $transfer->transitionTo($to, $note);
    }

    private function historicReceipts(Transfer $transfer, float $amount, string $code, float $rate, string $deliverCode, ?string $account, ?string $method, CarbonImmutable $from, CarbonImmutable $until, User $employee): void
    {
        $count = $this->weighted([1 => 75, 2 => 20, 3 => 5]);
        $parts = $count === 1 ? [$amount] : $this->split($amount, $count);

        Auth::setUser($employee);
        foreach ($parts as $n => $part) {
            $this->at($from->addSeconds((int) (($until->getTimestamp() - $from->getTimestamp()) * ($n + 1) / ($count + 1))));
            Receipt::create([
                'transfer_id' => $transfer->id, 'amount' => $part, 'currency_id' => $this->currency($code),
                'payment_account_id' => $account ? $this->accounts[$account]->id : null,
                'rate' => $rate, 'deliver_amount' => round($part * $rate, 2), 'deliver_currency_id' => $this->currency($deliverCode),
                'delivery_method_id' => $method ? ($this->methods[$method] ?? null) : null,
                'external_ref' => $code === 'USDT' ? Str::upper(hash('sha256', $transfer->reference . $n)) : 'DEMO-' . $code . '-' . (++$this->ref),
                'sender_name' => $transfer->sender_name,
                'receipt_file' => $this->file('receipts', "RECEIPT {$transfer->reference} #" . ($n + 1)),
            ]);
        }
    }

    /** @return list<float> */
    private function split(float $amount, int $count): array
    {
        $parts = [];
        $left = $amount;
        for ($i = 1; $i < $count; $i++) {
            $part = round($left * mt_rand(30, 60) / 100);
            $parts[] = $part;
            $left -= $part;
        }
        $parts[] = round($left, 2);

        return $parts;
    }

    // ── Customers ────────────────────────────────────────────

    private function customerFor(string $country): Customer
    {
        // About 7 in 10 transfers come from a returning customer; loyal (early) customers return most.
        if ($this->pool !== [] && mt_rand(1, 100) <= 70) {
            $idx = (int) floor(count($this->pool) * (mt_rand() / mt_getrandmax()) ** 2.4);

            return $this->pool[min($idx, count($this->pool) - 1)];
        }

        $name = self::FIRST[array_rand(self::FIRST)] . ' ' . self::FIRST[array_rand(array_slice(self::FIRST, 0, 20))] . ' ' . self::LAST[array_rand(self::LAST)];
        $customer = Customer::create([
            'name' => $name,
            'phone' => (self::DIAL[$country] ?? '+97059') . str_pad((string) (++$this->phoneSeq), 7, '0', STR_PAD_LEFT),
            'country_id' => $this->country($country),
            'kyc_status' => $this->weighted(['verified' => 45, 'pending' => 20, 'none' => 35]),
            'notes' => 'بيانات تجريبية',
        ]);
        $this->pool[] = $customer;

        return $customer;
    }

    private function finishCustomers(): void
    {
        $this->at($this->now->subDays(3));
        Auth::setUser($this->staff['supervisor']);

        $top = Transfer::query()->selectRaw('customer_id, count(*) as c')->groupBy('customer_id')->orderByDesc('c')->limit(12)->pluck('customer_id');
        Customer::whereIn('id', $top)->update(['status' => 'vip', 'kyc_status' => 'verified']);
        foreach ($top->take(4) as $id) {
            CustomerNote::create(['customer_id' => $id, 'user_id' => $this->staff['supervisor']->id, 'note' => 'عميل دائم بمبالغ كبيرة، أولوية في التسليم.']);
        }

        $flagged = Transfer::where('status', TransferStatus::Cancelled)->whereHas('statusLogs', fn ($q) => $q->where('to_status', TransferStatus::Rejected->value))->limit(2)->pluck('customer_id');
        Customer::whereIn('id', $flagged)->update(['status' => 'blocked']);
        foreach ($flagged as $id) {
            CustomerNote::create(['customer_id' => $id, 'user_id' => $this->staff['supervisor']->id, 'note' => 'موقوف: إيصال لا يطابق كشف الحساب.']);
        }
    }

    private function extraStaff(): void
    {
        foreach (['employee2' => ['أنس قديح (تجريبي)', Permissions::ROLE_EMPLOYEE], 'cashier2' => ['دعاء الغول (تجريبي)', Permissions::ROLE_CASHIER]] as $key => [$name, $role]) {
            $user = User::where('email', "demo-{$key}@alraed.local")->first() ?? tap(new User, fn (User $u) => $u->forceFill([
                'name' => $name, 'email' => "demo-{$key}@alraed.local", 'password' => Str::password(32), 'is_active' => false, 'locale' => 'ar',
            ])->save());
            $user->syncRoles([$role]);
            $this->staff[$key] = $user;
        }
    }

    // ── Website & announcements ──────────────────────────────

    /** Website requests for the last weeks, linked to the transfers they became. */
    private function historicRequests(): void
    {
        $recent = Transfer::with('customer')->where('created_at', '>=', $this->now->subDays(28))->where('created_at', '<', $this->now->subHours(6))->get();

        foreach ($recent as $transfer) {
            if (mt_rand(1, 100) > 40) {
                continue;
            }
            $this->at(CarbonImmutable::parse($transfer->created_at)->subMinutes(mt_rand(10, 90)));
            Auth::forgetUser();
            $request = TransferRequest::create([
                'name' => $transfer->customer->name, 'phone' => $transfer->customer->phone,
                'operation' => match ($transfer->type->value) { 'usdt_withdraw' => 'withdraw', 'usdt_deposit' => 'deposit', 'send' => 'send', default => 'receive' },
                'amount' => $transfer->send_amount, 'currency_id' => $transfer->send_currency_id, 'service_id' => $transfer->service_id,
                'delivery_method_id' => $transfer->delivery_method_id, 'locale' => 'ar',
            ]);
            $request->forceFill(['status' => RequestStatus::Converted, 'transfer_id' => $transfer->id, 'handled_by' => $transfer->created_by, 'handled_at' => $transfer->created_at])->save();
        }

        foreach ([[9, 'closed', 'تجربة من العميل'], [4, 'closed', null], [2, 'contacted', null]] as [$days, $status, $notes]) {
            $this->at($this->now->subDays($days)->setTime(13, mt_rand(0, 59)));
            Auth::forgetUser();
            $request = TransferRequest::create(['name' => self::FIRST[array_rand(self::FIRST)] . ' ' . self::LAST[array_rand(self::LAST)], 'phone' => '+97059' . (++$this->phoneSeq) . '00',
                'operation' => 'receive', 'amount' => mt_rand(10, 80) * 10, 'currency_id' => $this->currency('USD'), 'service_id' => $this->service('paypal_receive'), 'notes' => $notes, 'locale' => 'ar']);
            Auth::setUser($this->staff['employee']);
            $status === 'closed' ? $request->close() : $request->markContacted();
        }
    }

    /** A few requests in the last hours, sent unmuted so the bell has something to show. */
    private function liveRequests(): void
    {
        foreach ([[170, 'usdt_withdraw', 'withdraw', 2500, 'USDT'], [95, 'egypt_vodafone_cash', 'receive', 12000, 'EGP'], [40, 'eu_sepa', 'receive', 650, 'EUR'], [12, 'jordan_cliq', 'receive', 300, 'JOD']] as [$minutes, $service, $op, $amount, $currency]) {
            Carbon::setTestNow($this->now->subMinutes($minutes));
            Auth::forgetUser();
            TransferRequest::create([
                'name' => self::FIRST[array_rand(self::FIRST)] . ' ' . self::LAST[array_rand(self::LAST)], 'phone' => '+97059' . (++$this->phoneSeq) . '11',
                'operation' => $op, 'amount' => $amount, 'currency_id' => $this->currency($currency), 'service_id' => $this->service($service),
                'delivery_method_id' => $this->methods['bank_of_palestine_ils'] ?? null, 'locale' => 'ar',
            ]);
        }
    }

    private function historicAnnouncements(): void
    {
        Auth::setUser($this->admin);
        $items = [
            ['2025-01-05', AnnouncementType::Info, 'إطلاق خدمة سحب USDT إلى بنك فلسطين', 'نستقبل USDT عبر شبكة TRC20 ونسلّم بالشيكل أو الدولار على بنك فلسطين.', null, false],
            ['2025-02-10', AnnouncementType::Info, 'إضافة خدمة CliQ من الأردن', 'أصبح بإمكانكم إرسال الحوالات من الأردن عبر CliQ.', null, false],
            ['2025-02-27', AnnouncementType::Info, 'ساعات العمل في رمضان', 'من 10 صباحًا حتى 4 عصرًا، ومن 8 حتى 11 مساءً.', '2025-03-30', false],
            ['2025-03-28', AnnouncementType::Warning, 'إجازة عيد الفطر', 'المكتب مغلق أول يومين من العيد. كل عام وأنتم بخير.', '2025-04-02', false],
            ['2025-07-14', AnnouncementType::AccountChange, 'تغيير رقم Vodafone Cash', 'تم إيقاف الرقم القديم، اطلب الرقم الجديد من واتساب الرسمي.', '2025-09-01', false],
            ['2025-10-02', AnnouncementType::Info, 'إضافة الحوالات البنكية من تركيا', 'نستقبل الآن الحوالات بالليرة التركية على IBAN.', null, false],
            ['2026-01-11', AnnouncementType::Warning, 'احذر الحسابات المنتحلة', 'رقمنا الرسمي الوحيد هو المذكور في الموقع. لا تحوّل لأي حساب يصلك من رقم آخر.', null, false],
            ['2026-02-16', AnnouncementType::Info, 'ساعات العمل في رمضان 2026', 'من 10 صباحًا حتى 4 عصرًا، ومن 8 حتى 11 مساءً.', '2026-03-20', false],
            ['2026-05-24', AnnouncementType::Warning, 'إجازة عيد الأضحى', 'المكتب مغلق ثلاثة أيام. نعود للعمل ثالث أيام العيد.', '2026-05-30', false],
            ['2026-09-15', AnnouncementType::Offer, 'أسعار خاصة للمبالغ الكبيرة', 'للمبالغ فوق 5,000 USDT تواصل معنا لعرض سعر خاص.', null, false],
            [$this->now->subDays(5)->toDateString(), AnnouncementType::AccountChange, 'تفعيل رقم استقبال جديد لـ Vodafone Cash', "تم إيقاف رقم الاستقبال السابق.\nاطلب الرقم الجديد من واتساب الرسمي فقط قبل أي تحويل.", null, true],
        ];

        foreach ($items as [$date, $type, $title, $body, $expires, $pinned]) {
            if ($date < $this->since->toDateString()) {
                continue;
            }
            $this->at(CarbonImmutable::parse($date)->setTime(9, 30));
            Announcement::create([
                'type' => $type, 'title_ar' => $title, 'body_ar' => $body, 'is_published' => true, 'is_pinned' => $pinned,
                'expires_at' => $expires ? CarbonImmutable::parse($expires)->setTime(23, 59) : null,
            ]);
        }
    }

    // ── Randomness ───────────────────────────────────────────

    private function poisson(float $lambda): int
    {
        $l = exp(-$lambda);
        $k = 0;
        $p = 1.0;
        do {
            $k++;
            $p *= mt_rand() / mt_getrandmax();
        } while ($p > $l);

        return $k - 1;
    }

    /** @param array<int|string, int|float> $weights */
    private function weighted(array $weights): int|string
    {
        $r = mt_rand(1, (int) round(array_sum($weights) * 100)) / 100;
        foreach ($weights as $key => $w) {
            if (($r -= $w) <= 0) {
                return $key;
            }
        }

        return array_key_last($weights);
    }

    private function pick(array $keys): User
    {
        return $this->staff[$keys[array_rand($keys)]];
    }
}
