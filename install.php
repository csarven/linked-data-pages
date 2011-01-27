<?php

class Install
{
    var $post;
    var $errorMessages = array();
    var $fileContents = array();

    function __construct()
    {

    }


    function configureFileContents()
    {
        $this->fileContents['index.php'] = <<<EOD
<?php
/**
 * Base file where everything is configured and dispatched to
 * 
 * PHP version 5
 *
 * @category  Base
 * @package   LDP
 * @author    Sarven Capadisli <sarven.capadisli@deri.org>
 * @copyright 2010 Digital Enterprise Research Institute
 * @license   http://www.fsf.org/licensing/licenses/gpl-3.0.html GNU General Public License version 3.0
 * @link      http://deri.org/
 */

define('SITE_DIR', '{$this->post['dir_site']}'); /* Site directory */
define('LDP_DIR', '{$this->post['dir_LDP']}'); /* This package's directory */
define('PAGET_DIR', '{$this->post['dir_paget']}');
define('MORIARTY_DIR', '{$this->post['dir_moriarty']}');
define('MORIARTY_ARC_DIR', '{$this->post['dir_arc2']}');

define('STORE_URI', '{$this->post['sparql_endpoint']}');

require_once LDP_DIR . 'classes/LDP_Config.php';
require_once LDP_DIR . 'classes/LDP.php';
require_once LDP_DIR . 'classes/SITE_Template.php';

\$config = new LDP_Config(); /* Grabs configuration values from this site */
\$space = new LDP(\$config);  /* Starts to bulid the request */
\$space->dispatch();          /* Dispatches the requested URI */
?>

EOD;

    }


    function showPage()
    {
        echo <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html
PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
       "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en_CA" lang="en_CA">
    <head>
        <title>Install</title>
    </head>
    <body id="install">
        <div id="wrap">
            <div id="core">
                <div id="content">
                     <div id="content_inner">
                        <h1>Linked Data Pages installation</h1>
{$this->handleInstall()}
                   </div>
                </div>
            </div>
        </div>
    </body>
</html>
EOD;
    }


    function handleInstall()
    {
        //TODO: check to see if it was already installed

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            return $this->processPost();
        } else {
            return $this->getForm();
        }
    }


    function getForm()
    {
        $formDataItems = array(
            'dir_site' => '
                            <label for="dir_site">Site</label>
                            <input type="text" id="dir_site" name="dir_site" value="'.$this->post['dir_site'].'"/>
                            <p class="form_guide">e.g., <code>/var/www/site/</code></p>
            ',
            'dir_LDP' => '
                            <label for="dir_LDP">LDP (this framework)</label>
                            <input type="text" id="dir_LDP" name="dir_LDP" value="'.$this->post['dir_LDP'].'"/>
                            <p class="form_guide">e.g., <code>/var/www/lib/linked-data-pages/</code></p>
            ',
            'dir_paget' => '
                            <label for="dir_paget">Paget</label>
                            <input type="text" id="dir_paget" name="dir_paget" value="'.$this->post['dir_paget'].'"/>
                            <p class="form_guide">e.g., <code>/var/www/lib/paget/</code></p>
            ',
            'dir_moriarty' => '
                            <label for="dir_moriarty">Moriarty</label>
                            <input type="text" id="dir_moriarty" name="dir_moriarty" value="'.$this->post['dir_moriarty'].'"/>
                            <p class="form_guide">e.g., <code>/var/www/lib/moriarty/</code></p>
            ',
            'dir_arc2' => '
                            <label for="dir_arc2">ARC2</label>
                            <input type="text" id="dir_arc2" name="dir_arc2" value="'.$this->post['dir_arc2'].'"/>
                            <p class="form_guide">e.g., <code>/var/www/lib/arc2/</code></p>
            ',
            'sparql_endpoint' => '
                            <label for="sparql_endpoint">SPARQL Endpoint</label>
                            <input type="text" id="sparql_endpoint" name="sparql_endpoint" value="'.$this->post['sparql_endpoint'].'"/>
                            <p class="form_guide">e.g., <code>http://localhost:3030/dataset/query</code></p>
            '
        );

        $formInstall = <<<EOD
        <form method="post" action="install.php" class="form_settings" id="form_install">
            <fieldset id="settings_site">
                <legend>Configuration</legend>

                <div class="form_instructions">
                    <p>This installation process temporarily requires write access to the installation (site) directory. Please make sure to give write access to the user that's running this installation script.</p>
                </div>

                <fieldset id="settings_directories">
                    <legend>Directories</legend>

                    <ul class="form_data">
EOD;
                        foreach($formDataItems as $k => $v) {
                            $class = (isset($this->errorMessages[$k])) ? ' class="form_error"' : '';

                            $formInstall .= "\n\t\t\t<li$class>";
                            $formInstall .= $formDataItems[$k];
                            if (isset($this->errorMessages[$k])) {
                                $formInstall .= '<p class="note_error">'.$this->errorMessages[$k].'</p>';
                            }
                            $formInstall .= "\t\t</li>";
                        }
        $formInstall .= <<<EOD

                    </ul>
                </fieldset>

                <input type="submit" name="submit" class="submit" value="Link me up!" title="Just submit the form already! =)"/>
            </fieldset>
        </form>
EOD;
        return $formInstall;
    }


    function successInstallation()
    {
        unset($_POST);

        $o = <<<EOD
            <dl class="installation_results">
                <dt>Results</dt>
                <dd>
                    <ul>
                        <li>Installation successful!</li>
                        <li>... we will add more todo/tips here.</li>
                    </ul>
                </dd>
            </dl>
EOD;

        return $o;

    }

    function processPost()
    {
        $this->post = $_POST;

        unset($this->post['submit']);

        foreach($this->post as $key => $value) {
            /**
             * TODO: This is basic sanitization, we can do more.
             */
            $this->post[$key] = trim($value);

            if (!empty($this->post[$key])) {
                $this->post[$key] = (substr($this->post[$key], -1) != '/') ? $this->post[$key].'/' : $this->post[$key];

                if (!$this->checkRequirements($key)) {
                    $this->errorMessages[$key] = $this->getErrorMessage('nowrite', $key);
                }
            }
            else {
                $this->errorMessages[$key] = $this->getErrorMessage('empty', $key);
            }
        }

        if (count($this->errorMessages) > 0) {
            return $this->getForm();
        }
        else {
            $this->copyFiles();
            return $this->successInstallation();
        }
    }


    /**
     * Write permissions to a directory.
     */
    function checkRequirements($key)
    {
        clearstatcache();

        $directory = $this->post[$key];

        $this->configureFileContents();

        switch($key) {
            case 'dir_site':
                if (is_writable($directory)) {
                    $this->writeToFile($directory.'index.php', $this->fileContents['index.php']);
                    /**
                     * TODO: $this->writeToFile($directory.'config.php', $this->fileContents['config.php']);
                     */
                    return true;
                }
                else {
                    return false;
                }
                break;

            default:
                return true;
                break;
        }
    }


    function copyFiles()
    {
        $source = $this->post['dir_LDP'];
        $dest =   $this->post['dir_site'];

        $this->recurse_copy($source, $dest);
    }

    /**
     * Adapted on http://ca2.php.net/manual/en/function.copy.php#91010
     */
    function recurse_copy($src,$dst) {
        $dir = opendir($src);
        @mkdir($dst);

        $filesSkip = array(
            '.',
            '..',
            'classes',
            'COPYING',
            'index.php.sample',
            '.git',
            'install.php',
            'patches',
            'scripts',
            'TODO'
        );

        while(false !== ( $file = readdir($dir)) ) {
            if (!in_array($file, $filesSkip)) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recurse_copy($src . '/' . $file,$dst . '/' . $file);
                }
                else {
                    copy($src . '/' . $file,$dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }


    function getErrorMessage($code, $key)
    {
        switch($code) {
            case 'nowrite':
                return '<code>'.$this->post[$key].'</code> is not writable.';
                break;

            case 'empty':
                return 'Required directory name.';
                break;

            default:
                return 'Who said what now? Check errorMessage()';
                break;
        }

    }


    function writeToFile($file, $fileContents)
    {
        $fp = fopen($file, 'w');
        fwrite($fp, $fileContents);

        fclose($fp);
    }

}


$i = new Install();
$i->showPage();

?>
