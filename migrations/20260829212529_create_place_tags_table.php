<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreatePlaceTagsTable extends AbstractMigration
{
    public function change(): void
    {
        $this->table('place_tags', ['id' => false, 'primary_key' => ['place_id', 'tag_id']])
            ->addColumn('place_id', 'uuid', ['null' => false])
            ->addColumn('tag_id', 'uuid', ['null' => false])
            ->addForeignKey('place_id', 'places', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->addForeignKey('tag_id', 'tags', 'id', ['delete' => 'CASCADE', 'update' => 'NO_ACTION'])
            ->create();
    }
}
