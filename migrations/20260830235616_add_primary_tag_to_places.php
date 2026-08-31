<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class AddPrimaryTagToPlaces extends AbstractMigration
{
    public function change(): void
    {
        $this->table('places')
            ->addColumn('primary_tag_id', 'uuid', ['null' => true])
            ->addForeignKey('primary_tag_id', 'tags', 'id', [
                'delete' => 'SET_NULL',
                'update' => 'NO_ACTION',
            ])
            ->update();
    }
}
