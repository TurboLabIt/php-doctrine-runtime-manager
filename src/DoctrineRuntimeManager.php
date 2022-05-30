<?php
/**
 * @see https://github.com/TurboLabIt/php-doctrine-runtime-manager
 */
namespace TurboLabIt\DoctrineRuntimeManager;

use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\ORM\EntityManagerInterface;


class DoctrineRuntimeManager extends Connection
{
    protected string $dbBaseName;
    protected string $currentDbName;
    protected ?EntityManagerInterface $em;


    public function __construct(array $params, Driver $driver, ?Configuration $config = null, ?EventManager $eventManager = null)
    {
        parent::__construct($params, $driver, $config, $eventManager);
    }


    public function setEntityManager(EntityManagerInterface $em) : self
    {
        $this->em = $em;
        return $this;
    }


    public function selectDatabase(string $dbName): void
    {
        if( empty($dbName) || $dbName == $this->currentDbName ) {
            return;
        }

        if ($this->isConnected()) {
            $this->close();
        }

        $params = $this->getParams();

        // backing up the default/base DB name, so we can use it later
        $this->setDbBaseName($params['dbname']);

        //
        $this->clearCache();

        //
        $params['dbname'] = $dbName;
        $this->currentDbName = $dbName;
        parent::__construct($params, $this->_driver, $this->_config, $this->_eventManager);
    }


    public function selectDatabaseByAppend(string $suffix): void
    {
        if( empty($suffix) ) {
            return;
        }

        $params = $this->getParams();

        // backing up the default/base DB name, so we can use it later
        $this->setDbBaseName($params['dbname']);

        //
        $newName = $this->dbBaseName . "_" . $suffix;
        $this->selectDatabase($newName);
    }


    public function selectDefaultDatabase()
    {
        if( empty($this->dbBaseName) ) {
            // it was never changed in the first place
            return null;
        }

        return $this->selectDatabase($this->dbBaseName);
    }


    public function clearCache() : self
    {
        if( !empty($this->em) ) {
            $this->em->clear();
        }

        return $this;
    }


    protected function setDbBaseName(string $dbName)
    {
        if( !empty($this->dbBaseName) ) {
            return null;
        }

        $this->dbBaseName       = $dbName;
        $this->currentDbName    = $dbName;
    }
}
