<?php

namespace App\Console\Commands;

use App\Jobs\ProcessUserJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ImportDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $file = $this->argument('file');
        $json = Storage::get($file);
        $data = json_decode($json, true);

        if (!is_array($data)) {
            $this->error('Invalid data. Please ensure the file contains a JSON array.');
            return;
        }

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $item) {
            $hash = md5(json_encode($item));
            ProcessUserJob::dispatch($item, $hash);
            $bar->advance();
        }

        $bar->finish();

        $this->info("\nDispatched jobs for {$file}.");

        return 0;
    }
}
