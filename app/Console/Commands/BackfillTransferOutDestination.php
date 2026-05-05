<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillTransferOutDestination extends Command
{
    protected $signature = 'stock:backfill-transfer-destinations';
    protected $description = 'Backfill from_branch_id on transfer_out records using the paired transfer_in record sharing the same transfer_reference.';

    public function handle()
    {
        $this->info('Backfilling transfer_out destination branches...');

        // Find all transfer_out records with no from_branch_id and a non-null transfer_reference
        $outgoing = DB::table('stock_movements')
            ->where('type', 'transfer_out')
            ->whereNull('from_branch_id')
            ->whereNotNull('transfer_reference')
            ->select('id', 'transfer_reference')
            ->get();

        if ($outgoing->isEmpty()) {
            $this->info('Nothing to backfill — all transfer_out records already have a destination.');
            return 0;
        }

        $this->info("Found {$outgoing->count()} transfer_out record(s) to patch.");

        $updated = 0;
        $skipped = 0;

        foreach ($outgoing as $row) {
            // Find the paired transfer_in record with the same reference
            $paired = DB::table('stock_movements')
                ->where('type', 'transfer_in')
                ->where('transfer_reference', $row->transfer_reference)
                ->value('branch_id'); // branch_id on transfer_in IS the destination

            if ($paired) {
                DB::table('stock_movements')
                    ->where('id', $row->id)
                    ->update(['from_branch_id' => $paired]);
                $updated++;
            } else {
                $this->warn("  No paired transfer_in found for REF: {$row->transfer_reference} (ID: {$row->id}) — skipping.");
                $skipped++;
            }
        }

        $this->info("Done. Updated: {$updated}, Skipped: {$skipped}.");
        return 0;
    }
}
