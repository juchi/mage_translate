<?php
// require Translation_Abstract
require_once 'abstract.php';

/**
 * Class Translation_Export
 *
 * @author  Julien Chichignoud
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Translation_Export extends Translation_Abstract
{
    protected $_file = null;

    protected $_sourceDir = null;

    protected $_destinationFile = null;

    /**
     * @var array
     */
    protected $_mandatory = array('file', 'source');

    /**
     * Run the script
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->_checkMandatoryArgs();
            $this->_checkFiles();
            $this->_checkDirectories();
            $this->_getLocales($this->_sourceDir);
            $export = $this->_getAllData();
            $this->_buildExportFile($export);
            echo "The export file was built successfully.\n";
        } catch (Exception $e) {
            echo "{$e->getMessage()}\n";
        }
    }

    /**
     * Get all the data to export
     *
     * @return array
     * @throws Exception
     */
    protected function _getAllData()
    {
        $exportData = array();

        foreach ($this->_langs as $lang) {
            $file = $this->_sourceDir.'/'.$lang.'/'.$this->_file;
            $data = $this->_getLocaleData($file);
            foreach ($data as $code => $value) {
                $exportData[$code][$lang] = $value;
            }
        }

        return $exportData;
    }

    /**
     * Get all the translations in a given file
     *
     * @param string $file
     *
     * @return array
     */
    protected function _getLocaleData($file)
    {
        $handle = $this->_openFile($file, 'r');

        $translate = array();
        while (($data = fgetcsv($handle, 0, ',', '"')) !== false) {
            $code = $data[0];
            $translate[$code] = $data[1];
        }
        $this->_closeFile($handle);

        return $translate;
    }

    /**
     * Build the file to export
     *
     * @param $exportData
     *
     * @return void
     */
    protected function _buildExportFile($exportData)
    {
        $lines = array();
        $lines[] = 'Code (do not modify it),'.implode(',', $this->_langs);

        foreach ($exportData as $code => $translations) {
            $line = array(str_replace('"', '""', $code));
            foreach ($this->_langs as $lang) {
                if (!isset($translations[$lang])) {
                    $translations[$lang] = $code;
                }
                $line[] = str_replace('"', '""', $translations[$lang]);
            }
            $lines[] = '"'.implode('","', $line).'"';

        }

        $handle = $this->_openFile($this->_file, 'w');
        fwrite($handle, implode("\n", $lines));
        $this->_closeFile($handle);
    }

    /**
     * Check the existence of the working directories
     *
     * @return void
     * @throws Exception
     */
    protected function _checkDirectories()
    {
        $this->_sourceDir = $this->getArg('source');

        if (!is_dir($this->_sourceDir)) {
            throw new Exception('The source dir could not be found.');
        }
    }

    /**
     * Check files arguments
     *
     * @return void
     * @throws Exception
     */
    protected function _checkFiles()
    {
        $this->_file = $this->getArg('file');
        $this->_destinationFile = $this->getArg('destination');

        if (!$this->_destinationFile) {
            $this->_destinationFile = './'.$this->_file;
        }
        if ((!is_file($this->_destinationFile) && !is_writable(dirname($this->_destinationFile))) ||
            (is_file($this->_destinationFile) && !is_writable($this->_destinationFile))) {
            throw new Exception('The destination file ('.$this->_destinationFile.') is not writable !');
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     * @return string
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php import.php --file import_file.csv --destination dest_folder [options]

Mandatory arguments:
  --file        The csv filename to export
                ex: translate.csv
  --source      The source folder ("locale") for the translations
                ex: [magento_base]/app/code/locale

Options:
  -h            Short alias for help
  help          This help
  -s            Silent. No warnings.
  --locale      List of locales to export in the file, comma separated.
                If not specified, the script will detect them from the source dir's content.
                ex: en_GB,fr_FR,es_ES
  --destination The destination file. By default it will be [working_dir]/[file]
                ex: /home/smile/export.csv

USAGE;
    }
}

$export = new Translation_Export();
$export->run();
