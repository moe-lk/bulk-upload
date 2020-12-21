<?php

use Maatwebsite\Excel\Excel;

return [

    'exports' => [

        /*
        |--------------------------------------------------------------------------
        | Chunk size
        |--------------------------------------------------------------------------
        |
        | When using FromQuery, the query is automatically chunked.
        | Here you can specify how big the chunk should be.
        |
        */
        'chunk_size'             => 1000,

        /*
        |--------------------------------------------------------------------------
        | Pre-calculate formulas during export
        |--------------------------------------------------------------------------
        */
        'pre_calculate_formulas' => false,

        /*
        |--------------------------------------------------------------------------
        | CSV Settings
        |--------------------------------------------------------------------------
        |
        | Configure e.g. delimiter, enclosure and line ending for CSV exports.
        |
        */
        'csv'                    => [
            'delimiter'              => ',',
            'enclosure'              => '"',
            'line_ending'            => PHP_EOL,
            'use_bom'                => false,
            'include_separator_line' => false,
            'excel_compatibility'    => false,
        ],
    ],
    'import' => [
        'start_row' => 2,
        'chunk_size'=> 40,
        'ignore_empty' => true,
        'ignoreEmpty' => true
    ],
    'imports'            => [

        'read_only' => true,
        'start_row' => 2,
        'ignore_empty' => true,
        'ignoreEmpty' => true,

        'heading_row' => [

            /*
            |--------------------------------------------------------------------------
            | Heading Row Formatter
            |--------------------------------------------------------------------------
            |
            | Configure the heading row formatter.
            | Available options: none|slug|custom
            |
            */
            'formatter' => 'slug',
        ],

        /*
        |--------------------------------------------------------------------------
        | CSV Settings
        |--------------------------------------------------------------------------
        |
        | Configure e.g. delimiter, enclosure and line ending for CSV imports.
        |
        */
        'csv'         => [
            'delimiter'              => ',',
            'enclosure'              => '"',
            'line_ending'            => PHP_EOL,
            'use_bom'                => false,
            'include_separator_line' => false,
            'excel_compatibility'    => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Extension detector
    |--------------------------------------------------------------------------
    |
    | Configure here which writer type should be used when
    | the package needs to guess the correct type
    | based on the extension alone.
    |
    */
    'extension_detector' => [
        'xlsx'     => Excel::XLSX,
        'xlsm'     => Excel::XLSX,
        'xltx'     => Excel::XLSX,
        'xltm'     => Excel::XLSX,
        'xls'      => Excel::XLS,
        'xlt'      => Excel::XLS,
        'ods'      => Excel::ODS,
        'ots'      => Excel::ODS,
        'slk'      => Excel::SLK,
        'xml'      => Excel::XML,
        'gnumeric' => Excel::GNUMERIC,
        'htm'      => Excel::HTML,
        'html'     => Excel::HTML,
        'csv'      => Excel::CSV,
        'tsv'      => Excel::TSV,

        /*
        |--------------------------------------------------------------------------
        | PDF Extension
        |--------------------------------------------------------------------------
        |
        | Configure here which Pdf driver should be used by default.
        | Available options: Excel::MPDF | Excel::TCPDF | Excel::DOMPDF
        |
        */
        'pdf'      => Excel::DOMPDF,
    ],

    'value_binder' => [

        /*
        |--------------------------------------------------------------------------
        | Default Value Binder
        |--------------------------------------------------------------------------
        |
        | PhpSpreadsheet offers a way to hook into the process of a value being
        | written to a cell. In there some assumptions are made on how the
        | value should be formatted. If you want to change those defaults,
        | you can implement your own default value binder.
        |
        */
        'default' => Maatwebsite\Excel\DefaultValueBinder::class,
    ],

    'transactions' => [

        /*
        |--------------------------------------------------------------------------
        | Transaction Handler
        |--------------------------------------------------------------------------
        |
        | By default the import is wrapped in a transaction. This is useful
        | for when an import may fail and you want to retry it. With the
        | transactions, the previous import gets rolled-back.
        |
        | You can disable the transaction handler by setting this to null.
        | Or you can choose a custom made transaction handler here.
        |
        | Supported handlers: null|db
        |
        */
        'handler' => 'db',
    ],

    'temporary_files' => [

        /*
        |--------------------------------------------------------------------------
        | Local Temporary Path
        |--------------------------------------------------------------------------
        |
        | When exporting and importing files, we use a temporary file, before
        | storing reading or downloading. Here you can customize that path.
        |
        */
        'local_path'  => sys_get_temp_dir(),

        /*
        |--------------------------------------------------------------------------
        | Remote Temporary Disk
        |--------------------------------------------------------------------------
        |
        | When dealing with a multi server setup with queues in which you
        | cannot rely on having a shared local temporary path, you might
        | want to store the temporary file on a shared disk. During the
        | queue executing, we'll retrieve the temporary file from that
        | location instead. When left to null, it will always use
        | the local path. This setting only has effect when using
        | in conjunction with queued imports and exports.
        |
        */
        'remote_disk' => null,

    ],

    'columns' => [
        "remarks",
        "student_id",
        "full_name" ,
        "preferred_name",
        "gender_mf",
        "date_of_birth_yyyy_mm_dd",
        "address",
        "birth_registrar_office_as_in_birth_certificate",
        "birth_divisional_secretariat",
        "nationality",
        "identity_type",
        "identity_number",
        "special_need_type",
        "special_need",
        "bmi_academic_period",
        "bmi_date_yyyy_mm_dd",
        "bmi_height",
        "bmi_weight",
        "admission_no",
        "academic_period",
        "education_grade",
        "start_date_yyyy_mm_dd",
        "option_1",
        "option_2",
        "option_3",
        "option_4",
        "option_5",
        "option_6",
        "option_7",
        "option_8",
        "option_9",
        "option_10",
        "option_11",
        "option_12",
        "option_13",
        "option_14",
        "option_15",
        "option_16",
        "fathers_full_name",
        "fathers_date_of_birth_yyyy_mm_dd",
        "fathers_address",
        "fathers_address_area",
        "fathers_nationality",
        "fathers_identity_type",
        "fathers_identity_number",
        "fathers_phone",
        "mothers_full_name",
        "mothers_date_of_birth_yyyy_mm_dd",
        "mothers_address",
        "mothers_address_area",
        "mothers_nationality",
        "mothers_identity_type",
        "mothers_identity_number",
        "mothers_phone",
        "guardians_full_name",
        "name_with_initials",
        "guardians_gender_mf",
        "guardians_date_of_birth_yyyy_mm_dd",
        "guardians_address",
        "guardians_address_area",
        "guardians_nationality",
        "guardians_identity_type",
        "guardians_identity_number",
        "guardians_phone"
    ],
    'optional_columns' => [
        "preferred_name",
        //"identity_type",
        "academic_period",
        "guardians_phone",
        "fathers_phone",
        "mothers_phone",
        "option_1",
        "option_2",
        "option_3",
        "option_4",
        "option_5",
        "option_6",
        "option_7",
        "option_8",
        "option_9",
        "option_10",
        "option_11",
        "option_12",
        "option_13",
        "option_14",
        "option_15",
        "option_16"
    ]
];
