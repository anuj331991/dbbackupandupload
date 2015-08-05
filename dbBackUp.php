<?php

class DbBackupScript
{
    const INFO_MESSAGE = 'info';
    const ERROR_MESSAGE = 'error';
    const WARNING_MESSAGE = 'warning';

    public $backUpFileName = '';
    public $db_name = '';
    public $fileSize = '';

    /**
     * this function will dump the database (LEAD-140)
     */
    function dumpDb()
    {

        try {

            $root_dir = dirname(__DIR__);
            $backUpFolderPath = $root_dir . '/api/dbBackUp';

            if (!file_exists($backUpFolderPath)) {
                mkdir($backUpFolderPath, 0777, true);
            }

            chmod($backUpFolderPath, 0777);
            $host = gethostname();
            $hostIp = '127.0.0.1';
            $db_name = 'mydb';
            $username = 'username';
            $password = 'password';

            $current_date = date('Y-m-d');
            $backUpFileName = $current_date . '_' . $db_name . '.sql';
            $fullBackUpPath = $backUpFolderPath . '/' . $backUpFileName;


            //set the public variables
            $this->backUpFileName = @$backUpFileName;
            $this->db_name = @$db_name;

            //check if the db backup already exists already exists
            if (!file_exists($fullBackUpPath)) {

                //start db backup dump log
                $this->createLog(self::INFO_MESSAGE, $backUpFileName, $db_name, "", 'Db dump started');
                exec("mysqldump --user=$username --password=$password --host=$hostIp $db_name > $fullBackUpPath"); //dump sql

                //determine the size of db backup created
                $fileSize = filesize($fullBackUpPath);
                $this->fileSize = @$fileSize;

                $fileSize = $this->formatSizeUnits($fileSize);

                //end db backup log
                $this->createLog(self::INFO_MESSAGE, $backUpFileName, $db_name, @$fileSize, 'Db dump created');
                $this->createZip($backUpFolderPath, $backUpFileName, $fullBackUpPath);

                $zipSize = filesize($fullBackUpPath . '.zip');
                $zipSize = $this->formatSizeUnits($zipSize);

                //log the zip file created
                $this->createLog(self::INFO_MESSAGE, $backUpFileName, $db_name, @$zipSize, 'Db zip created');

            } else {

                //determine the size of existing backup
                $fileSize = filesize($fullBackUpPath);
                $this->fileSize = @$fileSize;
                $this->createLog(self::WARNING_MESSAGE, $backUpFileName, $db_name, $fileSize, 'Db dump already exists');

                //check if the zip exists
                if (!file_exists($fullBackUpPath . '.zip')) {
                    $this->createZip($backUpFolderPath, $backUpFileName, $fullBackUpPath);

                    $zipSize = filesize($fullBackUpPath . '.zip');
                    $zipSize = $this->formatSizeUnits($zipSize);

                    //log the existing file zip file created
                    $this->createLog(self::INFO_MESSAGE, $backUpFileName, $db_name, $zipSize, 'Zip of existing db created');
                }
            }


        } catch (Exception $e) {
            $message = $e->getMessage();
            $this->createLog(self::ERROR_MESSAGE, $this->backUpFileName, $this->db_name, $this->fileSize, $message);
        }

    }


    /**
     * @param $zipFolderPath : Folder in which the zip is to created
     * @param $zipFolderName : Zipped folder name
     * @param $filePath :   File path that will be zipped
     */
    private function createZip($zipFolderPath, $zipFolderName, $filePath)
    {
        $zip = new ZipArchive();
        if ($zip->open("$zipFolderPath/$zipFolderName.zip", ZipArchive::CREATE)) {
            $zip->addFile($filePath, $zipFolderName);
            $zip->close();
            chmod("$zipFolderPath/$zipFolderName.zip", 0777);
            @unlink($filePath); //delete the sql file
            echo 'Archive created!';
        } else {
            echo 'Failed!';
        }
    }

    public function createLog($errorType, $fileName, $dbName, $fileSize, $message)
    {
        openlog($dbName, LOG_PID | LOG_PERROR, LOG_LOCAL0);
        $accessTime = date("Y-m-d H:i:s");

        if ($errorType == 'info') {
            $logType = LOG_INFO;
        } else if ($errorType == 'error') {
            $logType = LOG_ERR;
        } else if ($errorType == 'warning') {
            $logType = LOG_WARNING;
        } else {
            $logType = LOG_CRIT;
        }

        if (!empty($fileSize)) {
            $fileSizeString = "File Size: $fileSize";
        } else {
            $fileSizeString = '';
        }

        syslog($logType, "File Name: $fileName" . ',' . " $message" . ',' . " $fileSizeString " . ',' . " Created Time: $accessTime");
        closelog();
    }

    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }


}

$dbBackupObj = new DbBackupScript();
$dbBackupObj->dumpDb();

?>
