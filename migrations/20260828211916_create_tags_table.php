<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;
use Phinx\Util\Literal;

final class CreateTagsTable extends AbstractMigration
{
    public function up(): void
    {
        $this->table('tags', ['id' => false, 'primary_key' => ['id']])
            ->addColumn('id', 'uuid', ['null' => false])
            ->addColumn('user_id', 'uuid', ['null' => false])
            ->addColumn('name', Literal::from('citext'), ['null' => false])
            ->addColumn('color', 'string', ['limit' => 7, 'null' => false])
            ->addColumn('emoji', 'text', ['null' => true])
            ->addColumn('created_at', 'timestamp', ['timezone' => true, 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addColumn('updated_at', 'timestamp', ['timezone' => true, 'default' => 'CURRENT_TIMESTAMP', 'null' => false])
            ->addIndex(['user_id', 'name'], ['unique' => true])
            ->addForeignKey('user_id', 'users', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();

        $this->execute('ALTER TABLE IF EXISTS tags ADD CONSTRAINT tags_name_len CHECK (char_length(name) <= 255)');

        $this->execute("ALTER TABLE IF EXISTS tags ADD CONSTRAINT tags_color_hex CHECK (color ~ '^#[0-9A-Fa-f]{6}$')");

        $this->execute('ALTER TABLE IF EXISTS tags ADD CONSTRAINT tags_emoji_len CHECK (emoji IS NULL OR char_length(emoji) BETWEEN 1 AND 16)');
    }

    public function down(): void
    {
        $this->table('tags')->drop()->save();
    }
}
