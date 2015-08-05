<?php

require_once __DIR__ . '/dropbox/helper.php';
use \Dropbox as dbx;

class DbDumpTransfer
{

    /**
     * this will transfer the dbBackup zip to dropbox(LEAD-140)
     */
    public function transferZip()
    {

        $root_dir = dirname(__DIR__);
        $backUpFolderPath = $root_dir . '/api/dbBackUp';

        $host = gethostname();
        
        $db_name = 'mydb';

        /*dropbox settings starts here*/
        $dropbox_config = array(
            'key' => 'xxxxxxxx',
            'secret' => 'xxxxxxxxx'
        );

        $appInfo = dbx\AppInfo::loadFromJson($dropbox_config);
        $webAuth = new dbx\WebAuthNoRedirect($appInfo, "PHP-Example/1.0");
        $accessToken = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
        $dbxClient = new dbx\Client($accessToken, "PHP-Example/1.0");
        /*dropbox settings ends here*/


        $current_date = date('Y-m-d');
        $backUpFileName = $current_date . '_' . $db_name . '.sql.zip';

        $fullBackUpPath = $backUpFolderPath . '/' . $backUpFileName;

        if (file_exists($backUpFolderPath)) {
            $files = scandir($backUpFolderPath); //retrieve all the files

            foreach ($files as $file) {
                if ($file == $backUpFileName) { // file matches with the db back up file created today
                    /* transfer the file to dropbox*/
                    $f = fopen($fullBackUpPath, "rb");
                    $dbxClient->uploadFileChunked("/$backUpFileName", dbx\WriteMode::add(), $f);
                    fclose($f);
                    echo 'Upload Completed';
                }
            }
        }

    }

}

$dbTransferObj = new DbDumpTransfer();
$dbTransferObj->transferZip();


?>
