<?php
namespace App\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;
use Nette\Utils\Datetime;
use Nette\Utils\Strings;


class GenTrips1Command extends Command
{
    /** @var Context Instance třídy pro práci s databází. */
    protected $database;

    /** @var \App\CoreModule\Model\TripManager @inject */
    public $tripManager;
    /** @var \App\CoreModule\Model\GuideManager @inject */
    public $guideManager;
    /** @var \App\CoreModule\Model\MasterDataManager @inject */
    public $masterDataManager;

    
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
        $this->setName('app:genTrips1');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/genTrips1_' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

//        //vymazu vse - musi se pak odstranit
//        $this->database->query('TRUNCATE adm_task_trip');
//        $this->database->query('TRUNCATE trp_trip_name');
//        $this->database->query('TRUNCATE trp_trip');

        $resStartPointTypes = $this->masterDataManager->getConfig('trip.startPointTypes')->fetchAll();
        $arrStartPointTypes = explode(",",$resStartPointTypes[0]['hodnota']);
        $resFinalPointTypes = $this->masterDataManager->getConfig('trip.finalPointTypes')->fetchAll();
        $arrFinalPointTypes = explode(",",$resFinalPointTypes[0]['hodnota']);
        
        fwrite($log,'checkPoint 1'.CHR(13).CHR(10));
        //nactu seznam vsech POI
        $res = $this->guideManager->getPoints($arrFinalPointTypes, null, null, null, null, null, null, null, null, null, null, 1);
        foreach ($arrFinalPointTypes as $iFinalPointTypeId) {
            fwrite($log,'checkPoint 1 - final point types ' . $iFinalPointTypeId.CHR(13).CHR(10));
        }
        
        fwrite($log,'checkPoint 2'.CHR(13).CHR(10));
        foreach ($res as $r) {
            fwrite($log,'checkPoint 2 - point id = '.$r['point_id'].CHR(13).CHR(10));
            dump($r['name'] . '('.$r['point_id'].')');
            //nactu souradnice
            fwrite($log,'checkPoint 3'.CHR(13).CHR(10));
            $arrPoint = $this->guideManager->getPoint($r['point_id'],null,1);
            
            fwrite($log,'checkPoint 4 - start'.CHR(13).CHR(10));
            //nacteni vesnic pro pesi - 1
            $arrBounds = $this->tripManager->getBounds($arrPoint,10);
            //mesto 5002, vesnice 5003, parking u místa 7007, ubytko group 1
            $resPoi1 = $this->guideManager->getPoints($arrStartPointTypes, null, null, null, null, null, [$arrBounds[0], $arrBounds[1], $arrBounds[2], $arrBounds[3]],null,null,null,null,1);
            //vytvorim zaznam o vyletu vcetne zaznamu pro jmeno
            $this->tripManager->generateTrips1($arrPoint, $resPoi1);
            fwrite($log,'checkPoint 4 - stop'.CHR(13).CHR(10));

//            fwrite($log,'checkPoint 5 - start'.CHR(13).CHR(10));
//            $arrBounds = $this->tripManager->getBounds($arrPoint,30);
//            $resPoi2 = $this->guideManager->getPoints(null, null, [1,2,3], null, null, null, [$arrBounds[0], $arrBounds[1], $arrBounds[2], $arrBounds[3]],null,null,null,null,1);
//            $this->tripManager->generateTrips2($r['point_id'], $resPoi2);
//            fwrite($log,'checkPoint 5 - stop'.CHR(13).CHR(10));

        }
        fwrite($log,'checkPoint 7'.CHR(13).CHR(10));

        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);

        return 0;
    }
}