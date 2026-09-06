<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateEmailVerificationTokensTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('email_verification_tokens', ['id' => false, 'primary_key' => ['token_hash']])
            ->addColumn('token_hash', 'string', ['limit' => 64, 'null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('expires_at', 'timestamp', ['timezone' => true, 'null' => false])
            ->addColumn('created_at', 'timestamp', ['timezone' => true, 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addForeignKey('user_id', 'users', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION',
            ])
            ->addIndex(['user_id'])
            ->create();
    }
}
