<?php
// Exit if accessed directly.
if (! defined('ABSPATH')) {
    exit;
}

/**
 * Defines the custom level map for the game.
 * Each level has a name and the minimum XP required to reach it.
 *
 * @return array The map of levels.
 */
function kiddoquest_get_level_map()
{
    return [
        // Tier 1: The Beginning (Level 1-5)
        1  => ['name' => 'Pemula Ceria',      'xp_required' => 0],
        2  => ['name' => 'Penjelajah Cilik',  'xp_required' => 200],
        3  => ['name' => 'Pemberani Mini',    'xp_required' => 1000],
        4  => ['name' => 'Kawan Bintang',     'xp_required' => 2500],
        5  => ['name' => 'Jagoan Mungil',     'xp_required' => 4500],

        // Tier 2: The Adventure (Level 6-10)
        6  => ['name' => 'Pencari Jejak',     'xp_required' => 6000],
        7  => ['name' => 'Ksatria Fajar',     'xp_required' => 7800],
        8  => ['name' => 'Ahli Rintangan',    'xp_required' => 9800],
        9  => ['name' => 'Master Peta',       'xp_required' => 12000],
        10 => ['name' => 'Penjaga Hutan',     'xp_required' => 15000],

        // Tier 3: The Mastery (Level 11-15)
        11 => ['name' => 'Pahlawan Siang',    'xp_required' => 18000],
        12 => ['name' => 'Kolektor Artefak',  'xp_required' => 21500],
        13 => ['name' => 'Pawang Monster Lucu', 'xp_required' => 25000],
        14 => ['name' => 'Ahli Puzzle',       'xp_required' => 29000],
        15 => ['name' => 'Kapten Tim Hebat',  'xp_required' => 33000],

        // Tier 4: The Heroics (Level 16-20)
        16 => ['name' => 'Bintang Kejora',    'xp_required' => 37500],
        17 => ['name' => 'Penakluk Gunung',   'xp_required' => 42500],
        18 => ['name' => 'Penyelam Samudra',  'xp_required' => 48000],
        19 => ['name' => 'Penerbang Angkasa', 'xp_required' => 54000],
        20 => ['name' => 'Juara Arena',       'xp_required' => 60000],

        // Tier 5: The Epic Quest (Level 21-30)
        21 => ['name' => 'Pelindung Alam',    'xp_required' => 66000],
        22 => ['name' => 'Utusan Bintang',    'xp_required' => 72500],
        23 => ['name' => 'Ahli Sejarah',      'xp_required' => 79500],
        24 => ['name' => 'Peramal Cuaca',     'xp_required' => 87000],
        25 => ['name' => 'Arsitek Impian',    'xp_required' => 95000],
        26 => ['name' => 'Kaisar Permen',     'xp_required' => 103000],
        27 => ['name' => 'Duta Ceria',        'xp_required' => 111000],
        28 => ['name' => 'Jenderal Bantal',   'xp_required' => 120000],
        29 => ['name' => 'Profesor Imajinasi', 'xp_required' => 130000],
        30 => ['name' => 'Penjaga Galaksi',   'xp_required' => 140000],

        // Tier 6: The Legend (Level 31-40)
        31 => ['name' => 'Pencipta Pelangi',  'xp_required' => 152000],
        32 => ['name' => 'Pawang Naga Imut',  'xp_required' => 165000],
        33 => ['name' => 'Sultan Mainan',     'xp_required' => 180000],
        34 => ['name' => 'Pustakawan Semesta', 'xp_required' => 197000],
        35 => ['name' => 'Dewa Waktu Main',   'xp_required' => 215000],
        36 => ['name' => 'Avatar Kebaikan',   'xp_required' => 235000],
        37 => ['name' => 'Kurir Luar Angkasa', 'xp_required' => 258000],
        38 => ['name' => 'Oracle Mimpi',      'xp_required' => 285000],
        39 => ['name' => 'Titan Cokelat',     'xp_required' => 315000],
        40 => ['name' => 'Legenda Abadi',     'xp_required' => 350000],
    ];
}
