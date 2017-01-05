<?php

namespace Enqueue\JobQueue;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema as BaseSchema;

class Schema extends BaseSchema
{
    /**
     * @var string
     */
    private $uniqueTableName;

    /**
     * @param Connection $connection
     * @param string     $uniqueTableName
     */
    public function __construct(Connection $connection, $uniqueTableName)
    {
        $this->uniqueTableName = $uniqueTableName;

        $schemaConfig = $connection->getSchemaManager()->createSchemaConfig();

        parent::__construct([], [], $schemaConfig);

        $this->addUniqueJobTable();
    }

    /**
     * Merges ACL schema with the given schema.
     *
     * @param BaseSchema $schema
     */
    public function addToSchema(BaseSchema $schema)
    {
        foreach ($this->getTables() as $table) {
            $schema->_addTable($table);
        }

        foreach ($this->getSequences() as $sequence) {
            $schema->_addSequence($sequence);
        }
    }

    private function addUniqueJobTable()
    {
        $table = $this->createTable($this->uniqueTableName);
        $table->addColumn('name', 'string', ['length' => 255]);
        $table->addUniqueIndex(['name']);
    }
}
