<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EnablePostgis extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(): void
    {
        $this->execute('DROP EXTENSION IF EXISTS postgis');
    }
}
