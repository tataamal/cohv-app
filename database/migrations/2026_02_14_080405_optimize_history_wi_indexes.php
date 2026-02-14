<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $table = 'history_wi';

    // Nama index/constraint yang konsisten (biar gampang drop di down())
    private string $uqDocCode = 'uq_history_wi_document_code';
    private string $idxPrefixSeq = 'idx_history_wi_prefix_seq';
    private string $idxSeq = 'idx_history_wi_sequence_number';

    private function indexExists(string $table, string $indexName): bool
    {
        // SHOW INDEX hanya mengembalikan row kalau index ada
        $rows = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return !empty($rows);
    }

    public function up(): void
    {
        // 1) Tambah UNIQUE wi_document_code (wajib)
        if (!$this->indexExists($this->table, $this->uqDocCode)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->unique('wi_document_code', $this->uqDocCode);
            });
        }

        // 2) (Recommended) Tambah doc_prefix + index (doc_prefix, sequence_number)
        //    Ini bikin query "ambil last sequence per prefix" jauh lebih cepat dibanding LIKE 'WIH%'
        if (!Schema::hasColumn($this->table, 'doc_prefix')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->char('doc_prefix', 3)->nullable()->after('wi_document_code');
            });

            // Isi doc_prefix untuk data existing (WIH/WIW dari 3 char awal)
            DB::statement("
                UPDATE `{$this->table}`
                SET `doc_prefix` = LEFT(`wi_document_code`, 3)
                WHERE `doc_prefix` IS NULL OR `doc_prefix` = ''
            ");
        }

        if (Schema::hasColumn($this->table, 'doc_prefix') && !$this->indexExists($this->table, $this->idxPrefixSeq)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->index(['doc_prefix', 'sequence_number'], $this->idxPrefixSeq);
            });
        }

        // 3) Index sequence_number (opsional tapi bagus)
        if (Schema::hasColumn($this->table, 'sequence_number') && !$this->indexExists($this->table, $this->idxSeq)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->index('sequence_number', $this->idxSeq);
            });
        }
    }

    public function down(): void
    {
        // Drop index sequence_number
        if ($this->indexExists($this->table, $this->idxSeq)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropIndex($this->idxSeq);
            });
        }

        // Drop index (doc_prefix, sequence_number)
        if ($this->indexExists($this->table, $this->idxPrefixSeq)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropIndex($this->idxPrefixSeq);
            });
        }

        // Drop column doc_prefix (kalau ada)
        if (Schema::hasColumn($this->table, 'doc_prefix')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropColumn('doc_prefix');
            });
        }

        // Drop UNIQUE wi_document_code
        if ($this->indexExists($this->table, $this->uqDocCode)) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->dropUnique($this->uqDocCode);
            });
        }
    }
};
