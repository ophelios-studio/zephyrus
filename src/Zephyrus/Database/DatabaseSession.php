<?php namespace Zephyrus\Database;

use RuntimeException;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\FatalDatabaseException;

class DatabaseSession
{
    private static ?DatabaseSession $instance = null;
    private Database $database;

    /**
     * @throws FatalDatabaseException
     */
    final public static function initiate(array $configurations): void
    {
        static::$instance = new static(new Database($configurations));
    }

    final public static function kill(): void
    {
        static::$instance = null;
    }

    final public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            throw new RuntimeException("DatabaseSession instance must first be initialized with [DatabaseSession::initiate(Database \$databaseInstance)].");
        }
        return static::$instance;
    }

    public function getDatabase(): Database
    {
        return $this->database;
    }

    protected function __construct(Database $database)
    {
        $this->database = $database;
        $this->activateSearchPath();
    }

    /**
     * Set the proper locale to use within the Postgres session for date/time formatting. E.g.:
     *
     * SET lc_time = 'fr_FR.UTF-8';
     * SELECT TO_CHAR(CURRENT_DATE, 'Day, DD Month YYYY');
     *
     * If the formatting is entirely done by the back end, then no need to use this.
     *
     * @param string $locale
     * @return void
     */
    public function activateLocale(string $locale): void
    {
        $this->database->query("SET lc_time = '$locale.UTF-8'");
    }

    private function activateSearchPath(): void
    {
        $searchPaths = $this->database->getConfiguration()->getSearchPaths();
        if (empty($searchPaths)) {
            return;
        }
        $paths = implode(', ', $searchPaths);
        $this->database->query("SET search_path TO $paths;");
    }
}
