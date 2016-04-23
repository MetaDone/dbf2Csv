<?php

namespace Convert\Command;

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

    protected function configure()
    {
        $this
                ->setName('convert')
                ->setDescription('Convert DBF to CSV')
                ->addArgument(
                        'input', InputArgument::REQUIRED, 'File to convert'
                )
                ->addArgument(
                        'output', InputArgument::REQUIRED, 'Output path'
                )
                ->addArgument(
                        'charsetInput', InputArgument::OPTIONAL, 'Charset of dbf database'
                )
                ->addArgument(
                        'charsetOutput', InputArgument::OPTIONAL, 'Charset of final file'
                )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        $inputFile = $input->getArgument('input');
        $outputFile = $input->getArgument('output');
        $charsetInput = $input->getArgument('charsetInput');
        $charsetOutput = $input->getArgument('charsetOutput');
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
        $numRec =dbase_numrecords($dbf);
        $progress = new ProgressBar($output, $numRec);
        $columnInfo = dbase_get_header_info($dbf);
        $countElements = count($columnInfo);
        $this->writeHead($columnInfo, $countElements, $outputFile);

        $output->writeln('Convert start');
        for ($i = 0; $i <= $numRec; $i++) {
            $string = $this->getRowToString($dbf, $i, $countElements, $charsetInput, $charsetOutput);
            file_put_contents($outputFile, $string . "\n", FILE_APPEND);
            $progress->advance();
        }
        $progress->finish();
        $output->writeln('Convert is finished, memory: ' . (memory_get_peak_usage(1) / 1024) . "kb");
        dbase_close($dbf);
    }

    /**
     * Write first string file - add columns names
     * @param array $columnInfo data from columns in input db
     * @param int $countElements count columns
     * @param string $outputFile path to final file
     */
    private function writeHead($columnInfo, $countElements, $outputFile)
    {

        $headString = "";

        for ($i = 0; $i < $countElements; $i++) {
            if ($i == $countElements - 1) {
                $headString.=$columnInfo[$i]['name'];
            } else {
                $headString.=$columnInfo[$i]['name'] . ";";
            }
        }

        file_put_contents($outputFile, "");
        file_put_contents($outputFile, $headString . "\n", FILE_APPEND);
    }

    /**
     * Get string for append in final file
     * @param int $dbf input database resource id from dbase_open
     * @param int $i element index
     * @param int $countElements count elements in string
     * @param string $charsetInput input database charset
     * @param string $charsetOutput output file charset
     * @return string string to append in csv
     */
    private function getRowToString($dbf, $i, $countElements, $charsetInput = false, $charsetOutput = "UTF-8")
    {

        $out = "";
        $row = dbase_get_record($dbf, $i);
        //print_r($row);
        //sleep(5);
        $current = 0;
        foreach ($row as $key => $value) {
            if ($key === "deleted") {
                continue;
            }
            if ($current == $countElements - 1) {
                $out.= $this->getValueForCSV(trim($value));
            } else {
                $out.= $this->getValueForCSV(trim($value)) . ";";
            }
            $current++;
        }

        if ($charsetInput) {
            $out = iconv($charsetInput, $charsetOutput, $out);
        }
        return $out;
    }

    /**
     * @param string|int $value value element in database string 
     * @return string|int formatted string for csv
     */
    private function getValueForCSV($value)
    {
        if (is_numeric($value)) {
            return $value;
        } else {
            return '"' . str_replace('"', '""', $value) . '"';
        }
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
    
}
