<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Command;

class GenerateMemberQrCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'members:generate-qr-codes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate QR codes for all members without one';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $members = Member::whereNull('qr_code')->get();

        $this->info("Generating QR codes for {$members->count()} members...");

        $bar = $this->output->createProgressBar($members->count());

        foreach ($members as $member) {
            $member->generateQrCode();
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('QR codes generated successfully!');

        return Command::SUCCESS;
    }
}
