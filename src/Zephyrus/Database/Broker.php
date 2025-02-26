<?php

namespace Zephyrus\Database;

use stdClass;
use Zephyrus\Core\Entity\Entity;
use Zephyrus\Database\Core\Database;

/**
 * The Broker class is an extension of the DatabaseBroker and is NOT necessary to use the database. See {@see \Zephyrus\Database\DatabaseBroker} for more informations.
 * It provides a more convenient way to interact with the database by providing C.R.U.D operations functions. And less overhead for insert and update operations.
 */
abstract class Broker extends DatabaseBroker
{
    protected string $table;

    /**
     * @param string $table The name of the table associated with this broker.
     * @param null|Database $database An optional database instance.
     */
    public function __construct(Database $database = null, string $table)
    {
        parent::__construct($database);
        $this->table = $table;
    }

    /**
     * Retrives all rows from the table.
     * 
     * @return array An array of stdClass objects.
     */
    public function findAll(): array
    {
        return $this->select("SELECT * FROM {$this->table}");
    }

    /**
     * Finds a row by its primary key ID.
     * 
     * @param int $id The ID of the row to find.
     * @return stdClass|null Thr raw row as stdClass or null if not found.
     */
    public function findById(int $id): ?stdClass
    {
        return $this->selectSingle("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
    }

    /**
     * Deletes a row by its primary key ID.
     * 
     * @param int $id The ID of the row to delete.
     * @return bool True if at least one row was deleted, false otherwise.
     */
    public function delete(int $id): bool
    {
        $this->query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
        return $this->getLastAffectedCount() > 0;
    }

    // The section below still need some think-through. Should we remove the save() function and replace it with insert() and update()?
    // Or should we keep the save() function and make it call insert() or update() depending on the ID?

    /**
     * Saves the entity to the database by determining whether to insert or update.
     * 
     * @param object $entity The entity to save.
     * @return int The row's ID (existing or newly created).
     */
    public function save(Entity $entity): int // Should we verify the state of the entity in the database ? ... maybe not (overhead on the DB). The state of the entity can be known from the id being unset or set.
    {
        $data = $entity->jsonSerialize();
        if (isset($data['id']) && $data['id']) {
            $this->update($entity);
            return (int)$data['id'];
        } else {
            return $this->insert($entity);
        }
    }

    /**
     * Updates an existing row in the database.
     *
     * The entity must implement jsonSerialize() and have an 'id' property.
     *
     * @param object $entity The entity to update.
     * @return int The number of affected rows.
     * @throws \InvalidArgumentException If the entity does not have an id.
     */
    private function update(Entity $entity): int
    {
        $data = $entity->jsonSerialize();
        if (!isset($data['id']) || !$data['id']) {
            throw new \InvalidArgumentException("Cannot update an entity without an id.");
        }
        $id = $data['id'];
        unset($data['id']);
        $fields = array_keys($data);
        $updateFields = implode(", ", array_map(fn($field) => "$field = ?", $fields));
        $query = "UPDATE {$this->table} SET $updateFields WHERE id = ?";
        $parameters = array_values($data);
        $parameters[] = $id;
        $this->query($query, $parameters);
        return $this->getLastAffectedCount();
    }

    /**
     * Inserts a new row into the database.
     *
     * The entity must implement jsonSerialize() and not have an 'id' property, as it is auto-generated.
     *
     * @param object $entity The entity to insert.
     * @return int The newly inserted row's ID.
     */
    private function insert(Entity $entity): int
    {
        $data = $entity->jsonSerialize();
        if (isset($data['id'])) {
            unset($data['id']);
        }
        $fields = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), '?'));
        $query = "INSERT INTO {$this->table} ($fields) VALUES ($placeholders) RETURNING id";
        $result = $this->selectSingle($query, array_values($data));
        return (int)$result->id;
    }
}
