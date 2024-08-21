<?php
namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;

class RunBackupCommand extends Command
{
    /** @var Context Instance třídy pro práci s databází. */
    protected $database;

    /**
     * Konstruktor s injektovanou třídou pro práci s databází.
     * @param Context $database automaticky injektovaná třída pro práci s databází
     */
    public function __construct(Context $database) {
         parent::__construct();
        $this->database = $database;
    }

    protected function configure()
    {
        $this->setName('app:runBackup');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(12000);
        
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/runBackup' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        $datum = getdate();
        $rok = $datum["year"];
        $mesic = $datum["mon"];
        $den = $datum["mday"];
        $hodina = $datum["hours"];
        $minuta = $datum["minutes"];
        $sekunda = $datum["seconds"];
        $mesic = str_pad($mesic,2,'0',STR_PAD_LEFT);
        $den = str_pad($den,2,'0',STR_PAD_LEFT);
        $hodina = str_pad($hodina,2,'0',STR_PAD_LEFT);
        $minuta = str_pad($minuta,2,'0',STR_PAD_LEFT);
        $sekunda = str_pad($sekunda,2,'0',STR_PAD_LEFT);
        $zapis = $rok.$mesic.$den.$hodina.$minuta.$sekunda;
        fwrite($log,date("d.m.y H:i:s").CHR(13).CHR(10));

//        //tabulky
//        $backupTables = dirname(__FILE__)."/../../backup/01_crg_backupTables_" . $zapis . ".sql";
//        exec ('mysqldump --user=administrator --password=alma0053 --skip-triggers --no-data crg > '.$backupTables);
//        fwrite($log,'Backup tables done.'.CHR(13).CHR(10));
//
//        //triggery
//        $backupTriggers = fopen('nette.safe://'.__DIR__ . '/../../backup/05_crg_backupTriggers_' . $zapis . '.sql', 'a+'); // před jméno souboru dáme nette.safe://
//        $resTri = $this->database->query('select * from information_schema.triggers where trigger_schema = "crg"');
//        foreach($resTri as $rTri) {
//            fwrite($backupTriggers,'DELIMITER //' . CHR(13).CHR(10));
//            fwrite($backupTriggers,
//            'CREATE TRIGGER ' . $rTri['TRIGGER_NAME'] . ' ' . $rTri['ACTION_TIMING'] . ' ' . $rTri['EVENT_MANIPULATION'] . ' ON ' . $rTri['EVENT_OBJECT_TABLE'] . ' FOR EACH ROW ' . $rTri['ACTION_STATEMENT'] . ';'   
//            .CHR(13).CHR(10));
//            fwrite($backupTriggers,'//' . CHR(13).CHR(10) . CHR(13).CHR(10));
//        }
//        fwrite($log,'Backup triggers done.'.CHR(13).CHR(10));
//
//        //funkce
//        $backupFunctions = fopen('nette.safe://'.__DIR__ . '/../../backup/03_crg_backupFunctions_' . $zapis . '.sql', 'a+'); // před jméno souboru dáme nette.safe://
//        $resFce = $this->database->query('select * from information_schema.routines where routine_schema = "crg" and routine_type = "FUNCTION"');
//        foreach($resFce as $rFce) {
//            $sParRet = '';
//            $resPar = $this->database->query('select * from information_schema.parameters where specific_schema = "crg" and specific_name = "' . $rFce['ROUTINE_NAME'] . '" and ordinal_position = 0');
//            foreach($resPar as $rPar) {
//                $sParRet .= 'RETURNS ' . $rPar['DATA_TYPE'] . ' (' . $rPar['CHARACTER_MAXIMUM_LENGTH'] . ') CHARSET ' . $rPar['CHARACTER_SET_NAME'] . ' DETERMINISTIC';
//            }
//
//            $sPar = '';
//            $resPar = $this->database->query('select * from information_schema.parameters where specific_schema = "crg" and specific_name = "' . $rFce['ROUTINE_NAME'] . '" and ordinal_position > 0 order by ordinal_position');
//            foreach($resPar as $rPar) {
//                            $sPar .= ',' . $rPar['PARAMETER_NAME'] . ' ' . $rPar['DATA_TYPE'];
//            }
//            $sPar = substr($sPar,1,1000);
//            fwrite($backupFunctions,'DELIMITER //' . CHR(13).CHR(10));
//            fwrite($backupFunctions,
//            'CREATE ' . $rFce['ROUTINE_TYPE'] . ' ' . $rFce['ROUTINE_NAME'] . ' (' . $sPar . ') ' . $sParRet . ' ' . $rFce['ROUTINE_DEFINITION'] . ';'   
//            .CHR(13).CHR(10));
//            fwrite($backupFunctions,'//' . CHR(13).CHR(10) . CHR(13).CHR(10));
//        }
//        fwrite($log,'Backup functions done.'.CHR(13).CHR(10));
//
//        //procedury
//        $backupProcedures = fopen('nette.safe://'.__DIR__ . '/../../backup/04_crg_backupProcedures_' . $zapis . '.sql', 'a+'); // před jméno souboru dáme nette.safe://
//        $resPrc = $this->database->query('select * from information_schema.routines where routine_schema = "crg" and routine_type = "PROCEDURE"');
//        foreach($resPrc as $rPrc) {
//            $sPar = '';
//            $resPar = $this->database->query('select * from information_schema.parameters where specific_schema = "crg" and specific_name = "' . $rFce['ROUTINE_NAME'] . '" and ordinal_position > 0 order by ordinal_position');
//            foreach($resPar as $rPar) {
//                if ($rPar['DATA_TYPE'] == 'varchar') {
//                    $sPar .= ',IN ' . $rPar['PARAMETER_NAME'] . ' ' . $rPar['DATA_TYPE'] . '(' . $rPar['CHARACTER_MAXIMUM_LENGTH'] . ')';
//                } else {
//                    $sPar .= ',IN ' . $rPar['PARAMETER_NAME'] . ' ' . $rPar['DATA_TYPE'];
//                }
//            }
//            $sPar = substr($sPar,1,1000);
//            fwrite($backupProcedures,'DELIMITER //' . CHR(13).CHR(10));
//            fwrite($backupProcedures,
//            'CREATE ' . $rPrc['ROUTINE_TYPE'] . ' ' . $rPrc['ROUTINE_NAME'] . ' (' . $sPar . ') ' . $rPrc['ROUTINE_DEFINITION']   
//            .CHR(13).CHR(10));
//            fwrite($backupProcedures,'//' . CHR(13).CHR(10) . CHR(13).CHR(10));
//        }
//        fwrite($log,'Backup procedures done.'.CHR(13).CHR(10));
//

        //data
        $backupData = dirname(__FILE__)."/../../backup/crg_backupData_" . $zapis . ".sql";
        $backupLog = dirname(__FILE__)."/../../log/backup/crg_backupLog_" . $zapis . ".sql";
        exec ('mysqldump --user=administrator --password=Alma+0053- --log-error=' . $backupLog .' --result-file=' . $backupData . ' crg_v1');

        fwrite($log,'Backup data done.'.CHR(13).CHR(10));
        
        fwrite($log,'Run backup finished - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);

        //$this->database->query('INSERT INTO dat_log VALUES(1,"A")');
        //$output->writeLn('Test Command');




        return 0;
    }
}


