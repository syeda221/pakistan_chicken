<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CompareMigrations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'compare:migrations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Compare migrations with SQL dump';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dumpPath = 'c:\Users\Admin\Downloads\bin_sultan.sql';
        if (!file_exists($dumpPath)) {
            $this->error("Dump file not found");
            return;
        }

        $dumpContent = file_get_contents($dumpPath);
        preg_match_all("/CREATE TABLE `(.*?)`/is", $dumpContent, $matches);
        $dumpTables = $matches[1];

        $migrationsPath = database_path('migrations');
        $migrationFiles = scandir($migrationsPath);
        
        $migrationContent = '';
        foreach($migrationFiles as $file) {
            if(str_contains($file, '.php')) {
                $migrationContent .= file_get_contents($migrationsPath . '/' . $file) . "\n";
            }
        }
        
        $missingTables = [];
        foreach ($dumpTables as $table) {
            if (!str_contains($migrationContent, "Schema::create('$table'") && !str_contains($migrationContent, "Schema::create(\"$table\"")) {
                $missingTables[] = $table;
            }
        }

        if (empty($missingTables)) {
            $this->info("All tables from dump exist in migrations.");
        } else {
            $this->warn("The following tables are missing from migrations:");
            foreach ($missingTables as $table) {
                $this->line("- " . $table);
            }
        }
    }
}
