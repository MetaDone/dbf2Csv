<?php

namespace Metadone\Convert\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\ProgressBar;

/**
 * Command to convert
 */
class ToCSV extends Command
{

    private $charsetInput;
    private $charsetOutput;

    protected function configure()
    {
        $this
                ->setName('convert')
                ->setDescription('Convert DBF to CSV')
                ->addArgument('input', InputArgument::REQUIRED, 'File to convert')
                ->addArgument('output', InputArgument::REQUIRED, 'Output path')
                ->addArgument('charsetInput', InputArgument::OPTIONAL, 'Charset of dbf database')
                ->addArgument('charsetOutput', InputArgument::OPTIONAL, 'Charset of final file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        $inputFile = $input->getArgument('input');
        $outputFile = $input->getArgument('output');
        $this->charsetInput = $input->getArgument('charsetInput');
        $charsetOutput = $input->getArgument('charsetOutput');
        $this->charsetOutput = $charsetOutput == "" ? "UTF-8" : $charsetOutput;
        if (!extension_loaded("dbase")) {
            $output->writeln('<error>dbase extension is not installed!</error>');
            return;
        }

        if (!$dbf = dbase_open($inputFile, 0)) {
            $output->writeln('<error>Can not open database</error>');
            return;
        }
        if (!$this->checkWritePermission($outputFile)) {
            $output->writeln('<error>Output file is not writable! Check permissions</error>');
            return;
        }
        $numRec = dbase_numrecords($dbf);
        $progress = new ProgressBar($output, $numRec);
        $columnInfo = dbase_get_header_info($dbf);

        $fp = fopen($outputFile, 'w');
        $this->writeHead($columnInfo, $fp);

        $output->writeln('Convert start');
        for ($i = 0; $i <= $numRec; $i++) {
            $data = $this->getRowToString($dbf, $i);
            if (!empty($data)) {
                fputcsv($fp, $data);
                $progress->advance();
            }
        }
        $progress->finish();
        $output->writeln('Convert is finished, memory: ' . (memory_get_peak_usage(1) / 1024) . "kb");
        fclose($fp);
        dbase_close($dbf);
    }

    /**
     * Write first string file - add columns names
     * @param array $columnInfo data from columns in input db
     * @param resource $fp opened file
     */
    private function writeHead($columnInfo, $fp)
    {
        $names = [];
        foreach ($columnInfo as $column) {
            $names[] = $column['name'];
        }
        fputcsv($fp, $names);
    }

    /**
     * Get string for append in final file
     * @param int $dbf input database resource id from dbase_open
     * @param int $i element index
     * @return array array to append in csv
     */
    private function getRowToString($dbf, $i)
    {
        $row = dbase_get_record($dbf, $i);
        if ($row['deleted'] == 1) {
            return [];
        }
        unset($row['deleted']);

        if ($this->charsetInput) {
            $row = array_map(array($this,"conv"), $row);
        }
        return $row;
    }

    /**
     * @param string $outputFile path to final file
     * @return boolean
     */
    private function checkWritePermission($outputFile)
    {
        if (is_writable($outputFile)) {
            return true;
        }
        if (!file_exists($outputFile)) {
            $checkFile = file_put_contents($outputFile, " ");
            return (bool) $checkFile;
        }
        return false;
    }

    private function conv($item)
    {
        return iconv($this->charsetInput, $this->charsetOutput, trim($item));
    }
}
