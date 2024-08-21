<?php
namespace App\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;
use Nette\Utils\Datetime;
use Nette\Utils\Strings;


class GenTrips2Command extends Command
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
        $this->setName('app:genTrips2');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/genTrips2_' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        $resFinalPointTypes = $this->masterDataManager->getConfig('trip.finalPointTypes')->fetchAll();
        $arrFinalPointTypes = explode(",",$resFinalPointTypes[0]['hodnota']);

        
        //nactu vygenerovane vylety
        //getTrips($arrTripTypeId = null, $arrTripStyleId = null, $arrPointTypeId = null, $arrDistanceId = null, $iLanguageId = null, $arrStatusId = null, $iPageLength = null, $iPageOffset = null, $arrPointIds = null, $sSort = null) {
        $arrTrips = $this->tripManager->getTrips(null, [1], $arrFinalPointTypes, null, 1, [0], 10, 0)->fetchAll();

        fwrite($log,count($arrTrips).CHR(13).CHR(10));

        fwrite($log,'checkPoint 1'.CHR(13).CHR(10));
        foreach($arrTrips as $arrTrip) {
            fwrite($log,'checkPoint 2 - trip_id = '.$arrTrip['trip_id'].CHR(13).CHR(10));

            //urcim plochu, kudy vylet vest
            $arrCoorsBorder = array();
            $arrPoint1 = $this->guideManager->getPoint($arrTrip['point_id1'],null,1);
            $arrPoint4 = $this->guideManager->getPoint($arrTrip['point_id4'],null,1);

            switch ($arrTrip['trip_structure_id']) {
                case 1: //tam a zpet
                case 2: //tam a zpet
                    $arrCoorsBorder[] = array($arrPoint1['lat'],$arrPoint1['lon']);
                    $arrCoorsBorder[] = array($arrPoint4['lat'],$arrPoint4['lon']);
                    break;
//                case 2: //okruh
//                    $arrPoint1['lat'] > $arrPoint3['lat'] ? $dLat = $arrPoint1['lat'] - $arrPoint3['lat'] : $dLat = $arrPoint3['lat'] - $arrPoint1['lat'];
//                    $arrPoint1['lon'] > $arrPoint3['lon'] ? $dLon = $arrPoint1['lon'] - $arrPoint3['lon'] : $dLon = $arrPoint3['lon'] - $arrPoint1['lon'];
//
//                    $dLatAdd = $dLat / 2;
//                    $dLonAdd = $dLon / 2;
//                    
//                    if ($dLon < 0.004) {
//                        $arrPoint1['lon'] > $arrPoint3['lon'] ? $dLon2 = $arrPoint1['lon'] + (5 * $dLonAdd) : $dLon2 = $arrPoint3['lon'] + (5 * $dLonAdd);
//                        $arrPoint1['lon'] > $arrPoint3['lon'] ? $dLon4 = $arrPoint3['lon'] - (5 * $dLonAdd) : $dLon4 = $arrPoint1['lon'] - (5 * $dLonAdd);
//                    } else {
//                        $arrPoint1['lon'] > $arrPoint3['lon'] ? $dLon2 = $arrPoint3['lon'] + $dLonAdd : $dLon2 = $arrPoint1['lon'] + $dLonAdd;
//                        $arrPoint1['lon'] > $arrPoint3['lon'] ? $dLon4 = $arrPoint1['lon'] + $dLonAdd : $dLon4 = $arrPoint3['lon'] - $dLonAdd;
//                    }
//
//                    if ($dLat < 0.004) {
//                        $arrPoint1['lat'] > $arrPoint3['lat'] ? $dLat2 = $arrPoint1['lat'] + (5 * $dLatAdd) : $dLat2 = $arrPoint3['lat'] + (5 * $dLatAdd);
//                        $arrPoint1['lat'] > $arrPoint3['lat'] ? $dLat4 = $arrPoint3['lat'] - (5 * $dLatAdd) : $dLat4 = $arrPoint1['lat'] - (5 * $dLatAdd);
//                    } else {
//                        $arrPoint1['lat'] > $arrPoint3['lat'] ? $dLat2 = $arrPoint3['lat'] + $dLatAdd : $dLat2 = $arrPoint1['lat'] + $dLatAdd;
//                        $arrPoint1['lat'] > $arrPoint3['lat'] ? $dLat4 = $arrPoint3['lat'] + $dLatAdd : $dLat4 = $arrPoint1['lat'] - $dLatAdd;
//                    }
//                    $arrCoorsBorder[] = array($arrPoint1['lat'],$arrPoint1['lon']);
//                    $arrCoorsBorder[] = array($dLat2,$dLon2);
//                    $arrCoorsBorder[] = array($arrPoint3['lat'],$arrPoint3['lon']);
//                    $arrCoorsBorder[] = array($dLat4,$dLon4);
//                    $arrCoorsBorder[] = array($arrPoint1['lat'],$arrPoint1['lon']);
//
//                    break;
            }
            fwrite($log,'checkPoint 3'.CHR(13).CHR(10));

            switch($arrTrip['trip_style_id']) {
                case 1:
                    $arrPoint4['subject_type_id'] == 3010 ? $sProfile = 'foot-hiking' :  $sProfile = 'foot-walking';
                    break;
                case 2:
                    $sProfile = 'cycling-regular';
                    break;
                case 3:
                    $sProfile = 'driving-car';
                    break;
            }

//            fwrite($log,'getRoute'.'-'.$arrCoorsBorder[0][1].'-'.$arrCoorsBorder[1][1].'-'.$arrCoorsBorder[2][1].'-'.$arrCoorsBorder[3][1].CHR(13).CHR(10));
            dump('getRoute trip_id='.$arrTrip['trip_id'].CHR(13).CHR(10));
//            dump('getRoute'.'-[['.$arrCoorsBorder[0][1].','.$arrCoorsBorder[0][0].'],['.$arrCoorsBorder[1][1].','.$arrCoorsBorder[1][0].']]'.CHR(13).CHR(10));
            fwrite($log,'checkPoint 4'.CHR(13).CHR(10));
            $arrRoute = $this->gpxManager->getRoute($sProfile, $arrCoorsBorder, $arrTrip['trip_structure_id']);
            dump($arrRoute);

            if ($arrRoute != 22) {
                fwrite($log,'checkPoint 4'.CHR(13).CHR(10));

    //            fwrite($log,'getRoute'.'-'.$arrCoorsBorder[0][1].'-'.$arrCoorsBorder[1][1].'-'.$arrCoorsBorder[2][1].'-'.$arrCoorsBorder[3][1].CHR(13).CHR(10));

                $arrRouteParams = $this->tripManager->getRouteParameters($arrRoute);
                fwrite($log,'checkPoint 5'.CHR(13).CHR(10));

                if ($arrRouteParams['distance'] > 0){
                    $arrCoorsBorderTmp = array();
                    $sNote = '';
                    foreach($arrCoorsBorder as $arrCoorBorder) {
                        $arrCoorsBorderTmp[] = $arrCoorBorder[0] . ',' . $arrCoorBorder[1];
                        $sNote .= '['.$arrCoorBorder[1] . ',' . $arrCoorBorder[0].'],';
                    }
                    $sCoorsBorder = implode(";",$arrCoorsBorderTmp);

                    $this->database->query('UPDATE trp_trip SET', [
                        'trip_structure_id' => $arrRouteParams['structure_id'],
                        'coors_border' => $sCoorsBorder,
                        'latitude' => $arrPoint4['lat'],
                        'longitude' => $arrPoint4['lon'],
                        'coors' => $arrRouteParams['coors'],
                        'altitude' => $arrRouteParams['altitude'],
                        'profile_id' => $arrRouteParams['profile_id'],
                        'hills' => $arrRouteParams['hills'],
                        'ascent' => round($arrRouteParams['ascent'],0),
                        'descent' => round($arrRouteParams['descent'],0),
                        'distance' => $arrRouteParams['distance'],
                        'distance_id' => $arrRouteParams['distance_id'],
                        'duration' => $arrRouteParams['duration'],
                        'duration_id' => $arrRouteParams['duration_id'],
                        'date_upd' => new DateTime,
                        'status_id' => 1,
                        'note' => $sNote
                            ], 'WHERE trip_id = ?', $arrTrip['trip_id']);

                }
            }
        }

        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);

        return 0;
    }
}