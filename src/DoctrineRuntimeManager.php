<?php
/**
 * @see https://github.com/TurboLabIt/php-doctrine-runtime-manager
 */
namespace TurboLabIt\DoctrineRuntimeManager;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;


class DoctrineRuntimeManager extends Connection
{
    public function __construct(array $params, Driver $driver, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        parent::__construct($params, $driver, $config, $eventManager);
    }


    public function selectDatabase(string $dbName): void
    {
        if( empty($dbName) ) {
            return;
        }

        if ($this->isConnected()) {
            $this->close();
        }

        $params = $this->getParams();
        $params['dbname'] = $dbName;
        parent::__construct($params, $this->_driver, $this->_config, $this->_eventManager);
    }


    public function selectDatabaseByAppend(string $suffix): void
    {
        if( empty($suffix) ) {
            return;
        }

        if ($this->isConnected()) {
            $this->close();
        }

        $params = $this->getParams();
        $newName = $params['dbname'] . "_" . $suffix;
        $this->selectDatabase($newName);
    }
}
