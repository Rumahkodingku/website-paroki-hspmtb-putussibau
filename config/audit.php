<?php

/*
|--------------------------------------------------------------------------
| Audit Log
|--------------------------------------------------------------------------
|
| The audit log is an allowlist system. Only the modules, actions, fields and
| metadata declared here can reach the database; the logger refuses anything
| it does not recognise. Adding a field to a model is therefore not enough to
| have it audited, which is deliberate: a snapshot that grows with every
| migration is how a password or a file path ends up in a log.
|
| Labels live in lang/id/audit.php. This file holds the stable codes, which are
| what filters and code compare against.
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Modules
    |--------------------------------------------------------------------------
    */

    'modules' => [
        'news',
        'agenda',
        'mass_schedules',
        'special_masses',
        'schedule_notices',
        'parish_profile',
        'parish_histories',
        'pastors',
        'pastor_assignments',
        'parish_boards',
        'parish_board_members',
        'parish_service_area',
        'services',
        'service_requirements',
        'service_steps',
        'service_faqs',
        'settings',
        'users',
        'media',
        'documents',
        'document_categories',
        'post_categories',
        'event_categories',
        'service_categories',
        'community_categories',
        'communities',
        'areas',
        'locations',
        'galleries',
        'gallery_photos',
    ],

    /*
    |--------------------------------------------------------------------------
    | Actions
    |--------------------------------------------------------------------------
    */

    'actions' => [
        'created',
        'updated',
        'settings_updated',
        'published',
        'unpublished',
        'scheduled',
        'archived',
        'cancelled',
        'postponed',
        'deleted',
        'restored',
        'activated',
        'deactivated',
        'reordered',
        'attached',
        'duplicated',
        'upload_accepted',
        'media_processing_completed',
        'media_processing_failed',
        'storage_delete_failed',
        'login_succeeded',
        'login_failed',
        'logout',
        'profile_updated',
        'email_changed',
        'password_changed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Results
    |--------------------------------------------------------------------------
    */

    'results' => [
        'success',
        'failure',
        'denied',
    ],

    /*
    |--------------------------------------------------------------------------
    | Field allowlist
    |--------------------------------------------------------------------------
    |
    | These are the only model attributes that may appear in before/after. The
    | list is intentionally narrower than each model's fillable list.
    |
    */

    'fields' => [
        'news' => [
            'title', 'slug', 'excerpt', 'status', 'published_at',
            'post_category_id', 'is_pinned', 'author_name', 'media_id',
            'image_alt', 'scripture_reference', 'meta_title', 'meta_description',
        ],
        'agenda' => [
            'title', 'slug', 'start_at', 'end_at', 'is_all_day',
            'event_category_id', 'location_id', 'location_name', 'status',
            'is_published', 'media_id', 'poster_alt',
        ],
        'mass_schedules' => [
            'name', 'day_of_week', 'start_time', 'location_id', 'notes', 'is_active',
        ],
        'special_masses' => [
            'title', 'start_at', 'end_at', 'location_id', 'notes', 'is_active',
        ],
        'schedule_notices' => [
            'title', 'message', 'location_id', 'mass_schedule_id',
            'special_mass_id', 'starts_at', 'ends_at', 'is_active',
        ],
        'parish_profile' => [
            'description', 'established_year', 'cover_media_id', 'cover_alt', 'video_url',
        ],
        'parish_histories' => [
            'title', 'year', 'date', 'description', 'narrative', 'media_id',
            'image_alt', 'sort_order', 'is_published',
        ],
        'pastors' => [
            'name', 'title', 'photo_media_id', 'photo_alt', 'bio', 'sort_order', 'is_published',
        ],
        'pastor_assignments' => [
            'pastor_id', 'position', 'start_year', 'end_year', 'is_current', 'description',
        ],
        'parish_boards' => [
            'name', 'start_year', 'end_year', 'description', 'is_active',
            'is_published', 'sort_order',
        ],
        'parish_board_members' => [
            'parish_board_period_id', 'name', 'position', 'photo_media_id',
            'photo_alt', 'description', 'sort_order', 'is_published',
        ],
        'parish_service_area' => [
            'content', 'map_media_id', 'map_url', 'is_published',
        ],
        'services' => [
            'title', 'slug', 'service_category_id', 'thumbnail_media_id',
            'thumbnail_alt', 'summary', 'introduction', 'audience_info',
            'preparation_info', 'schedule_info', 'fee_info', 'contact_name',
            'contact_phone', 'form_url', 'form_button_label', 'is_form_open',
            'form_closed_message', 'is_emergency', 'is_active', 'sort_order',
        ],
        'service_requirements' => [
            'service_id', 'text', 'note', 'requirement_condition', 'applies_to', 'sort_order',
        ],
        'service_steps' => [
            'service_id', 'title', 'description', 'sort_order',
        ],
        'service_faqs' => [
            'service_id', 'question', 'answer', 'sort_order',
        ],
        'users' => [
            'name', 'email', 'is_active',
        ],
        'media' => [
            'original_name', 'mime_type', 'file_size', 'width', 'height', 'alt_text', 'status',
        ],
        'documents' => [
            'title', 'document_category_id', 'description', 'version_label',
            'document_date', 'expires_at', 'status', 'is_featured', 'published_at',
            'original_filename', 'mime_type', 'file_size',
        ],
        'document_categories' => [
            'name', 'slug', 'description', 'sort_order', 'is_active',
        ],
        'post_categories' => [
            'name', 'slug', 'sort_order',
        ],
        'event_categories' => [
            'name', 'slug', 'color',
        ],
        'service_categories' => [
            'name', 'slug', 'description', 'sort_order', 'is_active',
        ],
        'community_categories' => [
            'name', 'slug', 'description', 'sort_order', 'is_active',
        ],
        'communities' => [
            'community_category_id', 'name', 'slug', 'logo_media_id', 'logo_alt',
            'thumbnail_media_id', 'thumbnail_alt', 'description', 'vision',
            'mission', 'meeting_schedule', 'meeting_location', 'eligibility',
            'joining_info', 'contact_name', 'contact_phone', 'show_contact',
            'facebook', 'instagram', 'tiktok', 'youtube', 'website', 'status',
            'is_published', 'published_at',
        ],
        'areas' => [
            'parent_id', 'name', 'slug', 'type', 'description', 'address',
            'maps_url', 'latitude', 'longitude', 'leader_name', 'contact_phone',
            'show_contact', 'sort_order', 'is_active',
        ],
        'locations' => [
            'name', 'slug', 'type', 'address', 'latitude', 'longitude',
            'maps_url', 'notes', 'is_active',
        ],
        'galleries' => [
            'title', 'slug', 'description', 'event_date', 'location',
            'cover_photo_id', 'is_published', 'published_at',
        ],
        'gallery_photos' => [
            'gallery_album_id', 'media_id', 'caption', 'alt_text', 'sort_order',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metadata allowlist
    |--------------------------------------------------------------------------
    |
    | Callers may build metadata, but only these keys survive. There is no
    | endpoint that accepts metadata from the browser.
    |
    */

    'metadata' => [
        'settings' => ['setting_keys_changed', 'summarized_fields', 'truncated_fields'],
        'media' => ['media_id', 'original_name', 'mime_type', 'file_size', 'job_name', 'error_class', 'variants', 'reason_code'],
        'documents' => ['file_replaced', 'file_mime_type', 'file_size', 'reason_code'],
        'news' => ['duplicated_from', 'count', 'post_ids'],
        'agenda' => ['duplicated_from'],
        'mass_schedules' => ['source_id', 'destination_location_id', 'count', 'ids'],
        'galleries' => ['photo_ids', 'count'],
        'gallery_photos' => ['count', 'ids'],
        'services' => ['count', 'ids'],
        'service_requirements' => ['count', 'ids'],
        'service_steps' => ['count', 'ids'],
        'service_faqs' => ['count', 'ids'],
        'pastor_assignments' => ['affected_ids'],
        'parish_boards' => ['affected_ids'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Summarized fields
    |--------------------------------------------------------------------------
    |
    | Rich text and long prose are represented by a length marker instead of
    | their full value. A privacy policy re-read on every save would otherwise
    | grow the audit table by a page of HTML each time.
    |
    */

    'summarize' => [
        'settings' => ['privacy_policy_content', 'terms_and_conditions_content'],
        'parish_profile' => ['description'],
        'parish_histories' => ['description', 'narrative'],
        'pastors' => ['bio'],
        'pastor_assignments' => ['description'],
        'parish_boards' => ['description'],
        'parish_board_members' => ['description'],
        'parish_service_area' => ['content'],
        'services' => [
            'summary', 'introduction', 'audience_info', 'preparation_info',
            'schedule_info', 'fee_info',
        ],
        'service_steps' => ['description'],
        'service_faqs' => ['answer'],
        'mass_schedules' => ['notes'],
        'special_masses' => ['notes'],
        'schedule_notices' => ['message'],
        'locations' => ['notes'],
        'document_categories' => ['description'],
        'service_categories' => ['description'],
        'community_categories' => ['description'],
        'communities' => [
            'description', 'vision', 'mission', 'eligibility', 'joining_info',
        ],
        'areas' => ['description'],
        'galleries' => ['description'],
        'documents' => ['description'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Redaction
    |--------------------------------------------------------------------------
    */

    'redact_keys' => [
        'password',
        'password_confirmation',
        'current_password',
        'token',
        'secret',
        'api_key',
        'authorization',
        'cookie',
        'session',
        'credential',
        'remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Query and limits
    |--------------------------------------------------------------------------
    */

    'search_fields' => ['description', 'subject_label', 'actor_name'],

    'snapshot' => [
        'max_value_length' => 1000,
        'max_fields' => 30,
    ],

    'pagination' => [
        'default' => 10,
        'allowed' => [10, 15, 25, 50],
    ],

    'max_date_range_days' => 366,
    'retention_months' => 12,
    'user_agent_max_length' => 500,

];
