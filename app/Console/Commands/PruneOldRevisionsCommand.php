<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CmsPageRevision;
use App\Models\CmsPreviewToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class PruneOldRevisionsCommand extends Command
{
    protected $signature = 'cms:prune-revisions {--days=90 : Revisions older than this many days will be deleted}';

    protected $description = 'Delete old revisions and expired preview tokens';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        // Delete expired preview tokens
        $tokenCount = CmsPreviewToken::withoutGlobalScopes()
            ->where('expires_at', '<', now())
            ->delete();

        $this->info("Deleted {$tokenCount} expired preview tokens.");

        // Delete old revisions not referenced as live or staged
        $pruned = CmsPageRevision::withoutGlobalScopes()
            ->where('created_at', '<', $cutoff)
            ->whereNotIn('id', function ($query): void {
                $query->select(DB::raw('COALESCE(live_revision_id, staged_revision_id)'))
                    ->from('cms_pages')
                    ->whereNotNull(DB::raw('COALESCE(live_revision_id, staged_revision_id)'))
                    ->union(
                        DB::table('cms_posts')
                            ->select(DB::raw('COALESCE(live_revision_id, staged_revision_id)'))
                            ->whereNotNull(DB::raw('COALESCE(live_revision_id, staged_revision_id)'))
                    );
            })
            ->delete();

        $this->info("Deleted {$pruned} revisions older than {$days} days.");

        return self::SUCCESS;
    }
}
