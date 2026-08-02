<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrgLinkStyle;
use App\Enums\OrgNodeStyle;
use App\Models\OrgLink;
use App\Models\OrgNode;
use Illuminate\Database\Seeder;

/**
 * Carta organisasi DBENA sebenar sebagai titik permulaan.
 *
 * Kanvas kosong ialah cara paling pasti untuk memastikan ciri ini tidak
 * pernah digunakan. Bermula daripada carta yang betul bermakna kerja
 * pertama ialah pindaan kecil, bukan membina semula sesuatu yang sudah
 * wujud di atas kertas.
 *
 * Dijalankan sekali sahaja. Selepas ada nod, seeder ini berundur — sync
 * semula tidak sepatutnya membuang kedudukan yang telah diseret dengan
 * tangan.
 */
class OrgChartSeeder extends Seeder
{
    public function run(): void
    {
        if (OrgNode::exists()) {
            return;
        }

        $node = function (array $data): OrgNode {
            return OrgNode::create($data);
        };

        // ── Baris 1: Managing Director ────────────────────────────────
        $md = $node([
            'title' => 'Managing Director', 'name' => 'AHMAD NIZAMUDDIN BIN ROSNAN',
            'icon' => 'ph-user-circle', 'style' => OrgNodeStyle::Executive->value,
            'x' => 460, 'y' => 40, 'width' => 300, 'sort_order' => 1,
        ]);

        // ── Baris 2: Empat pengarah ───────────────────────────────────
        $eksekutif = $node([
            'title' => 'Executive Director', 'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'icon' => 'ph-user', 'style' => OrgNodeStyle::Executive->value,
            'x' => 40, 'y' => 190, 'width' => 250, 'sort_order' => 2,
        ]);

        $pengurusan = $node([
            'title' => 'Management Department', 'name' => 'AHMAD NIZAMUDDIN BIN ROSNAN',
            'icon' => 'ph-chart-bar', 'style' => OrgNodeStyle::Executive->value,
            'x' => 320, 'y' => 190, 'width' => 250, 'sort_order' => 3,
        ]);

        $kontrak = $node([
            'title' => 'Contract & Project Director', 'name' => 'MOHD HAFIZAN BIN ABDUL MAJID',
            'icon' => 'ph-briefcase', 'style' => OrgNodeStyle::Executive->value,
            'x' => 600, 'y' => 190, 'width' => 250, 'sort_order' => 4,
        ]);

        $operasi = $node([
            'title' => 'Operation Director', 'name' => 'AZHARI BIN PUTEH',
            'icon' => 'ph-hard-hat', 'style' => OrgNodeStyle::Executive->value,
            'x' => 880, 'y' => 190, 'width' => 250, 'sort_order' => 5,
        ]);

        // ── Baris 3: Jabatan ──────────────────────────────────────────
        $pemasaran = $node([
            'title' => 'MARKETING DEPARTMENT', 'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'icon' => 'ph-megaphone', 'style' => OrgNodeStyle::Department->value,
            'x' => 20, 'y' => 330, 'width' => 200, 'sort_order' => 6,
        ]);

        $id = $node([
            'title' => 'ID DEPARTMENT', 'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'icon' => 'ph-identification-card', 'style' => OrgNodeStyle::Department->value,
            'x' => 240, 'y' => 330, 'width' => 200, 'sort_order' => 7,
        ]);

        $hr = $node([
            'title' => 'HR Manager', 'name' => 'Maznizar Izzatul Rizan Mohd Sedi',
            'icon' => 'ph-users-three', 'style' => OrgNodeStyle::Department->value,
            'x' => 460, 'y' => 330, 'width' => 170, 'sort_order' => 8,
        ]);

        $akaun = $node([
            'title' => 'Freelancer Account', 'name' => null,
            'icon' => 'ph-calculator', 'style' => OrgNodeStyle::Department->value,
            'x' => 650, 'y' => 330, 'width' => 150, 'sort_order' => 9,
        ]);

        $jabKontrak = $node([
            'title' => 'CONTRACT DEPARTMENT', 'name' => 'MOHD HAFIZAN BIN ABDUL MAJID',
            'icon' => 'ph-file-text', 'style' => OrgNodeStyle::Department->value,
            'x' => 820, 'y' => 330, 'width' => 210, 'sort_order' => 10,
        ]);

        $projek = $node([
            'title' => 'PROJECT DEPARTMENT', 'name' => 'AZHARI BIN PUTEH',
            'icon' => 'ph-folder', 'style' => OrgNodeStyle::Department->value,
            'x' => 1050, 'y' => 330, 'width' => 200, 'sort_order' => 11,
        ]);

        $produksi = $node([
            'title' => 'PRODUCTION DEPARTMENT', 'name' => '-DBENA INDUSTRIES SDN BHD-',
            'icon' => 'ph-buildings', 'style' => OrgNodeStyle::Department->value,
            'x' => 1270, 'y' => 330, 'width' => 210, 'sort_order' => 12,
        ]);

        // ── Baris 4: Sokongan & freelancer ────────────────────────────
        $freelanceMarketing = $node([
            'title' => 'Freelancer Marketing', 'name' => null,
            'icon' => 'ph-user', 'style' => OrgNodeStyle::Support->value,
            'x' => 20, 'y' => 500, 'width' => 190, 'sort_order' => 13,
        ]);

        $pengurusOperasi = $node([
            'title' => 'Operation Manager', 'name' => 'AZMAN BIN ALIAS',
            'icon' => 'ph-gear', 'style' => OrgNodeStyle::Executive->value,
            'x' => 240, 'y' => 460, 'width' => 200, 'sort_order' => 14,
        ]);

        $konsultan = $node([
            'title' => 'Design Consultant', 'name' => 'NOR FATIN SYAMIMI MAKHTAR',
            'icon' => 'ph-pencil-simple', 'style' => OrgNodeStyle::Support->value,
            'x' => 250, 'y' => 560, 'width' => 200, 'sort_order' => 15,
        ]);

        $qs = $node([
            'title' => 'Quantity Surveyor', 'name' => '-Kosong-',
            'icon' => 'ph-chart-bar', 'style' => OrgNodeStyle::Department->value,
            'x' => 850, 'y' => 490, 'width' => 180, 'sort_order' => 16,
        ]);

        $koordinator = $node([
            'title' => 'Project Coordinator', 'name' => 'Affiful Najmi Mohd Masri',
            'icon' => 'ph-users-three', 'style' => OrgNodeStyle::Department->value,
            'x' => 1060, 'y' => 490, 'width' => 190, 'sort_order' => 17,
        ]);

        // ── Garisan ───────────────────────────────────────────────────
        $pepejal = [
            [$md, $eksekutif], [$md, $pengurusan], [$md, $kontrak], [$md, $operasi],
            [$eksekutif, $pemasaran], [$eksekutif, $id],
            [$pengurusan, $hr], [$pengurusan, $akaun],
            [$kontrak, $jabKontrak], [$jabKontrak, $qs],
            [$operasi, $projek], [$operasi, $produksi], [$projek, $koordinator],
            [$id, $pengurusOperasi],
        ];

        foreach ($pepejal as [$dari, $ke]) {
            OrgLink::create([
                'from_node_id' => $dari->id,
                'to_node_id' => $ke->id,
                'style' => OrgLinkStyle::Solid->value,
            ]);
        }

        // Freelancer dan konsultan disambung dengan garisan putus-putus.
        // Melukisnya pepejal bermakna carta mendakwa mereka melapor secara
        // langsung, yang mengubah maksud carta.
        $putus = [
            [$pemasaran, $freelanceMarketing],
            [$pengurusOperasi, $konsultan],
        ];

        foreach ($putus as [$dari, $ke]) {
            OrgLink::create([
                'from_node_id' => $dari->id,
                'to_node_id' => $ke->id,
                'style' => OrgLinkStyle::Dashed->value,
            ]);
        }
    }
}
