<?php

namespace N98\Magento\Command\Database;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Util\Console\Helper\Table\Renderer\RendererFactory;
use N98\Util\Filesystem;

class VariablesCommand extends AbstractShowCommand
{
    /**
     * variable name => recommended size (but this value must be calculated depending on the server size
     * @see https://launchpadlibrarian.net/78745738/tuning-primer.sh convert that to PHP ... ?
     *      http://www.slideshare.net/shinguz/mysql-configuration-the-most-important-variables GERMAN
     * @var array
     */
    protected $_importantVars = array(
        'have_query_cache'                => '',
        'innodb_additional_mem_pool_size' => '',
        'innodb_buffer_pool_size'         => '',
        'innodb_log_buffer_size'          => '',
        'innodb_log_file_size'            => '',
        'innodb_thread_concurrency'       => '',
        'join_buffer_size'                => '',
        'key_buffer_size'                 => '',
        'max_allowed_packet'              => '',
        'max_connections'                 => '',
        'max_heap_table_size'             => '',
        'open_files_limit'                => '',
        'query_cache_size'                => '',
        'query_cache_type'                => '',
        'read_rnd_buffer_size'            => '',
        'read_buffer_size'                => '',
        'sort_buffer_size'                => '',
        'table_definition_cache'          => '',
        'table_open_cache'                => '',
        'thread_cache_size'               => '',
        'tmp_table_size'                  => array(
            'desc' => '', // @todo add description everywhere
            'opt'  => ''  // @todo calculate somehow the optimal values depending on the MySQL server environment
        ),
    );

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('db:variables')
            ->setDescription('Shows important variables or custom selected');

        $help = <<<HELP
This command is useful to print all important variables about the current database.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function allowRounding($name)
    {
        $toHuman = array(
            'max_length_for_sort_data' => 1,
            'max_allowed_packet'       => 1,
            'max_seeks_for_key'        => 1,
            'max_write_lock_count'     => 1,
            'slave_max_allowed_packet' => 1,
        );
        $isSize = false !== strpos($name, '_size');

        return $isSize || isset($toHuman[$name]);
    }
}
