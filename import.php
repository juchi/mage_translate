<?php
// require Translation_Abstract
require_once 'abstract.php';

/**
 * Class Translation_Import
 * Import a client's csv translation file into magento
 * The file is excepted to contain a variable amount of columns, each of which
 * containing the translated labels for a given locale
 *
 * @author  Julien Chichignoud
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Translation_Import extends Translation_Abstract
{
    protected $_file = null;

    protected $_destinationDir = null;

    /**
     * @var array
     */
    protected $_mandatory = array('file', 'destination');

    /**
     * Run the script
     *
     * @return void
     */
    public function run()
    {
        try {
            $this->_checkMandatoryArgs();
            $this->_checkInputFile();
            $this->_checkDirectories();
            $this->_getLocales($this->_destinationDir);

            $result = $this->_parseFile($this->_file);
            $this->_displayErrors($result['warnings']);
            $this->_constructLocaleFiles($result['data']);
            echo "The file was parsed successfully.\n";
        } catch (Exception $e) {
            echo "{$e->getMessage()}\n";
        }
    }

    /**
     * Check the columns present in the file
     *
     * @param array $columns
     *
     * @return array
     */
    protected function _checkFileColumns($columns)
    {
        $warnings = array();

        foreach ($columns as $lang) {
            if (!in_array($lang, $this->_langs)) {
                $warnings[] = 'Locale '.$lang . ' found in the file but not expected. Ignoring this column.';
            }
        }
        foreach ($this->_langs as $lang) {
            if (!in_array($lang, $columns)) {
                $warnings[] = 'Locale '.$lang . ' expected but not present in the file.';
            }
        }

        return $warnings;
    }

    /**
     * Parse the translated file
     *
     * @param string $fileName  The name of the csv file
     * @param string $separator The csv separator
     * @param string $delimiter The csv delimiter
     *
     * @return array
     * @throws Exception
     */
    protected function _parseFile($fileName, $separator = ',', $delimiter = '"')
    {
        $sourceHandle = $this->_openFile($fileName, 'r');
        $finalData = array();
        $cptLine = 0;
        $warnings = array();
        $baseLine = array();

        while (($data = fgetcsv($sourceHandle, 0, $separator, $delimiter)) !== false) {
            // We have to ignore the first header lines
            if ($cptLine++ == 0) {
                // first column is the translation key
                array_shift($data);
                $baseLine = $data;

                $columnWarnings = $this->_checkFileColumns($baseLine);
                $warnings = array_merge($warnings, $columnWarnings);
                continue;
            } else if (count($baseLine) != count($data) - 1) { // check each line is same size
                throw new Exception(
                    "In $fileName : The line $cptLine has ".(count($data)-1).
                    " entries instead of ".count($baseLine)." expected.\n"
                );
                continue;
            }

            // Shift of the "code" column
            $code = array_shift($data);
            if (!$code) {
                $warnings[] = 'No translation key on line ' . $cptLine . ', skipping...';
                continue;
            }

            if (!array_key_exists($code, $finalData)) {
                $finalData[$code] = array();
            }

            foreach ($baseLine as $lang) {
                if (!in_array($lang, $this->_langs)) {
                    // do not import unexpected locales
                    continue;
                }
                $finalData[$code][$lang] = array_shift($data);
            }
        }
        fclose($sourceHandle);

        return array('warnings' => $warnings, 'data' => $finalData);
    }

    /**
     * Write the updated locale files in the destination directory
     *
     * @param array $translatedData
     *
     * @return void
     */
    protected function _constructLocaleFiles($translatedData)
    {
        $finalLangs = array_keys(reset($translatedData));
        $filename = basename($this->_file);

        $fileHandles = array();

        foreach ($finalLangs as $lang) {
            $dirLang = $this->_destinationDir.'/'.$lang;
            if (!file_exists($dirLang) || !is_dir($dirLang)) {
                mkdir($dirLang);
            }
            $fileHandles[$lang] = $this->_openFile($dirLang.'/'.$filename, "c+");

            // update mode
            // add possible translations present in destination files but not in source file
            if (!$this->getArg('truncate')) {
                while (($data = fgetcsv($fileHandles[$lang], 0, ',', '"')) !== false) {
                    if (!isset($translatedData[$data[0]][$lang])) {
                        if (!isset($translatedData[$data[0]])) {
                            $translatedData[$data[0]] = array();
                        }
                        $translatedData[$data[0]][$lang] = $data[1];
                    }
                }
            }

            // empty existing files
            ftruncate($fileHandles[$lang], 0);
            fseek($fileHandles[$lang], 0);
        }

        ksort($translatedData);
        $finalContent = array();
        foreach ($translatedData as $code => $translated) {
            foreach ($translated as $lang => $label) {
                $label = str_replace('"', '""', $label);
                $code = str_replace('"', '""', $code);

                $newLine = '"'.$code.'","'.$label.'"';
                $finalContent[$lang][] = $newLine;
            }
        }

        foreach ($finalLangs as $lang) {
            fwrite($fileHandles[$lang], implode("\n", $finalContent[$lang]));
            fclose($fileHandles[$lang]);
        }
    }

    /**
     * Check the input file (existence and format)
     *
     * @throws Exception
     */
    protected function _checkInputFile()
    {
        $this->_file = $this->_args['file'];

        if (substr($this->_file, -3) !== 'csv') {
            throw new Exception('The file should be in the CSV format.');
        }

        if (!is_file($this->_file)) {
            throw new Exception('The file "'.$this->_file.'" could not be found.');
        }
    }

    /**
     * Check the existence of the working directories
     *
     * @return void
     * @throws Exception
     */
    protected function _checkDirectories()
    {
        $this->_destinationDir = $this->getArg('destination');

        if (!is_dir($this->_destinationDir)) {
            throw new Exception('The destination dir could not be found.');
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
  --file        The csv file to import
                ex: ./translate.csv
  --destination The destination folder ("locale") for the translations
                ex: [magento_base]/app/locale

Options:
  -h            Short alias for help
  help          This help
  -s            Silent. No warnings.
  --locale      List of locales to import from the file, comma separated.
                If not specified, the script will detect them from the destination_dir's content.
                ex: en_GB,fr_FR,es_ES
  --truncate    Truncate the existing destination files before writing
                If not specified, the default mode is 'update'

USAGE;
    }
}

$import = new Translation_Import();
$import->run();
