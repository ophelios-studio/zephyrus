<?php

namespace Zephyrus\Tests\Database;

use Zephyrus\Database\Broker;

class BrokerTest extends DatabaseTestCase
{
    public function testFindAll()
    {
        $instance = $this->buildBroker();
        $results = $instance->findAll();
        self::assertCount(6, $results);
        self::assertEquals('Batman', $results[0]->name);
        self::assertEquals('Green Lantern', $results[5]->name);
    }

    public function testFindById()
    {
        $instance = $this->buildBroker();
        $result = $instance->findById(3);
        self::assertEquals('Aquaman', $result->name);
        $result = $instance->findById(99);
        self::assertNull($result);
    }

    public function testInsert()
    {
        $instance = $this->buildBroker();
        $newHero = (object)[
            'name'  => 'The Destroyer',
            'alter' => 'Bob Lewis',
            'power' => 40
        ];
        $newId = $instance->save($newHero);
        self::assertEquals(7, $newId);
        self::assertEquals(7, $instance->getDatabase()->getLastInsertedId('heroes_id_seq'));
        $result = $instance->findById(7);
        self::assertNotNull($result);
        self::assertEquals("Bob Lewis", $result->alter);
    }

    /**
     * @depends testInsert
     */
    public function testUpdate()
    {
        $instance = $this->buildBroker();
        $updateHero = (object)[
            'id'    => 7,
            'name'  => 'The Destroyer2',
            'alter' => 'Bob Lewis2',
            'power' => 42
        ];
        $updatedId = $instance->save($updateHero);
        self::assertEquals(7, $updatedId);
        $result = $instance->findById(7);
        self::assertNotNull($result);
        self::assertEquals("The Destroyer2", $result->name);
        self::assertEquals("Bob Lewis2", $result->alter);
        self::assertEquals(42, $result->power);
    }

    public function testDelete()
    {
        $instance = $this->buildBroker();
        $success = $instance->delete(7);
        self::assertTrue($success);
        $result = $instance->findById(7);
        self::assertNull($result);
    }

    /**
     * Builds an anonymous broker instance for the heroes table.
     *
     * @return Broker
     */
    private function buildBroker(): Broker
    {
        $database = $this->buildDatabase();
        // Create an anonymous class extending Broker.
        return new class('heroes') extends Broker {
            // No additional methods; we rely on the base CRUD operations via save().
        };
    }
}
