<?php
namespace App\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;
use Nette\Utils\Datetime;
use Nette\Utils\Strings;


class GenTrips3Command extends Command
{
    /** @var Context Instance třídy pro práci s databází. */
    protected $database;

    /** @var \App\CoreModule\Model\TripManager @inject */
    public $tripManager;
    /** @var \App\CoreModule\Model\GuideManager @inject */
    public $guideManager;
    /** @var \App\CoreModule\Model\GpxManager @inject */
    public $gpxManager;
    /** @var \App\CoreModule\Model\MasterDataManager @inject */
    public $masterDataManager;

    private $iPointId;
    
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
        $this->setName('app:genTrips3');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/genTrips3_' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        $resFinalPointTypes = $this->masterDataManager->getConfig('trip.finalPointTypes')->fetchAll();
        $arrFinalPointTypes = explode(",",$resFinalPointTypes[0]['hodnota']);

        
        //vytvoreni nazvu
        //$arrTripTypeId, $arrTripStyleId, $arrPointTypeId, $arrDistanceId, $iLanguageId, $arrStatusId, $iPageLength, $iPageOffset, $arrPointIds
        $arrTrips = $this->tripManager->getTrips(null, [1], $arrFinalPointTypes, null, 1, [1,2,3], 100, 0, null)->fetchAll();
        fwrite($log,'checkPoint 1'.CHR(13).CHR(10));

        foreach ($arrTrips as $arrTrip) {
            fwrite($log,'checkPoint 2 - trip_id = '.$arrTrip['trip_id'].CHR(13).CHR(10));
            //czech
            $sTripName = $this->getTripTitle($arrTrip['trip_id'], 1);
            fwrite($log,'checkPoint 2 - name_cz = '.$sTripName.CHR(13).CHR(10));
            $this->tripManager->tripNameUpd($arrTrip['trip_id'], $sTripName,1);
            //english
            $sTripName = $this->getTripTitle($arrTrip['trip_id'], 2);
            fwrite($log,'checkPoint 2 - name_en = '.$sTripName.CHR(13).CHR(10));
            $this->tripManager->tripNameUpd($arrTrip['trip_id'], $sTripName,2);

            $this->tripManager->tripStatusUpd($arrTrip['trip_id'], 2);
        }            
        
        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);

        return 0;
    }

    public function getTripTitle($iTripId, $iLanguageId) {
        $arrTrip = $this->tripManager->getTrip($iTripId);
        $arrTripStyles = $this->masterDataManager->getMd(29,[$arrTrip['trip_style_id']], [$iLanguageId])->fetchAll();
        $arrPointFrom = $this->guideManager->getPoint($arrTrip['point_id1'], null, $iLanguageId);
        $arrPointTo = $this->guideManager->getPoint($arrTrip['point_id4'], null, $iLanguageId);
        $arrSubjectTypesFrom = $this->masterDataManager->getSubjectTypes([$arrPointFrom['subject_type_id']],null,null,null,$iLanguageId)->fetchAll();
        $arrSubjectTypesTo = $this->masterDataManager->getSubjectTypes([$arrPointTo['subject_type_id']],null,null,null,$iLanguageId)->fetchAll();

//        $arrNames = $this->openAiManager->getTripNames(
//                $arrTripStyles[0]['dat_name'], 
//                $arrSubjectTypesFrom[0]['prefix_from'], 
//                $arrSubjectTypesFrom[0]['subject_type_from'],
//                $arrPointFrom['name'],
//                $arrSubjectTypesTo[0]['prefix_to'], 
//                $arrSubjectTypesTo[0]['subject_type_to'],
//                $arrPointTo['name'],
//                $iLanguageId
//                );
//        
//        $iRandom = rand(0,2);
//        $sTitle = $arrNames[$iRandom];
        
//        if ($arrPointFrom['name_from'] && $arrPointTo['name_to']) {
//            $sTitle = $iTripId . ' ' . $arrTripStyles[0]['dat_name'] . ' ' . $arrPointFrom['prefix_from'] . ' ' . $arrPointFrom['name_from'] . ' ' . $arrPointTo['prefix_to'] . ' ' . $arrPointTo['name_to'];
//        } else {
//            $sTitle = $arrTripStyles[0]['dat_name'] . ' ' . $arrSubjectTypesFrom[0]['prefix_from'] . ' ' . $arrSubjectTypesFrom[0]['subject_type_from'] . ' ' . $arrPointFrom['name'] . ' ' . $arrSubjectTypesTo[0]['prefix_to'] . ' ' . $arrSubjectTypesTo[0]['subject_type_to'] . ' ' . $arrPointTo['name'];
//        }
        if ($iLanguageId == 1) {
            $sTitle = $arrTripStyles[0]['dat_name'] . ' č.' . $iTripId . ' - ' . $arrPointFrom['name'] . ' - ' . $arrPointTo['name'];
        } else {
            $sTitle = $arrTripStyles[0]['dat_name'] . ' no. ' . $iTripId . ' - ' . $arrPointFrom['name'] . ' - ' . $arrPointTo['name'];
           }
            

        return $sTitle;
    }
    
    
    
}