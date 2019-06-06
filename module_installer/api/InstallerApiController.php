<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Installer\Api;

use Kajona\Api\System\ApiControllerInterface;
use Kajona\Installer\System\SamplecontentInstallerHelper;
use Kajona\Packagemanager\System\PackagemanagerManager;
use Kajona\Packagemanager\System\PackagemanagerMetadata;
use Kajona\System\System\Config;
use Kajona\System\System\Database;
use Kajona\System\System\DbConnectionParams;
use Kajona\System\System\Filesystem;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\SystemModule;
use PSX\Http\Environment\HttpContext;
use PSX\Http\Exception\BadRequestException;
use PSX\Http\Exception\InternalServerErrorException;
use PSX\Http\Exception\NotFoundException;
use Symfony\Component\Lock\Factory;
use Symfony\Component\Lock\Store\FlockStore;

/**
 * InstallerApiController
 *
 * @author christoph.kappestein@gmail.com
 * @since 7.1
 */
class InstallerApiController implements ApiControllerInterface
{
    const INSTALL_STATE_PENDING = 1;
    const INSTALL_STATE_COMPLETED = 2;

    /**
     * @inject system_db
     * @var Database
     */
    protected $connection;

    /**
     * Endpoint which can be called to verify that we are actually talking to a valid AGP API
     *
     * @api
     * @method GET
     * @path /agp
     * @authorization filetoken
     */
    public function getAgpInfo()
    {
        return [
            "success" => true,
            "base_url" => substr($_SERVER["REQUEST_URI"], 0, -3),
        ];
    }

    /**
     * @api
     * @method GET
     * @path /installer/systeminfo
     * @authorization filetoken
     */
    public function getSystemInfo()
    {
        return [
            "version" => $this->getPHPVersion(),
            "extensions" => $this->getPHPExtensions(),
            "folders" => $this->getFolders(),
        ];
    }

    /**
     * @api
     * @method GET
     * @path /installer/connection
     * @authorization filetoken
     */
    public function getConnection()
    {
        $allExtensions = ["mysqli", "pgsql", "sqlsrv", "sqlite3", "oci8"];
        $extensions = [];
        foreach ($allExtensions as $extension) {
            $extensions[$extension] = in_array($extension, get_loaded_extensions());
        }

        $config = Config::getInstance("module_system", "config.php");
        $configured = $config->getConfig("dbhost") !== "%%defaulthost%%";
        if ($configured) {
            $conf = [
                "host" => $config->getConfig("dbhost"),
                "username" => $config->getConfig("dbusername"),
                "database" => $config->getConfig("dbname"),
                "port" => $config->getConfig("dbport"),
            ];
        } else {
            $conf = false;
        }

        return [
            "config" => $conf,
            "extensions" => $extensions,
        ];
    }

    /**
     * @api
     * @method POST
     * @path /installer/connection
     * @authorization filetoken
     */
    public function validateConnection($body)
    {
        return [
            "check" => $this->checkConnection(
                $body["driver"] ?? null,
                $body["hostname"] ?? null,
                $body["username"] ?? null,
                $body["password"] ?? null,
                $body["dbname"] ?? null,
                $body["port"] ?? null
            ),
        ];
    }

    /**
     * @api
     * @method POST
     * @path /installer/config
     * @authorization filetoken
     */
    public function writeConfig($body)
    {
        $configFile = _realpath_."project/module_system/system/config/config.php";

        if (is_file($configFile)) {
            throw new InternalServerErrorException("Config is already written");
        }

        $available = $this->checkConnection(
            $body["driver"] ?? null,
            $body["hostname"] ?? null,
            $body["username"] ?? null,
            $body["password"] ?? null,
            $body["dbname"] ?? null,
            $body["port"] ?? null
        );

        if ($available) {
            $content = "<?php\n";
            $content.= "/*\n Kajona V7 config-file.\n If you want to overwrite additional settings, copy them from /core/module_system/system/config/config.php into this file.\n*/";
            $content.= "\n\n\n";
            $content.= "  \$config['dbhost']               = '".$body["hostname"]."';                   //Server name \n";
            $content.= "  \$config['dbusername']           = '".$body["username"]."';                   //Username \n";
            $content.= "  \$config['dbpassword']           = '".$body["password"]."';                   //Password \n";
            $content.= "  \$config['dbname']               = '".$body["dbname"]."';                     //Database name \n";
            $content.= "  \$config['dbdriver']             = '".$body["driver"]."';                     //DB-Driver \n";
            $content.= "  \$config['dbport']               = '".$body["port"]."';                       //Database port \n";
            $content.= "\n";

            $result = file_put_contents($configFile, $content);
        } else {
            $result = false;
        }

        return [
            "written" => $result,
        ];
    }

    /**
     * @api
     * @method GET
     * @path /installer/module
     * @authorization filetoken
     */
    public function getModules()
    {
        $manager = new PackagemanagerManager();
        $modules = $manager->getAvailablePackages();
        $result = [];
        foreach ($modules as $module) {
            $result[] = $module;
        }

        $result = $manager->sortPackages($result, true);

        return [
            "modules" => $result,
        ];
    }

    /**
     * @api
     * @method POST
     * @path /installer/module
     * @authorization filetoken
     */
    public function moduleInstall($body)
    {
        if (!isset($body["module"])) {
            throw new \RuntimeException("No module provided");
        }

        $manager = new PackagemanagerManager();

        $module = $manager->getPackage($body["module"]);
        if (!$module instanceof PackagemanagerMetadata) {
            throw new NotFoundException("Module not found");
        }

        ResponseObject::getInstance()->setObjEntrypoint(RequestEntrypointEnum::INSTALLER());

        $handler = $manager->getPackageManagerForPath($module->getStrPath());

        if ($handler->isInstallable()) {
            $store = new FlockStore(_realpath_."project/temp/cache");
            $factory = new Factory($store);
            $lock = $factory->createLock("install-" . $module->getStrTitle());

            $return = "";
            $status = "locked";

            if ($lock->acquire()) {
                $return = $handler->installOrUpdate();
                $status = "success";

                $lock->release();
            }

            return [
                "status" => $status,
                "module" => $module->getStrTitle(),
                "log" => $return
            ];
        } else {
            // is not installable either since the module has no installer or the requirements are not met, we still
            // return a 200 since it could be possible to install the module later on
            return [
                "status" => "not_installable",
                "module" => "The module " . $module->getStrTitle() . " is currently not installable",
            ];
        }
    }

    /**
     * @api
     * @method GET
     * @path /installer/sample
     * @authorization filetoken
     */
    public function getSample()
    {
        $manager = new PackagemanagerManager();
        $modules = $manager->getAvailablePackages();
        $result = [];
        $names = [];

        foreach ($modules as $module) {
            $sampleInstaller = SamplecontentInstallerHelper::getSamplecontentInstallerForPackage($module);
            if ($sampleInstaller !== null) {
                $installable = false;
                $installed = false;
                if (SystemModule::getModuleByName($module->getStrTitle()) != null) {
                    $installable = true;
                    $installed = $sampleInstaller->isInstalled();
                }

                $class = get_class($sampleInstaller);

                $names[] = substr($class, strrpos($class, "\\") + 1);
                $result[] = [
                    "title" => $module->getStrTitle(),
                    "class" => $class,
                    "isInstallable" => $installable,
                    "isInstalled" => $installed,
                ];
            }
        }

        array_multisort($names, SORT_ASC, $result);

        return [
            "samples" => $result,
        ];
    }

    /**
     * @api
     * @method POST
     * @path /installer/sample
     * @authorization filetoken
     */
    public function sampleInstall($body)
    {
        if (!isset($body["module"])) {
            throw new \RuntimeException("No module provided");
        }

        $manager = new PackagemanagerManager();

        $module = $manager->getPackage($body["module"]);
        if (!$module instanceof PackagemanagerMetadata) {
            throw new NotFoundException("Module not found");
        }

        ResponseObject::getInstance()->setObjEntrypoint(RequestEntrypointEnum::INSTALLER());

        $sampleContent = SamplecontentInstallerHelper::getSamplecontentInstallerForPackage($module);
        if ($sampleContent != null ) {
            $return = SamplecontentInstallerHelper::install($sampleContent);

            return [
                "status" => "success",
                "module" => $body["module"],
                "log" => $return,
            ];
        } else {
            throw new InternalServerErrorException("No sample content available for " . $body["module"]);
        }
    }

    /**
     * @api
     * @method GET
     * @path /installer/log
     * @authorization filetoken
     */
    public function getLogs()
    {
        $fileSystem = new Filesystem();
        $files = $fileSystem->getFilelist(_projectpath_."/log", array(".log"));
        $return = [];

        foreach ($files as $fileName) {
            $return[] = substr($fileName, 0, -4);
        }

        return [
            "logs" => $return
        ];
    }

    /**
     * @api
     * @method GET
     * @path /installer/log/{log}
     * @authorization filetoken
     */
    public function getLogDetail(HttpContext $context)
    {
        $lines = (int) $context->getParameter("lines");
        if (!empty($lines)) {
            $lines = min(200, max(1, $lines));
        } else {
            $lines = null;
        }

        $fileSystem = new Filesystem();
        $files = $fileSystem->getFilelist(_projectpath_."/log", [".log"]);
        $file = $context->getUriFragment("log") . ".log";

        // it can happen that the log file is really large so we max return this amount of bytes
        $maxSize = 1024 * 50;

        if (in_array($file, $files)) {
            $path = _realpath_._projectpath_."/log/".$file;
            $size = filesize($path);

            if ($lines !== null) {
                $fileSystem->openFilePointer(_projectpath_."/log/".$file, "r");
                $result = $fileSystem->readLastLinesFromFile($lines);
                $fileSystem->closeFilePointer();
            } else {
                $result = file_get_contents($path, false, null, 0, $maxSize);
            }

            $result = str_replace(["\r\n", "\n", "\r"], "\n", $result);
            $result = array_values(explode("\n", $result));

            return [
                "size" => $size,
                "file" => $file,
                "lines" => $result,
            ];
        } else {
            throw new BadRequestException("Invalid log");
        }
    }

    private function checkConnection($driver, $hostname, $username, $password, $dbname, $port)
    {
        return $this->connection->validateDbCxData(
            $driver,
            new DbConnectionParams($hostname, $username, $password, $dbname, $port)
        );
    }

    private function getPHPVersion()
    {
        $requiredPHPVersion = "7.2";
        $actualPHPVersion = phpversion();

        return [
            "required" => $requiredPHPVersion,
            "actual" => $actualPHPVersion,
            "match" => version_compare($requiredPHPVersion, $actualPHPVersion, "<"),
        ];
    }

    private function getPHPExtensions()
    {
        $neededExtensions = array(
            "curl",
            "exif",
            "fileinfo",
            "gd",
            "iconv",
            "json",
            "ldap",
            "libxml",
            "mbstring",
            "openssl",
            "zend opcache",
            "pcre",
            "phar",
            "reflection",
            "session",
            "simplexml",
            "sockets",
            "spl",
            "xml",
            "xmlreader",
            "xmlwriter",
            "xsl",
            "zip"
        );

        $availableExtensions = array_map(function(string $val) {
            return strtolower($val);
        }, get_loaded_extensions());

        $result = [];
        foreach ($neededExtensions as $extension) {
            $result[$extension] = in_array($extension, $availableExtensions);
        }

        return $result;
    }

    private function getFolders()
    {
        $folders = array(
            "/project/module_system/system/config",
            "/project/dbdumps",
            "/project/log",
            "/project/temp",
            "/files/cache",
            "/files/images",
            "/files/downloads",
            "/files/temp",
        );

        $result = [];
        foreach ($folders as $file) {
            if (is_writable(_realpath_.$file)) {
                $result[$file] = true;
            } else {
                $result[$file] = false;
            }
        }

        return $result;
    }
}
