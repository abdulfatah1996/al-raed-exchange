<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\CryptoNetwork;
use App\Models\Currency;
use App\Models\DeliveryMethod;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $countries = [
            ['PS', 'فلسطين', 'Palestine', '+970'], ['JO', 'الأردن', 'Jordan', '+962'], ['EG', 'مصر', 'Egypt', '+20'],
            ['TR', 'تركيا', 'Turkey', '+90'], ['SY', 'سوريا', 'Syria', '+963'], ['LB', 'لبنان', 'Lebanon', '+961'],
            ['MA', 'المغرب', 'Morocco', '+212'], ['DZ', 'الجزائر', 'Algeria', '+213'], ['TN', 'تونس', 'Tunisia', '+216'],
            ['US', 'الولايات المتحدة', 'United States', '+1'], ['CA', 'كندا', 'Canada', '+1'], ['AE', 'الإمارات', 'UAE', '+971'],
            ['SA', 'السعودية', 'Saudi Arabia', '+966'], ['AU', 'أستراليا', 'Australia', '+61'], ['GB', 'بريطانيا', 'United Kingdom', '+44'],
            ['DE', 'ألمانيا', 'Germany', '+49'], ['FR', 'فرنسا', 'France', '+33'], ['NL', 'هولندا', 'Netherlands', '+31'],
            ['BE', 'بلجيكا', 'Belgium', '+32'], ['IT', 'إيطاليا', 'Italy', '+39'], ['ES', 'إسبانيا', 'Spain', '+34'], ['SE', 'السويد', 'Sweden', '+46'],
        ];
        foreach ($countries as $i => [$code, $ar, $en, $dial]) {
            Country::updateOrCreate(['code' => $code], ['name_ar' => $ar, 'name_en' => $en, 'dial_code' => $dial, 'sort' => $i + 1]);
        }

        $currencies = [
            ['ILS', 'شيكل', 'Israeli Shekel', '₪', 2, 'fiat'], ['USD', 'دولار أمريكي', 'US Dollar', '$', 2, 'fiat'],
            ['JOD', 'دينار أردني', 'Jordanian Dinar', 'JD', 3, 'fiat'], ['EGP', 'جنيه مصري', 'Egyptian Pound', 'E£', 2, 'fiat'],
            ['EUR', 'يورو', 'Euro', '€', 2, 'fiat'], ['TRY', 'ليرة تركية', 'Turkish Lira', '₺', 2, 'fiat'],
            ['SYP', 'ليرة سورية', 'Syrian Pound', 'SYP', 0, 'fiat'], ['GBP', 'جنيه إسترليني', 'British Pound', '£', 2, 'fiat'],
            ['AED', 'درهم إماراتي', 'UAE Dirham', 'AED', 2, 'fiat'], ['SAR', 'ريال سعودي', 'Saudi Riyal', 'SAR', 2, 'fiat'],
            ['CAD', 'دولار كندي', 'Canadian Dollar', 'C$', 2, 'fiat'], ['AUD', 'دولار أسترالي', 'Australian Dollar', 'A$', 2, 'fiat'],
            ['MAD', 'درهم مغربي', 'Moroccan Dirham', 'MAD', 2, 'fiat'], ['DZD', 'دينار جزائري', 'Algerian Dinar', 'DZD', 2, 'fiat'],
            ['TND', 'دينار تونسي', 'Tunisian Dinar', 'TND', 3, 'fiat'], ['LBP', 'ليرة لبنانية', 'Lebanese Pound', 'LBP', 0, 'fiat'],
            ['USDT', 'تيثر', 'Tether', 'USDT', 8, 'crypto'],
        ];
        foreach ($currencies as $i => [$code, $ar, $en, $symbol, $dec, $type]) {
            Currency::updateOrCreate(['code' => $code], ['name_ar' => $ar, 'name_en' => $en, 'symbol' => $symbol, 'decimals' => $dec, 'type' => $type, 'sort' => $i + 1]);
        }

        foreach ([['TRC20', 'Tron (TRC20)'], ['ERC20', 'Ethereum (ERC20)'], ['BEP20', 'BNB Smart Chain (BEP20)'], ['TON', 'TON'], ['SOL', 'Solana']] as $i => [$code, $name]) {
            CryptoNetwork::updateOrCreate(['code' => $code], ['name' => $name, 'sort' => $i + 1]);
        }

        $c = fn (string $code) => Country::where('code', $code)->value('id');
        $services = [
            ['usdt_withdraw', 'سحب USDT', 'USDT withdrawal', null, 'receive'],
            ['usdt_deposit', 'إيداع / شراء USDT', 'USDT deposit', null, 'send'],
            ['usdt_transfer', 'استقبال وإرسال USDT', 'USDT transfer', null, 'both'],
            ['paypal_receive', 'حوالة PayPal', 'PayPal transfer', null, 'receive'],
            ['jordan_bank', 'حوالة بنكية أردنية', 'Jordan bank transfer', 'JO', 'both'],
            ['jordan_cliq', 'حوالة CliQ', 'CliQ transfer', 'JO', 'both'],
            ['jordan_wallet', 'محفظة أردنية', 'Jordan e-wallet', 'JO', 'both'],
            ['egypt_vodafone_cash', 'Vodafone Cash', 'Vodafone Cash', 'EG', 'both'],
            ['egypt_instapay', 'InstaPay', 'InstaPay', 'EG', 'both'],
            ['egypt_bank', 'حوالة بنكية مصرية', 'Egypt bank transfer', 'EG', 'both'],
            ['eu_sepa', 'حوالة أوروبية SEPA', 'SEPA transfer', null, 'receive'],
            ['turkey_bank', 'حوالة بنكية تركية', 'Turkey bank transfer', 'TR', 'both'],
            ['syria_shamcash', 'Sham Cash', 'Sham Cash', 'SY', 'both'],
            ['lebanon_wish', 'Wish Money (لبنان)', 'Wish Money (Lebanon)', 'LB', 'both'],
            ['intl_moneygram', 'MoneyGram', 'MoneyGram', null, 'both'],
            ['intl_western_union', 'Western Union', 'Western Union', null, 'both'],
            ['intl_ria', 'Ria', 'Ria', null, 'both'],
            ['us_zelle', 'Zelle', 'Zelle', 'US', 'both'],
            ['us_venmo', 'Venmo', 'Venmo', 'US', 'both'],
            ['us_cashapp', 'Cash App', 'Cash App', 'US', 'both'],
            ['maghreb_transfer', 'حوالات المغرب والجزائر وتونس', 'Maghreb transfers', null, 'both'],
            ['canada_transfer', 'حوالات كندا', 'Canada transfers', 'CA', 'both'],
            ['uae_transfer', 'حوالات الإمارات', 'UAE transfers', 'AE', 'both'],
            ['australia_transfer', 'حوالات أستراليا', 'Australia transfers', 'AU', 'both'],
        ];
        foreach ($services as $i => [$code, $ar, $en, $country, $dir]) {
            Service::updateOrCreate(['code' => $code], ['name_ar' => $ar, 'name_en' => $en, 'country_id' => $country ? $c($country) : null, 'direction' => $dir, 'sort' => $i + 1]);
        }

        $cur = fn (string $code) => Currency::where('code', $code)->value('id');
        $methods = [
            ['bank_of_palestine_ils', 'بنك فلسطين — شيكل', 'Bank of Palestine — ILS', 'ILS'],
            ['bank_of_palestine_usd', 'بنك فلسطين — دولار', 'Bank of Palestine — USD', 'USD'],
            ['other_ps_bank', 'بنك فلسطيني آخر', 'Other Palestinian bank', null],
            ['ewallet_ps', 'محفظة إلكترونية', 'E-wallet', null],
            ['usdt', 'تسليم USDT', 'USDT delivery', 'USDT'],
            ['cash', 'نقدًا', 'Cash', null],
        ];
        foreach ($methods as $i => [$code, $ar, $en, $currency]) {
            DeliveryMethod::updateOrCreate(['code' => $code], ['name_ar' => $ar, 'name_en' => $en, 'currency_id' => $currency ? $cur($currency) : null, 'sort' => $i + 1]);
        }
    }
}
