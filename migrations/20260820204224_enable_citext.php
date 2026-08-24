<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class EnableCitext extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('CREATE EXTENSION IF NOT EXISTS citext');
    }

    public function down(): void
    {
        $this->execute('DROP EXTENSION IF EXISTS citext');
    }
}
