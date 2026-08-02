<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrgLinkStyle;
use App\Enums\OrgNodeStyle;
use App\Models\OrgLink;
use App\Models\OrgNode;
use Illuminate\Database\Seeder;

/**
 * Carta organisasi DBENA — susunan rasmi.
 *
 * Koordinat diukur daripada carta itu dan bukan dikira. Setiap penyelia
 * duduk TEPAT di tengah antara anak-anaknya, dan Managing Director tepat
 * di atas Management Department. Susunan itu yang menjadikan carta boleh
 * diimbas: mata mengikut garisan menegak ke bawah tanpa perlu mencari.
 *
 * Warna membawa maksud. Marun ialah peringkat pengarah, ungu ialah
 * jabatan, biru laut ialah pengurus, dan teal ialah peranan sokongan atau
 * kontrak. Membaca carta ini bergantung pada kumpulan warna itu, jadi ia
 * ditetapkan di sini dan bukan dibiarkan kepada gaya lalai.
 *
 * Berundur sebaik ada nod — sync semula tidak sepatutnya membuang
 * kedudukan yang telah diseret dengan tangan. Untuk membina semula dengan
 * sengaja: php artisan dbena:carta-reset
 */
class OrgChartSeeder extends Seeder
{
    // Marun — peringkat pengarah.
    private const MD = '#3D0F2B';

    private const PENGARAH = '#5C1240';

    private const PENGARAH_2 = '#6B1F47';

    private const PENGARAH_3 = '#4A0F33';

    // Ungu — jabatan.
    private const JABATAN = '#4B2E83';

    // Biru laut — pengurus.
    private const PENGURUS = '#16345C';

    // Teal — freelancer.
    private const FREELANCER = '#3F7F82';

    // Teal gelap — sokongan berkontrak.
    private const SOKONGAN = '#123A38';

    public function run(): void
    {
        if (OrgNode::exists()) {
            return;
        }

        $n = 0;

        /*
         * Ikon dibiarkan kosong.
         *
         * Carta rasmi tiada ikon, dan lencana yang duduk di atas tepi
         * kotak akan menolak setiap kotak ke bawah sebanyak 16px —
         * memusnahkan penjajaran baris yang diukur dengan teliti di sini.
         */
        $buat = function (array $data) use (&$n): OrgNode {
            return OrgNode::create($data + ['icon' => null, 'sort_order' => ++$n]);
        };

        // ══ BARIS 1 — Managing Director ═══════════════════════════════
        // Tengah pada x=710, tepat di atas Management Department.
        $md = $buat([
            'title' => 'Managing Director', 'name' => 'AHMAD NIZAMUDDIN BIN ROSNAN',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::MD,
            'x' => 610, 'y' => 40, 'width' => 200, 'height' => 52,
        ]);

        // ══ BARIS 2 — Pengarah ════════════════════════════════════════
        $eksekutif = $buat([
            'title' => 'Executive Director', 'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::PENGARAH,
            'x' => 160, 'y' => 170, 'width' => 200, 'height' => 52,
        ]);

        $kontrak = $buat([
            'title' => 'Contract & Project Director', 'name' => 'MOHD HAFIZAN BIN ABDUL MAJID',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::PENGARAH_2,
            'x' => 940, 'y' => 170, 'width' => 200, 'height' => 52,
        ]);

        $operasi = $buat([
            'title' => 'Operation Director', 'name' => 'AZHARI BIN PUTEH',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::PENGARAH_3,
            'x' => 1250, 'y' => 170, 'width' => 200, 'height' => 52,
        ]);

        // ══ BARIS 3 — Jabatan ═════════════════════════════════════════
        $pemasaran = $buat([
            'title' => 'MARKETING DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'style' => OrgNodeStyle::Department->value, 'color' => self::JABATAN,
            'x' => 30, 'y' => 300, 'width' => 200, 'height' => 62,
        ]);

        $id = $buat([
            'title' => 'ID DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'AHMAD ZIKRI BIN ZAINAL',
            'style' => OrgNodeStyle::Department->value, 'color' => self::JABATAN,
            'x' => 300, 'y' => 300, 'width' => 180, 'height' => 62,
        ]);

        // Management Department turun ke baris jabatan dan melapor terus
        // kepada Managing Director.
        $pengurusan = $buat([
            'title' => 'MANAGEMENT DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'AHMAD NIZAMUDDIN BIN ROSNAN',
            'style' => OrgNodeStyle::Department->value, 'color' => self::JABATAN,
            'x' => 610, 'y' => 300, 'width' => 200, 'height' => 62,
        ]);

        $jabKontrak = $buat([
            'title' => 'CONTRACT DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'MOHD HAFIZAN BIN ABDUL MAJID',
            'style' => OrgNodeStyle::Department->value, 'color' => self::JABATAN,
            'x' => 940, 'y' => 300, 'width' => 200, 'height' => 62,
        ]);

        $projek = $buat([
            'title' => 'PROJECT DEPARTMENT', 'subtitle' => 'Head of Dept.',
            'name' => 'AZHARI BIN PUTEH',
            'style' => OrgNodeStyle::Department->value, 'color' => self::JABATAN,
            'x' => 1260, 'y' => 300, 'width' => 180, 'height' => 62,
        ]);

        // ══ BARIS 4 — Pengurus ════════════════════════════════════════
        $pengurusOperasi = $buat([
            'title' => 'Operation Manager', 'name' => 'AZMAN BIN ALIAS',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::PENGURUS,
            'x' => 300, 'y' => 430, 'width' => 180, 'height' => 52,
        ]);

        $hr = $buat([
            'title' => 'HR Manager', 'name' => 'Maznizar Izzatul Rizan Mohd Sedi',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::PENGURUS,
            'x' => 530, 'y' => 430, 'width' => 180, 'height' => 58,
        ]);

        $akaun = $buat([
            'title' => 'Freelancer Account',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::FREELANCER,
            'x' => 720, 'y' => 430, 'width' => 160, 'height' => 52,
        ]);

        // ══ BARIS 5 — Sokongan ════════════════════════════════════════
        $freelanceMarketing = $buat([
            'title' => 'Freelancer Marketing',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::FREELANCER,
            'x' => 45, 'y' => 560, 'width' => 170, 'height' => 52,
        ]);

        $konsultan = $buat([
            'title' => 'Design Consultant', 'name' => 'Nor Fatin Syamimi Makhatar',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::SOKONGAN,
            'x' => 300, 'y' => 560, 'width' => 180, 'height' => 58,
        ]);

        $qs = $buat([
            'title' => 'Quantity Surveyor', 'name' => '-Kosong-',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::SOKONGAN,
            'x' => 955, 'y' => 560, 'width' => 170, 'height' => 52,
        ]);

        $koordinator = $buat([
            'title' => 'Project Coordinator', 'name' => 'Affiful Najmi Mohd Masri',
            'style' => OrgNodeStyle::Executive->value, 'color' => self::SOKONGAN,
            'x' => 1265, 'y' => 560, 'width' => 170, 'height' => 52,
        ]);

        // ══ Garisan ═══════════════════════════════════════════════════
        // Semua pepejal dalam carta ini. Management Department disambung
        // TERUS kepada Managing Director, bukan melalui mana-mana pengarah.
        foreach ([
            [$md, $eksekutif], [$md, $pengurusan], [$md, $kontrak], [$md, $operasi],
            [$eksekutif, $pemasaran], [$eksekutif, $id],
            [$pengurusan, $hr], [$pengurusan, $akaun],
            [$id, $pengurusOperasi], [$pengurusOperasi, $konsultan],
            [$pemasaran, $freelanceMarketing],
            [$jabKontrak, $qs], [$kontrak, $jabKontrak],
            [$operasi, $projek], [$projek, $koordinator],
        ] as [$dari, $ke]) {
            OrgLink::create([
                'from_node_id' => $dari->id, 'to_node_id' => $ke->id,
                'style' => OrgLinkStyle::Solid->value,
            ]);
        }
    }
}
