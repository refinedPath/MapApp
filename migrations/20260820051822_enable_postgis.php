<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EnablePostgis extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS postgis');
    }

    public function down(): void
    {
        $this->execute('DROP EXTENSION IF EXISTS postgis');
    }
}
