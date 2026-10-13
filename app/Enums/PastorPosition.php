<?php

namespace App\Enums;

/**
 * Jabatan seorang pastor dalam satu periode penugasan.
 *
 * PRD section 9.4 menamai tiga nilai `clergy.position`; Phase 6A memindahkan
 * jabatan dari identitas orang (tabel `pastors`) ke penugasannya (tabel
 * `pastor_assignments`), karena orang yang sama dapat bertugas dengan jabatan
 * berbeda pada periode berbeda (D-52).
 *
 * Label dibaca dari `lang/id/profil.php`, bukan hardcoded, sehingga jabatan
 * baru tidak bisa muncul di antarmuka tanpa seseorang menuliskan namanya.
 *
 * @see docs/DECISIONS.md D-52
 */
enum PastorPosition: string
{
    /** Pastor paroki yang mengepalai paroki. */
    case PastorParoki = 'pastor_paroki';

    /** Pastor rekan/rekan pastor yang membantu. */
    case PastorRekan = 'pastor_rekan';

    /** Jabatan lain, mis. diakon atau tugas khusus. */
    case Lainnya = 'lainnya';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The interface label, read from the language file rather than hardcoded.
     */
    public function label(): string
    {
        return (string) __('profil.positions.'.$this->value);
    }
}
