<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScedularTransaction
{
    public static function checkTransactionStatus()
    {
        // Ambil waktu saat ini
        $now = Carbon::now();

        // Ambil data transaksi dengan status 1 (belum diproses)
        $transactions = DB::table('transactions')
            ->where('status', 0)
            ->get();

        echo "running scedular";
        Log::info(
            'Running Scedular',
            [
                'date' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        );

        if ($transactions) {
            // Loop melalui setiap transaksi
            foreach ($transactions as $transaction) {

                // Ubah created_at ke objek Carbon
                $created_at = Carbon::createFromFormat('Y-m-d H:i:s', $transaction->created_at);

                // Hitung selisih waktu dalam jam
                $diff_in_hours = $created_at->diffInHours($now);

                // Jika selisih waktu lebih dari 24 jam
                if ($diff_in_hours > 24) {
                    // Ubah status menjadi 0 (dibatalkan)
                    DB::table('transactions')
                        ->where('invoice', $transaction->invoice)
                        ->update(['status' => 2]);
                }
            }
        }

        echo "running scedular done";
        Log::info(
            'Running Scedular Done',
            [
                'date' => Carbon::now()->format('Y-m-d H:i:s')
            ]
        );
    }
}
