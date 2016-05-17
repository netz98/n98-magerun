<?php

namespace N98\Magento\Command\Database;

class StatusCommand extends AbstractShowCommand
{
    protected $showMethod = 'getGlobalStatus';

    /**
     * Add more important status variables
     *
     * @var array
     */
    protected $_importantVars = array(
        'Threads_connected'              => array(
            'desc' => 'Total number of clients that have currently open connections to the server.',
        ),
        'Created_tmp_disk_tables'        => array(
            'desc' => 'Number of temporary tables that have been created on disk instead of in-memory. Lower is
            better.',
        ),
        'Handler_read_first'             => array(
            'desc' => 'Number of times a table handler made a request to read the first row of a table index.',
        ),
        'Handler_read_rnd_next'          => array(
            'desc' => 'Number of requests to read the next row in the data file. This value is high if you
                are doing a lot of table scans. Generally this suggests that your tables are not properly indexed or
                that your queries are not written to take advantage of the indexes you have.',
        ),
        'Innodb_buffer_pool_wait_free'   => array(
            'desc' => 'Number of times MySQL has to wait for memory pages to be flushed.',
        ),
        'Innodb_buffer_pool_pages_dirty' => array(
            'desc' => 'Indicates the number of InnoDB buffer pool data pages that have been changed in memory,
                 but the changes are not yet written (flushed) to the InnoDB data files',
        ),
        'Key_reads'                      => array(
            'desc' => 'Number of filesystem accesses MySQL performed to fetch database indexes.',
        ),
        'Max_used_connections'           => array(
            'desc' => 'Max number of connections MySQL has had open at the same time since the server was
                 last restarted.',
        ),
        'Open_tables'                    => array(
            'desc' => 'Number of tables that are currently open.',
        ),
        'Select_full_join'               => array(
            'desc' => 'Number of full joins MySQL has performed to satisfy client queries.',
        ),
        'Slow_queries'                   => array(
            'desc' => 'Number of queries that have taken longer than usual to execute.',
        ),
        'Uptime'                         => array(
            'desc' => 'Time since the server was last restarted.',
        ),
        'Aborted_connects'               => array(
            'desc' => 'Total number of failed attempts to connect to MySQL.',
        ),
    );
    /**
     * @var array
     */
    protected $_specialFormat = array(
        'Uptime' => 'timeElapsedString',
    );

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('db:status')
            ->setDescription('Shows important server status information or custom selected status values');

        $help = <<<HELP
This command is useful to print important server status information about the current database.
HELP;
        $this->setHelp($help);
    }

    /**
     * @param array $outputVars
     * @param bool $hasDescription
     *
     * @return array
     */
    protected function generateRows(array $outputVars, $hasDescription)
    {
        $rows = parent::generateRows($outputVars, $hasDescription);

        if (false === $hasDescription) {
            return $rows;
        }

        if (isset($this->_allVariables['Handler_read_rnd_next'])) {
            $tableScanRate = (
                (
                    $this->_allVariables['Handler_read_rnd_next'] +
                    $this->_allVariables['Handler_read_rnd']
                )
                /
                (
                    $this->_allVariables['Handler_read_rnd_next'] +
                    $this->_allVariables['Handler_read_rnd'] +
                    $this->_allVariables['Handler_read_first'] +
                    $this->_allVariables['Handler_read_next'] +
                    $this->_allVariables['Handler_read_key'] +
                    $this->_allVariables['Handler_read_prev']
                )
            );
            $rows[] = array(
                'Full table scans',
                sprintf('%.2f%%', $tableScanRate * 100),
                $this->formatDesc(
                    'HINT: "Handler_read_rnd_next" is reset to zero when reached the value of 2^32 (4G).'
                ),
            );
        }
        if (isset($this->_allVariables['Innodb_buffer_pool_read_requests'])) {
            $bufferHitRate = $this->_allVariables['Innodb_buffer_pool_read_requests'] /
                ($this->_allVariables['Innodb_buffer_pool_read_requests'] +
                    $this->_allVariables['Innodb_buffer_pool_reads']);

            $rows[] = array(
                'InnoDB Buffer Pool hit',
                sprintf('%.2f', $bufferHitRate * 100) . '%',
                $this->formatDesc(
                    'An InnoDB Buffer Pool hit ratio below 99.9% is a weak indicator that ' .
                    'your InnoDB Buffer Pool could be increased.'
                ),
            );
        }

        return $rows;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    protected function allowRounding($name)
    {
        $isSize = false !== strpos($name, '_size');

        return $isSize;
    }

    /**
     * borrowed from <https://stackoverflow.com/questions/1416697/>
     *
     * echo time_elapsed_string('2013-05-01 00:22:35');
     * echo time_elapsed_string('@1367367755'); # timestamp input
     * echo time_elapsed_string('2013-05-01 00:22:35', true);
     *
     * @param      $datetime
     * @param bool $full
     *
     * @return string
     */
    protected function timeElapsedString($datetime, $full = false)
    {
        if (is_numeric($datetime)) {
            $datetime = time() - $datetime;
            $datetime = '@' . $datetime;
        }

        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) {
            $string = array_slice($string, 0, 1);
        }

        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}
