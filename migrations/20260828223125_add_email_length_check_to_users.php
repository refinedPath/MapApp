<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEmailLengthCheckToUsers extends AbstractMigration
{
    public function up(): void
    {
        $this->execute('ALTER TABLE users ADD CONSTRAINT users_email_len CHECK (char_length(email) <= 254)');
    }

    public function down(): void
    {
        $this->execute('ALTER TABLE IF EXISTS users DROP CONSTRAINT IF EXISTS users_email_len');
    }
}
