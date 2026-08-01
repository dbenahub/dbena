<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CriticalMetric;
use App\Models\CriticalMetricTarget;
use App\Models\Owner;
use App\Models\Service;
use Illuminate\Database\Seeder;

/**
 * 49 baris metrik kritikal — nilai TEPAT dari prototaip.
 *
 * PENTING: bilangan baris BERBEZA ikut servis.
 *   renovation / kabinet / bina-rumah / mihrab = 10 baris
 *   divider                                    =  9 baris
 *
 * Divider TIADA "No of Site Visit" dan menggunakan CPQ ganti CPA.
 * Bina Rumah menggunakan "No of Appointment (Offline / Online)".
 */
class CriticalMetricSeeder extends Seeder
{
    /** [metric_key, label_ms, label_en, type, value_type, target(null=teks), target_text, owner] */
    private const DATA = [
        'renovation' => [
            ['sales_collection_new',       'Sales Collection (New)',            'Sales Collection (New)',            'total', 'currency', 150000,  null,       'ZIKRI'],
            ['revenue_sales',              'Revenue/Sales',                     'Revenue/Sales',                     'total', 'currency', 500000,  null,       'ZIKRI'],
            ['sales_collection_progress',  'Sales Collection (Progress Claim)', 'Sales Collection (Progress Claim)', 'total', 'currency', null,    'Progress', 'HAFIZAN'],
            ['amount_quotation_release',   'Amount Quotation Release (New)',    'Amount Quotation Release (New)',    'total', 'currency', 2400000, null,       'HAFIZAN'],
            ['no_of_new_quotation',        'No of New Quotation',               'No of New Quotation',               'total', 'number',   16,      null,       'HAFIZAN'],
            ['no_of_site_visit',           'No of Site Visit',                  'No of Site Visit',                  'total', 'number',   24,      null,       'ZIKRI'],
            ['ads_spend',                  'Ads Spend',                         'Ads Spend',                         'total', 'currency', 6000,    null,       'ZIKRI'],
            ['no_of_lead',                 'No of Lead',                        'No of Lead',                        'total', 'number',   600,     null,       'ZIKRI'],
            ['cost_per_lead',              'Cost Per Lead (CPL)',               'Cost Per Lead (CPL)',               'avg',   'currency', 10,      null,       'INFO'],
            ['cost_per_appointment',       'Cost Per Appointment (CPA)',        'Cost Per Appointment (CPA)',        'avg',   'currency', 250,     null,       'INFO'],
        ],
        'kabinet' => [
            ['sales_collection_new',       'Sales Collection (New)',            'Sales Collection (New)',            'total', 'currency', 60000,   null,       'ZIKRI'],
            ['revenue_sales',              'Revenue/Sales',                     'Revenue/Sales',                     'total', 'currency', 200000,  null,       'ZIKRI'],
            ['sales_collection_progress',  'Sales Collection (Progress Claim)', 'Sales Collection (Progress Claim)', 'total', 'currency', null,    'Progress', 'ZIKRI'],
            ['amount_quotation_release',   'Amount Quotation Release (New)',    'Amount Quotation Release (New)',    'total', 'currency', 1000000, null,       'ZIKRI'],
            ['no_of_new_quotation',        'No of New Quotation',               'No of New Quotation',               'total', 'number',   30,      null,       'ZIKRI'],
            ['no_of_site_visit',           'No of Site Visit',                  'No of Site Visit',                  'total', 'number',   40,      null,       'ZIKRI'],
            ['ads_spend',                  'Ads Spend',                         'Ads Spend',                         'total', 'currency', 6000,    null,       'ZIKRI'],
            ['no_of_lead',                 'No of Lead',                        'No of Lead',                        'total', 'number',   400,     null,       'ZIKRI'],
            ['cost_per_lead',              'Cost Per Lead (CPL)',               'Cost Per Lead (CPL)',               'avg',   'currency', 15,      null,       'INFO'],
            ['cost_per_appointment',       'Cost Per Appointment (CPA)',        'Cost Per Appointment (CPA)',        'avg',   'currency', 150,     null,       'INFO'],
        ],
        'bina-rumah' => [
            ['sales_collection_new',       'Sales Collection (New)',            'Sales Collection (New)',            'total', 'currency', 150000,  null,       'ZIKRI'],
            ['revenue_sales',              'Revenue/Sales',                     'Revenue/Sales',                     'total', 'currency', 500000,  null,       'ZIKRI'],
            ['sales_collection_progress',  'Sales Collection (Progress Claim)', 'Sales Collection (Progress Claim)', 'total', 'currency', null,    'Progress', 'HAFIZAN'],
            ['amount_quotation_release',   'Amount Quotation Release (New)',    'Amount Quotation Release (New)',    'total', 'currency', 2500000, null,       'HAFIZAN'],
            ['no_of_new_quotation',        'No of New Quotation',               'No of New Quotation',               'total', 'number',   20,      null,       'HAFIZAN'],
            ['no_of_appointment',          'No of Appointment (Offline / Online)', 'No of Appointment (Offline / Online)', 'total', 'number', 30, null,     'HAFIZAN'],
            ['ads_spend',                  'Ads Spend',                         'Ads Spend',                         'total', 'currency', 4500,    null,       'ZIKRI'],
            ['no_of_lead',                 'No of Lead',                        'No of Lead',                        'total', 'number',   300,     null,       'ZIKRI'],
            ['cost_per_lead',              'Cost Per Lead (CPL)',               'Cost Per Lead (CPL)',               'avg',   'currency', 15,      null,       'INFO'],
            ['cost_per_appointment',       'Cost Per Appointment (CPA)',        'Cost Per Appointment (CPA)',        'avg',   'currency', 150,     null,       'INFO'],
        ],
        'mihrab' => [
            ['sales_collection_new',       'Sales Collection (New)',            'Sales Collection (New)',            'total', 'currency', 40000,   null,       'ZIKRI'],
            ['revenue_sales',              'Revenue/Sales',                     'Revenue/Sales',                     'total', 'currency', 80000,   null,       'ZIKRI'],
            ['sales_collection_progress',  'Sales Collection (Progress Claim)', 'Sales Collection (Progress Claim)', 'total', 'currency', null,    'Progress', 'ZIKRI'],
            ['amount_quotation_release',   'Amount Quotation Release (New)',    'Amount Quotation Release (New)',    'total', 'currency', 400000,  null,       'ZIKRI'],
            ['no_of_new_quotation',        'No of New Quotation',               'No of New Quotation',               'total', 'number',   11,      null,       'ZIKRI'],
            ['no_of_site_visit',           'No of Site Visit',                  'No of Site Visit',                  'total', 'number',   16,      null,       'ZIKRI'],
            ['ads_spend',                  'Ads Spend',                         'Ads Spend',                         'total', 'currency', 1200,    null,       'ZIKRI'],
            ['no_of_lead',                 'No of Lead',                        'No of Lead',                        'total', 'number',   80,      null,       'ZIKRI'],
            ['cost_per_lead',              'Cost Per Lead (CPL)',               'Cost Per Lead (CPL)',               'avg',   'currency', 15,      null,       'INFO'],
            ['cost_per_appointment',       'Cost Per Appointment (CPA)',        'Cost Per Appointment (CPA)',        'avg',   'currency', 75,      null,       'INFO'],
        ],
        /*
         * Divider tiada Site Visit dan menggunakan CPQ ganti CPA.
         *
         * Tiga metrik terakhir khas kepada Divider — ia wujud dalam Google
         * Sheet DBENA sebenar tetapi tiada dalam prototaip. Sasarannya
         * dibiarkan NULL kerana nilainya tidak diketahui; hidupkan "Import
         * Monthly Target dari sheet" supaya lajur H mengisinya.
         */
        'divider' => [
            ['sales_collection_new',       'Sales Collection (New)',            'Sales Collection (New)',            'total', 'currency', 20000,   null,       'ZIKRI'],
            ['revenue_sales',              'Revenue/Sales',                     'Revenue/Sales',                     'total', 'currency', 40000,   null,       'ZIKRI'],
            ['sales_collection_progress',  'Sales Collection (Progress Claim)', 'Sales Collection (Progress Claim)', 'total', 'currency', null,    'Progress', 'ZIKRI'],
            ['amount_quotation_release',   'Amount Quotation Release (New)',    'Amount Quotation Release (New)',    'total', 'currency', 200000,  null,       'ZIKRI'],
            ['no_of_new_quotation',        'No of New Quotation',               'No of New Quotation',               'total', 'number',   16,      null,       'ZIKRI'],
            ['ads_spend',                  'Ads Spend',                         'Ads Spend',                         'total', 'currency', 1200,    null,       'ZIKRI'],
            ['no_of_lead',                 'No of Lead',                        'No of Lead',                        'total', 'number',   80,      null,       'ZIKRI'],
            ['cost_per_lead',              'Cost Per Lead (CPL)',               'Cost Per Lead (CPL)',               'avg',   'currency', 15,      null,       'INFO'],
            ['cost_per_quotation',         'Cost Per Quotation (CPQ)',          'Cost Per Quotation (CPQ)',          'avg',   'currency', 75,      null,       'INFO'],
            ['total_value_complete',       'Total Value Complete (RM)',         'Total Value Complete (RM)',         'total', 'currency', null,    null,       'ZIKRI'],
            ['bilangan_papan',             'Bilangan Papan (PCS)',              'Number of Boards (PCS)',            'total', 'number',   null,    null,       'ZIKRI'],
            ['value_per_papan',            'Value Per Papan (RM)',              'Value Per Board (RM)',              'avg',   'currency', null,    null,       'INFO'],
        ],
    ];

    public function run(): void
    {
        $owners = Owner::pluck('id', 'name');
        // Sasaran disimpan untuk julat tahun penuh (keputusan D3 — data tiap tahun).
        $years = range(2023, 2032);

        foreach (self::DATA as $serviceKey => $rows) {
            $service = Service::where('key', $serviceKey)->firstOrFail();

            foreach ($rows as $index => [$key, $labelMs, $labelEn, $type, $valueType, $target, $targetText, $ownerName]) {
                $metric = CriticalMetric::updateOrCreate(
                    ['service_id' => $service->id, 'metric_key' => $key],
                    [
                        'label_ms' => $labelMs,
                        'label_en' => $labelEn,
                        'type' => $type,
                        'value_type' => $valueType,
                        'default_owner_id' => $owners[$ownerName] ?? null,
                        'sort_order' => $index + 1,
                    ]
                );

                foreach ($years as $year) {
                    CriticalMetricTarget::updateOrCreate(
                        ['critical_metric_id' => $metric->id, 'year' => $year],
                        ['monthly_target' => $target, 'target_text' => $targetText]
                    );
                }
            }
        }
    }
}
