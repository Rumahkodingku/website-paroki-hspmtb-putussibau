<?php

namespace App\Models;

use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One row of site configuration.
 *
 * The schema came from PRD 9.2 and Phase 01 section 8.2. There is no model for
 * it in the starter, so this is the first one, and it stays deliberately thin:
 * the interesting rules live in SiteSettingsService and config/site-settings.php
 * rather than here.
 *
 * `value` is deliberately not cast. The column is LONGTEXT and holds a mix of
 * text, digits and flags, so the service converts on the way out using the type
 * map. Casting on the model would hide that from callers.
 *
 * @property int $id
 * @property string $key
 * @property string|null $value
 * @property string|null $group
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable(['key', 'value', 'group'])]
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use HasFactory;
}
