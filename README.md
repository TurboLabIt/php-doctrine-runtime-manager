# php-doctrine-runtime-manager

Switch and manage the Doctrine connection at runtime.


## 📦 1. Install it with composer

````bash
composer config repositories.TurboLabIt/php-doctrine-runtime-manager git https://github.com/TurboLabIt/php-doctrine-runtime-manager.git
composer require turbolabit/php-doctrine-runtime-manager:dev-main

````

## ⚙️ 2. Mandatory Symfony configuration

If the application uses a single-DB connection:

````yaml
# config/packages/doctrine.yaml
doctrine:
  dbal:
  # ....
  wrapper_class: TurboLabIt\DoctrineRuntimeManager\DoctrineRuntimeManager
  # ....
````

If the application uses multiple connections, you must configure the wrapper on each connection:

````yaml
# config/packages/doctrine.yaml
doctrine:
  dbal:
  default_connection: default
  connections:
    default:
      # ....
      wrapper_class: TurboLabIt\DoctrineRuntimeManager\DoctrineRuntimeManager
      # ...
    wordpress:
      # ....
      # wrapper_class: TurboLabIt\DoctrineRuntimeManager\DoctrineRuntimeManager
      # ...
````


## 🔁 3. Symfony usage

````php
<?php
namespace App\Service;

class Language
{
    protected EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function setCurrent($currentLn)
    {
        $this->em->getConnection()->selectDatabaseByAppend($currentLn);
    }
}

````


## 🧪 Test it

````bash
git clone git@github.com:TurboLabIt/php-doctrine-runtime-manager.git
cd php-doctrine-runtime-manager
clear && bash script/test_runner.sh

````


## 🔗 Sources

- [Dynamic database connection based on request – Symfony and Doctrine](https://karoldabrowski.com/blog/dynamic-database-connection-based-on-request-symfony-and-doctrine/)
- [Running multiple migrations in the same command](https://stackoverflow.com/questions/68246878/running-multiple-migrations-in-the-same-command)
