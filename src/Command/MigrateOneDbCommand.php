<?php
namespace TurboLabIt\DoctrineRuntimeManager\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class MigrateOneDbCommand extends Command
{
    const CLI_ARG_DB_NAME       = 'dbname';
    const CLI_OPT_NAMING_MODE   = 'name-mode';
    const CLI_OPT_DRY_RUN       = 'dry-run';

    protected static $defaultName        = 'MigrateOneDb';
    protected static $defaultDescription = 'Run migrations against the provided DB';
    protected Connection $connection;

    use LockableTrait;


    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct(static::$defaultName);
        $this->connection = $entityManager->getConnection();
    }


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument(static::CLI_ARG_DB_NAME, InputArgument::OPTIONAL, 'The name or the suffix of of the database to migrate')
            ->addOption(static::CLI_OPT_NAMING_MODE, null, InputOption::VALUE_OPTIONAL, 'name: the next argument is the full name of the database to migrate | suffix: the next argument is the suffix to append to the defaul db name (defaultDb_suffix)', "name")
            ->addOption(static::CLI_OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Test only (no changes)')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input    = $input;
        $this->output   = $output;
        $this->io       = new SymfonyStyle($input, $output);

        $argDbName      = $this->input->getArgument(static::CLI_ARG_DB_NAME);
        $optNameMode    = $this->input->getOption(static::CLI_OPT_NAMING_MODE);

        $lockName       = static::$defaultName . "_" . implode("_", [$argDbName, $optNameMode]);
        if (!$this->lock($lockName)) {
            $this->io->error('The command ##' . $lockName . '## is already running in another process.');
            return 0;
        }

        $this->io->block("Migrating on " . $argDbName, null, 'fg=black;bg=cyan', ' ', true);

        if( !in_array($optNameMode, ["name", "suffix"]) ) {
            $this->io->error('Bad option! Valid values for ' . static::CLI_OPT_NAMING_MODE . ' are `name` or `suffix`');
            return 0;
        }

        if( !empty($argDbName) && $optNameMode == 'suffix') {

            $this->io->section("Switching DB by SUFFIX...");
            $this->connection->selectDatabaseByAppend($argDbName);

        } elseif( !empty($argDbName) && $optNameMode == 'name') {

            $this->io->section("Switching DB by NAME...");
            $this->connection->selectDatabase($argDbName);

        } else {

            $this->io->section("Null DB argument provided - Working on the default DB...");
        }

        // https://symfony.com/doc/current/console/calling_commands.html
        if( $this->input->getOption(static::CLI_OPT_DRY_RUN) ) {

            $this->io->section("Dry run. Attempting to show the status...");
            $cmdMigrate     = $this->getApplication()->find('doctrine:migrations:status');
            $cmdArguments   = new ArrayInput([]);

        } else {

            $this->io->section("Executing migrations...");
            $cmdMigrate     = $this->getApplication()->find('doctrine:migrations:migrate');
            $cmdArguments   = new ArrayInput(['--no-interaction' => true]);
        }

        $cmdArguments->setInteractive(false);
        $cmdMigrate->run($cmdArguments, $this->output);

        $this->io->success('Done');
        return 0;
    }
}
