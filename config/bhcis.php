<?php

return [
    'icd_sql_path' => env('BHCIS_ICD_SQL_PATH', ''),

    // Audit log retention: entries older than this many days are pruned by the
    // daily `model:prune` schedule (see routes/console.php).
    'audit_retention_days' => env('BHCIS_AUDIT_RETENTION_DAYS', 365),
];
