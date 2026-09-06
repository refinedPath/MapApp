<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddEmailVerifiedAtToUsers extends AbstractMigration
{
    public function change(): void
    {
        $this->table('users')
            ->addColumn('email_verified_at', 'timestamp', [
                'timezone' => true,
                'null' => true,
                'default' => null,
            ])
            ->update();
    }
}
