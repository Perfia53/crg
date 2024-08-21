<?php
namespace App\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;


class RunMinuteCommand extends Command
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
        $this->setName('app:runMinute');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/runMinute' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

//        /*..obsah minutoveho bloku*/
//        $datum = getdate();
//        $rok = $datum["year"];
//        $mesic = $datum["mon"];
//        $den = $datum["mday"];
//        $hodina = $datum["hours"];
//        $minuta = $datum["minutes"];
//        $sekunda = $datum["seconds"];
//        $mesic = str_pad($mesic,2,'0',STR_PAD_LEFT);
//        $den = str_pad($den,2,'0',STR_PAD_LEFT);
//        $hodina = str_pad($hodina,2,'0',STR_PAD_LEFT);
//        $minuta = str_pad($minuta,2,'0',STR_PAD_LEFT);
//        $sekunda = str_pad($sekunda,2,'0',STR_PAD_LEFT);
//        $zapis = $rok.$mesic.$den.$hodina.$minuta.$sekunda;
//        fwrite($log,date("d.m.y H:i:s").CHR(13).CHR(10));
//
//        $backupData = dirname(__FILE__)."/../../backup/crg_backupData_" . $zapis . ".sql";
//        $backupLog = dirname(__FILE__)."/../../log/backup/crg_backupLog_" . $zapis . ".sql";
//        exec ('mysqldump --user=administrator --password=Alma+0053- --log-error=' . $backupLog .' --result-file=' . $backupData . ' crg_v1');


        
        /*odmazavani pruvodcu vytvorenych roboty*/
        $this->database->query('DELETE FROM usr_guide WHERE ip LIKE ? OR ip LIKE ? OR ip LIKE ?','46.4%','54.36%','66.249%');

        fwrite($log,'Run minute finished - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);

        //$this->database->query('INSERT INTO dat_log VALUES(1,"A")');
        //$output->writeLn('Test Command');




        return 0;
    }
}