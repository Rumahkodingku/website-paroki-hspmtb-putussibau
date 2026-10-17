<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Documents are private. They are never written to the public disk and no
    | URL to this disk is ever sent to the browser.
    |
    */

    'disk' => env('DOCUMENTS_DISK', 'local'),

    'path' => 'documents',

    /*
    |--------------------------------------------------------------------------
    | Upload limits
    |--------------------------------------------------------------------------
    |
    | PRD DL-01: 10 MB per file. The host's upload_max_filesize and
    | post_max_size must be at least this large; .env cannot change them.
    |
    */

    'max_upload_kilobytes' => 10240,

    'max_filename_length' => 150,

    /*
    |--------------------------------------------------------------------------
    | Accepted types
    |--------------------------------------------------------------------------
    |
    | The map is canonical MIME => extension. finfo reports OOXML files as
    | application/zip, so the rule below validates the ZIP contents and stores
    | the canonical OOXML MIME from this map.
    |
    */

    'extensions' => [
        'application/pdf' => 'pdf',
        'application/msword' => 'doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
        'application/vnd.ms-powerpoint' => 'ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
        'application/zip' => 'zip',
        'application/x-zip-compressed' => 'zip',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Office signatures
    |--------------------------------------------------------------------------
    |
    | .doc/.xls/.ppt are OLE2 containers. libmagic reports some of them as
    | application/x-ole-storage or application/CDFV2, not as the Office MIME
    | type, so the canonical type is chosen from the extension after the
    | detection has been checked against the allowed list.
    |
    */

    'legacy_office' => [
        'doc' => [
            'mime' => 'application/msword',
            'mimes' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2'],
        ],
        'xls' => [
            'mime' => 'application/vnd.ms-excel',
            'mimes' => ['application/vnd.ms-excel', 'application/x-ole-storage', 'application/CDFV2'],
        ],
        'ppt' => [
            'mime' => 'application/vnd.ms-powerpoint',
            'mimes' => ['application/vnd.ms-powerpoint', 'application/x-ole-storage', 'application/CDFV2'],
        ],
    ],

    'mime_types' => [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-zip-compressed',
        'application/x-ole-storage',
        'application/CDFV2',
    ],

    /*
    |--------------------------------------------------------------------------
    | OOXML signatures
    |--------------------------------------------------------------------------
    |
    | A .docx/.xlsx/.pptx renamed from a plain ZIP must not pass. Each entry is
    | the canonical MIME stored on the row and the archive members that prove
    | the file is the format its extension claims.
    |
    */

    'ooxml' => [
        'docx' => [
            'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'entries' => ['[Content_Types].xml', 'word/document.xml'],
        ],
        'xlsx' => [
            'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'entries' => ['[Content_Types].xml', 'xl/workbook.xml'],
        ],
        'pptx' => [
            'mime' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'entries' => ['[Content_Types].xml', 'ppt/presentation.xml'],
        ],
    ],

    'preview_mime' => 'application/pdf',

];
