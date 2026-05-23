<?php

$dumpContent = file_get_contents("c:/Users/Admin/Downloads/bin_sultan.sql");
preg_match_all("/CREATE TABLE \`(.*?)\`(.*?)\;/is", $dumpContent, $matches);
$dumpTables = array_combine($matches[1], $matches[2]);

$migrationsPath = "c:/Users/Admin/Downloads/Bin_sultan_sweets/database/migrations";
$migrationFiles = scandir($migrationsPath);

$issues = [];
foreach ($dumpTables as $tableName => $tableContent) {
    if (in_array($tableName, ["migrations", "model_has_permissions", "model_has_roles", "permissions", "roles", "role_has_permissions"])) continue;

    preg_match_all("/\`(.*?)\`/", $tableContent, $colMatches);
    $columns = array_unique($colMatches[1]);

    // Read all migration files
    $migrationContent = "";
    foreach($migrationFiles as $file) {
        if(str_contains($file, ".php")) {
            $migrationContent .= file_get_contents($migrationsPath . "/" . $file) . "\n";
        }
    }

    $missingColumns = [];
    foreach ($columns as $col) {
        if (in_array($col, ["id", "created_at", "updated_at", "deleted_at", "remember_token"])) continue;

        // Ensure this column actually exists in a context related to creating or adding columns
        if (!preg_match("/(['\"]" . preg_quote($col, "/") . "['\"])/", $migrationContent)) {
             $missingColumns[] = $col;
        }
    }

    if (!empty($missingColumns)) {
        $issues[$tableName] = $missingColumns;
    }
}

print_r($issues);
