<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Service;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Eksport Master List of Project sebagai CSV.
 *
 * Admin sahaja. Fail ini membawa senarai pelanggan penuh — nama, telefon,
 * emel, alamat dan nilai kontrak — keluar daripada sistem dan masuk ke
 * folder muat turun seseorang. Itu keputusan pentadbiran, bukan
 * kemudahan paparan.
 */
class ProjectExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $this->authorize('export-projects');

        $serviceKey = $request->string('servis')->value() ?: null;
        $service = $serviceKey ? Service::where('key', $serviceKey)->first() : null;

        $projects = Project::query()
            ->with('service')
            ->forService($service?->id)
            ->withStatus($request->string('status')->value() ?: null)
            ->search($request->string('cari')->value())
            ->orderBy('project_date', 'desc')
            ->get();

        $filename = sprintf(
            'projek-%s-%s.csv',
            $service ? $service->key : 'semua',
            now()->format('Y-m-d')
        );

        return response()->streamDownload(function () use ($projects): void {
            $out = fopen('php://output', 'wb');

            // BOM UTF-8 supaya Excel memaparkan aksara Melayu dengan betul.
            // Tanpanya, "Ubah Suai" menjadi teks bercelaru dan orang
            // menganggap eksport itu rosak.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                __('project.col.code'),
                __('project.col.date'),
                __('project.col.client'),
                __('project.col.pic'),
                __('project.col.service'),
                __('project.col.phone'),
                __('project.col.address'),
                __('project.col.email'),
                __('project.col.contract'),
                __('project.col.vo'),
                __('project.col.status'),
            ]);

            foreach ($projects as $project) {
                fputcsv($out, [
                    $project->code,
                    $project->project_date?->format('Y-m-d'),
                    $project->client_name,
                    $project->pic_sales,
                    $project->service->name,
                    $project->phone,
                    $project->address,
                    $project->email,
                    (float) $project->contract_amount,
                    (float) $project->variation_order,
                    $project->status->label(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
