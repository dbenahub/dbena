<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrgLinkStyle;
use App\Enums\OrgNodeStyle;
use App\Models\OrgLink;
use App\Models\OrgNode;
use Illuminate\Database\Seeder;

/**
 * Carta organisasi DBENA — susunan sebenar daripada carta rasmi.
 *
 * Koordinat diukur daripada carta itu dan bukan dikira. Setiap pengarah
 * duduk TEPAT di tengah antara anak-anaknya, dan Managing Director di
 * tengah antara empat pengarah. Susunan itu yang menjadikan carta boleh
 * diimbas: mata mengikut garisan menegak ke bawah tanpa perlu mencari.
 *
 * Kanvas kosong ialah cara paling pasti untuk memastikan ciri ini tidak
 * pernah digunakan. Bermula daripada carta yang betul bermakna kerja
 * pertama ialah pindaan kecil, bukan membina semula sesuatu yang sudah
 * wujud di atas kertas.
 *
 * Berundur sebaik ada nod — sync semula tidak sepatutnya membuang
 * kedudukan yang telah diseret dengan tangan. Untuk membina semula dengan
 * sengaja: php artisan dbena:carta-reset
 */
class OrgChartSeeder extends Seeder
{
    /** Tinggi mengikut gaya — bilangan baris berbeza. */
    private const H_EXEC = 66;

    private const H_DEPT = 80;

    private const H_SUPPORT = 58;

    public function run(): void
    {
        if (OrgNode::exists()) {
            return;
        }

        $n = 0;

        $buat = function (array $data) use (&$n): OrgNode {
            return OrgNode::create($data + ['sort_order' => ++$n]);
        };

        // ══ BARIS 1 — Managing Director ═══════════════════════════════
        // Tengah pada x=705, iaitu titik tengah empat pengarah di bawah.
        $md = $buat([
            'title' => 'Managing Director',
            'name' => 'AHMAD NIZAMUDDIN BIN ROSNAN',
            'icon' => 'ph-user-circle', 'style' => OrgNodeStyle::Executive->value,
            'x' => 500, 'y' => 180, 'width' => 410, 'height' => 76,
        ]);

        // ══ BARIS 2 — Empat pengarah ══════════════════════════════════
        // Setiap satu berpusat di tengah anak-anaknya.
        $eksekutif = $buat([
            'title' => 'Executive Director', 'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'icon' => 'ph-user', 'style' => OrgNodeStyle::Executive->value,
            'x' => 60, 'y' => 330, 'width' => 300, 'height' => self::H_EXEC,
        ]);

        $pengurusan = $buat([
            'title' => 'Management Department', 'subtitle' => 'Head of Dept.',
            'name' => 'AHMAD NIZAMUDDIN BIN ROSNAN',
            'icon' => 'ph-chart-bar', 'style' => OrgNodeStyle::Executive->value,
            'x' => 425, 'y' => 330, 'width' => 290, 'height' => 74,
        ]);

        $kontrak = $buat([
            'title' => 'Contract & Project Director', 'name' => 'MOHD HAFIZAN BIN ABDUL MAJID',
            'icon' => 'ph-briefcase', 'style' => OrgNodeStyle::Executive->value,
            'x' => 725, 'y' => 330, 'width' => 300, 'height' => self::H_EXEC,
        ]);

        $operasi = $buat([
            'title' => 'Operation Director', 'name' => 'AZHARI BIN PUTEH',
            'icon' => 'ph-hard-hat', 'style' => OrgNodeStyle::Executive->value,
            'x' => 1044, 'y' => 330, 'width' => 300, 'height' => self::H_EXEC,
        ]);

        // ══ BARIS 3 — Jabatan ═════════════════════════════════════════
        $pemasaran = $buat([
            'title' => 'MARKETING DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'icon' => 'ph-megaphone', 'style' => OrgNodeStyle::Department->value,
            'x' => 20, 'y' => 480, 'width' => 190, 'height' => self::H_DEPT,
        ]);

        $id = $buat([
            'title' => 'ID DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'icon' => 'ph-identification-card', 'style' => OrgNodeStyle::Department->value,
            'x' => 225, 'y' => 480, 'width' => 170, 'height' => self::H_DEPT,
        ]);

        $hr = $buat([
            'title' => 'HR Manager', 'name' => 'Maznizar Izzatul Rizan Mohd Sedi',
            'icon' => 'ph-users-three', 'style' => OrgNodeStyle::Department->value,
            'x' => 420, 'y' => 480, 'width' => 140, 'height' => self::H_DEPT,
        ]);

        $akaun = $buat([
            'title' => 'Freelancer Account',
            'icon' => 'ph-calculator', 'style' => OrgNodeStyle::Department->value,
            'x' => 575, 'y' => 480, 'width' => 150, 'height' => self::H_DEPT,
        ]);

        $jabKontrak = $buat([
            'title' => 'CONTRACT DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'MOHD HAFIZAN BIN ABDUL MAJID',
            'icon' => 'ph-file-text', 'style' => OrgNodeStyle::Department->value,
            'x' => 770, 'y' => 480, 'width' => 210, 'height' => self::H_DEPT,
        ]);

        $projek = $buat([
            'title' => 'PROJECT DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'AZHARI BIN PUTEH',
            'icon' => 'ph-folder', 'style' => OrgNodeStyle::Department->value,
            'x' => 1000, 'y' => 480, 'width' => 180, 'height' => self::H_DEPT,
        ]);

        $produksi = $buat([
            'title' => 'PRODUCTION DEPARTMENT', 'name' => '-DBENA INDUSTRIES SDN BHD-',
            'icon' => 'ph-buildings', 'style' => OrgNodeStyle::Department->value,
            'x' => 1195, 'y' => 480, 'width' => 205, 'height' => self::H_DEPT,
        ]);

        // ══ BARIS 4 — Sokongan ════════════════════════════════════════
        $freelanceMarketing = $buat([
            'title' => 'Freelancer Marketing',
            'icon' => 'ph-user', 'style' => OrgNodeStyle::Support->value,
            'x' => 25, 'y' => 640, 'width' => 180, 'height' => self::H_SUPPORT,
        ]);

        $pengurusOperasi = $buat([
            'title' => 'Operation Manager', 'name' => 'AZMAN BIN ALIAS',
            'icon' => 'ph-gear', 'style' => OrgNodeStyle::Executive->value,
            'x' => 210, 'y' => 610, 'width' => 200, 'height' => self::H_EXEC,
        ]);

        $konsultan = $buat([
            'title' => 'Design Consultant', 'name' => 'NOR FATIN SYAMIMI MAKHTAR',
            'icon' => 'ph-pencil-simple', 'style' => OrgNodeStyle::Support->value,
            'x' => 215, 'y' => 720, 'width' => 210, 'height' => self::H_SUPPORT,
        ]);

        $qs = $buat([
            'title' => 'Quantity Surveyor', 'name' => '-Kosong-',
            'icon' => 'ph-chart-bar', 'style' => OrgNodeStyle::Department->value,
            'x' => 790, 'y' => 640, 'width' => 170, 'height' => 70,
        ]);

        $koordinator = $buat([
            'title' => 'Project Coordinator', 'name' => 'Affiful Najmi Mohd Masri',
            'icon' => 'ph-users-three', 'style' => OrgNodeStyle::Department->value,
            'x' => 1005, 'y' => 640, 'width' => 170, 'height' => 70,
        ]);

        // ══ Garisan pepejal — pelaporan langsung ══════════════════════
        foreach ([
            [$md, $eksekutif], [$md, $pengurusan], [$md, $kontrak], [$md, $operasi],
            [$eksekutif, $pemasaran], [$eksekutif, $id],
            [$pengurusan, $hr], [$pengurusan, $akaun],
            [$kontrak, $jabKontrak], [$jabKontrak, $qs],
            [$operasi, $projek], [$operasi, $produksi], [$projek, $koordinator],
            [$id, $pengurusOperasi],
        ] as [$dari, $ke]) {
            OrgLink::create([
                'from_node_id' => $dari->id, 'to_node_id' => $ke->id,
                'style' => OrgLinkStyle::Solid->value,
            ]);
        }

        // ══ Garisan putus-putus — sokongan / kontrak ══════════════════
        // Melukisnya pepejal bermakna carta mendakwa mereka melapor secara
        // langsung, yang mengubah maksud carta.
        foreach ([
            [$pemasaran, $freelanceMarketing],
            [$pengurusOperasi, $konsultan],
        ] as [$dari, $ke]) {
            OrgLink::create([
                'from_node_id' => $dari->id, 'to_node_id' => $ke->id,
                'style' => OrgLinkStyle::Dashed->value,
            ]);
        }
    }
}
