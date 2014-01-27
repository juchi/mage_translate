<?php
// require Mage_Shell_Abstract
require_once '../abstract.php';

error_reporting(E_ERROR);

/**
 * Class Translation_Export
 *
 * @author  Julien Chichignoud
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
abstract class Translation_Abstract extends Mage_Shell_Abstract
{
    /**
     * @var bool
     */
    protected $_includeMage = false;

    /**
     * @var array
     */
    protected $_langs = array();

    /**
     * @var array
     */
    protected $_mandatory = array();

    /**
     * Check that the mandatory arguments where provided
     *
     * @return void
     * @throws Exception
     */
    protected function _checkMandatoryArgs()
    {
        foreach ($this->_mandatory as $_arg) {
            if (!isset($this->_args[$_arg])) {
                throw new Exception("The argument $_arg is mandatory");
            }
        }
    }

    /**
     * Get the list of locales to process
     *
     * @param string $defaultDir
     *
     * @return void
     */
    protected function _getLocales($defaultDir)
    {
        if ($this->getArg('locale')) {
            $this->_langs = explode(',', $this->getArg('locale'));
        } else {
            $dirs = scandir($defaultDir);
            foreach ($dirs as $_dirname) {
                $_dir = $defaultDir . ''.$_dirname;
                if (is_dir($_dir) && strpos($_dirname, '.') === false) {
                    $this->_langs[] = $_dirname;
                }
            }
        }
    }

    /**
     * Open a file
     *
     * @param string $fileName
     * @param string $mode
     *
     * @return resource
     * @throws Exception
     */
    protected function _openFile($fileName, $mode = 'r')
    {
        $handle = fopen($fileName, $mode);
        if (!$handle) {
            throw new Exception("The file $fileName could not be opened.\n");
        }
        return $handle;
    }

    /**
     * Close a file
     *
     * @param resource $handle
     *
     * @return void
     */
    protected function _closeFile($handle)
    {
        if ($handle) {
            fclose($handle);
        }
    }

    /**
     * Display the errors that occurred
     *
     * @param array $errors
     *
     * @return  void
     */
    protected function _displayErrors($errors)
    {
        if (count($errors) && !$this->getArg('s')) {
            echo "Some warnings where raised during processing:\n";
            foreach ($errors as $error) {
                echo $error."\n";
            }
        }
    }
}
