<?php
namespace App\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;
use Nette\Utils\Datetime;
use Nette\Utils\Strings;


class GenTrips4Command extends Command
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
        $this->setName('app:genTrips4');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/genTrips4_' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        $resFinalPointTypes = $this->masterDataManager->getConfig('trip.finalPointTypes')->fetchAll();
        $arrFinalPointTypes = explode(",",$resFinalPointTypes[0]['hodnota']);
        $resStopoverRange = $this->masterDataManager->getConfig('trip.stopoverRange.walk')->fetchAll();
        $iStopoverRange = $resStopoverRange[0]['hodnota'];

        //getTrips($arrTripTypeId = null, $arrTripStyleId = null, $arrPointTypeId = null, $arrDistanceId = null, $iLanguageId = null, $arrStatusId = null, $iPageLength = null, $iPageOffset = null, $arrPointIds = null, $sSort = null) {
        $arrTrips = $this->tripManager->getTrips(null, [1,2], $arrFinalPointTypes, null, 1, [2,3], 100, 0)->fetchAll();
        fwrite($log,'checkPoint 1'.CHR(13).CHR(10));
        foreach ($arrTrips as $arrTrip) {
            fwrite($log,'checkPoint 2 - trip_id = '.$arrTrip['trip_id'].CHR(13).CHR(10));
            $arrPointId = array();
            $arrPointTypeId = array();

            $arrPointStart = $this->guideManager->getPoint($arrTrip['point_id1'],null,1);
            $arrPointId[] = $arrPointStart['point_id'];
            $arrPointTypeId[] =  $arrPointStart['subject_type_id'];


            $arrCoors = explode(';', $arrTrip['coors']);
            $i = 0;
            $iCoorCount = round(count($arrCoors) / 10);
            foreach($arrCoors as $arrCoor) {
                if ($i == $iCoorCount) {
                    $arrCoorTmp = explode(',', $arrCoor);
                    $dTop = $arrCoorTmp[0] + 0.009 * $iStopoverRange;
                    $dRight = $arrCoorTmp[1] + 0.0014 * $iStopoverRange;
                    $dBottom = $arrCoorTmp[0] - 0.009 * $iStopoverRange;
                    $dLeft = $arrCoorTmp[1] - 0.0014 * $iStopoverRange;

                    $resPoi = $this->guideManager->getPoints(null, null, [2,3], null, null, null, [$dTop, $dRight, $dBottom, $dLeft],null,null,null,null,1);
                    foreach ($resPoi as $rPoi) {
                        if (!in_array($rPoi['point_id'], $arrPointId)) {
                            if (($rPoi['point_id'] != $arrTrip['point_id1']) && ($rPoi['point_id'] != $arrTrip['point_id4'])) {
                                $arrPointId[] = $rPoi['point_id'];
                                $arrPointTypeId[] =  $rPoi['subject_type_id'];
                            }
                        }
                    }
                    $i = 0;
                }
                $i++;

            }
            $arrPointEnd = $this->guideManager->getPoint($arrTrip['point_id4'],null,1);
            $arrPointId[] = $arrPointEnd['point_id'];
            $arrPointTypeId[] =  $arrPointEnd['subject_type_id'];
            $sPointIds = implode(',',$arrPointId);
            $sPointTypeIds = implode(',',$arrPointTypeId);

            fwrite($log,'checkPoint 3 - trip_id = '.$arrTrip['trip_id'].CHR(13).CHR(10));

            $this->database->query('UPDATE trp_trip SET', [
                'stopovers' => $sPointIds,
                'point_type_ids' => $sPointTypeIds,
                'date_upd' => new DateTime,
                'status_id' => 3,
                    ], 'WHERE trip_id = ?', $arrTrip['trip_id']);
        }

        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);

        return 0;
    }
}