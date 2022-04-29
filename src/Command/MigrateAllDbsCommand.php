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
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


class MigrateAllDbsCommand extends Command
{
    const CLI_ARG_DB_NAMES      = 'dbnames';
    const CLI_OPT_NAMING_MODE   = 'name-mode';
    const CLI_OPT_DRY_RUN       = 'dry-run';

    const CLI_VAL_NAMING_MODE_NAME      = 'name';
    const CLI_VAL_NAMING_MODE_SUFFIX    = 'suffix';

    protected static $defaultName        = 'MigrateAllDbs';
    protected static $defaultDescription = 'Run migrations against all the provided DBs';

    use LockableTrait;


    protected function configure(): void
    {
        $this
            ->setDescription(self::$defaultDescription)
            ->addArgument(static::CLI_ARG_DB_NAMES, InputArgument::IS_ARRAY, 'The names or the suffixes of the databases to migrate')
            ->addOption(static::CLI_OPT_NAMING_MODE, null, InputOption::VALUE_OPTIONAL, 'name: the arguments are the full names of the databases to migrate | suffix: the arguments are the suffixes to append to the default db name (defaultDb_suffix)', "name")
            ->addOption(static::CLI_OPT_DRY_RUN, null, InputOption::VALUE_NONE, 'Test only (no changes)')
        ;
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input    = $input;
        $this->output   = $output;
        $this->io       = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $this->io->error('The command ##' . static::$defaultName . '## is already running in another process.');
            return 0;
        }

        $this->io->block("Running " . static::$defaultName, null, 'fg=black;bg=cyan', ' ', true);

        $argDbNames     = $this->input->getArgument(static::CLI_ARG_DB_NAMES);
        $optNameMode    = $this->input->getOption(static::CLI_OPT_NAMING_MODE);

        if( !in_array($optNameMode, [static::CLI_VAL_NAMING_MODE_NAME, static::CLI_VAL_NAMING_MODE_SUFFIX]) ) {
            $this->io->error('Bad option! Valid values for ' . MigrateAllDbsCommand::CLI_OPT_NAMING_MODE . ' are `name` or `suffix`');
            return 0;
        }

        // the default DB should always be migrated first
        if( !in_array(null, $argDbNames) ) {
            $argDbNames = array_merge([null], $argDbNames);
        }



        foreach($argDbNames as $dbName) {

            $arrMigrateOneArgs = [
                "bin/console", "MigrateOneDb", $dbName,
                '--' . static::CLI_OPT_NAMING_MODE  . "=" . $optNameMode
            ];

            if( $this->input->getOption(static::CLI_OPT_DRY_RUN) ) {
                $arrMigrateOneArgs[] = '--' . static::CLI_OPT_DRY_RUN;
            }

            $process = new Process($arrMigrateOneArgs);
            $process->run();

            if( !$process->isSuccessful() ) {
                throw new ProcessFailedException($process);
            }

            echo $process->getOutput();
        }

        $this->io->success('Done');
        return 0;
    }
}
