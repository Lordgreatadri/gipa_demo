<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BackfillInvestorProfiles extends Command
{
    protected $signature = 'investors:backfill-profiles {--chunk=200 : Number of users processed per batch}';

    protected $description = 'Create missing investor profiles for existing investor accounts';

    public function handle(): int
    {
        $chunkSize = max(1, min((int) $this->option('chunk'), 1000));
        $created = 0;

        User::query()
            ->select('id', 'name')
            ->where('account_type', User::ACCOUNT_INVESTOR)
            ->whereDoesntHave('investorProfile')
            ->chunkById($chunkSize, function ($users) use (&$created): void {
                foreach ($users as $user) {
                    $profile = $user->investorProfile()->firstOrCreate([], [
                        'display_name' => $user->name,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ]);
                    $created += $profile->wasRecentlyCreated ? 1 : 0;
                }
            });

        $this->info("Created {$created} investor profile(s).");

        return self::SUCCESS;
    }
}
