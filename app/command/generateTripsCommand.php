<?php
namespace App\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;
use Nette\Utils\Datetime;
use Nette\Utils\Strings;


class GenerateTripsCommand extends Command
{
    /** @var Context Instance třídy pro práci s databází. */
    protected $database;

    /** @var \App\CoreModule\Model\TripManager @inject */
    public $tripManager;
    /** @var \App\CoreModule\Model\GuideManager @inject */
    public $guideManager;
    /** @var \App\CoreModule\Model\GpxManager @inject */
    public $gpxManager;

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
        $this->setName('app:generateTrips');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/generateTrips' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        //vymazu vse - musi se pak odstranit
//        $this->database->query('TRUNCATE adm_task_trip');
//        $this->database->query('TRUNCATE trp_trip_name');
//        $this->database->query('TRUNCATE trp_trip');
        
        /*generovani a update vyletu*/
        fwrite($log,'getStartedPoints'.CHR(13).CHR(10));
        $res = $this->tripManager->getStartedPoints();
        
        fwrite($log,'getStartedPoints - cyklus start'.CHR(13).CHR(10));
        foreach ($res as $r) {
            fwrite($log,$r['point_id'].CHR(13).CHR(10));

            //info o startovnim bodu
            fwrite($log,'getPoint'.CHR(13).CHR(10));
            $arrPoint = $this->guideManager->getPoint($r['point_id'],null,1);
            
            //seznam bodu v dosahu
            fwrite($log,'trip style 1 - start'.CHR(13).CHR(10));
//            $arrBounds = $this->tripManager->getBounds($arrPoint,5);
//            $resPoi1 = $this->guideManager->getPoints(null, null, [1,2,3], null, null, null, [$arrBounds[0], $arrBounds[1], $arrBounds[2], $arrBounds[3]],null,null,null,null,1);
//            $this->tripManager->generateTripsByWalk($r['point_id'], $resPoi1);
            fwrite($log,'trip style 1 - stop'.CHR(13).CHR(10));

            fwrite($log,'trip style 2 - start'.CHR(13).CHR(10));
            $arrBounds = $this->tripManager->getBounds($arrPoint,30);
            $resPoi2 = $this->guideManager->getPoints(null, null, [1,2,3], null, null, null, [$arrBounds[0], $arrBounds[1], $arrBounds[2], $arrBounds[3]],null,null,null,null,1);
            $this->tripManager->generateTripsByBike($r['point_id'], $resPoi2);
            fwrite($log,'trip style 2 - stop'.CHR(13).CHR(10));


            //vymaz ze startovnich bodu
            $this->database->query('DELETE FROM trp_started_points WHERE point_id = ?',$r['point_id']);
        }
        fwrite($log,'getStartedPoints - cyklus stop'.CHR(13).CHR(10));
        
        //vylety autem
        fwrite($log,'trip style 3 - start'.CHR(13).CHR(10));
        $resCity = $this->guideManager->getPoints([5002], null, null, null, null, null, null, null, null, null, null, 1);
        foreach ($resCity as $r) {
            fwrite($log,'trip style 3 - city id = '.$r['point_id'].CHR(13).CHR(10));
            $arrCity = $this->guideManager->getPoint($r['point_id'],null,1);
            $arrTrips = $this->tripManager->getTrips([1], [1,2], null, null, 1, [0,1,2], 5,0)->fetchAll();
            foreach ($arrTrips as $arrTrip) {
                $arrTripSelected = $this->tripManager->getTrip($arrTrip['trip_id']);
                $arrPointStart = $this->guideManager->getPoint($arrTripSelected['point_id1'],null,1);
                $iDistance = $this->tripManager->getDistancesBetweenPoints($arrCity['lat'], $arrPointStart['lat'], $arrCity['lon'], $arrPointStart['lon']);
                if ($iDistance < 80) {
                    $this->tripManager->generateTrips3($arrCity, $arrTripSelected, $iDistance);
                }
            }

        }
        fwrite($log,'trip style 3 - stop'.CHR(13).CHR(10));
        
        
        //nactu vygenerovane vylety
        fwrite($log,'getTrips'.CHR(13).CHR(10));
        $arrTrips = $this->tripManager->getTrips(null, [1,2], null, null, 1, [0], 5, 0)->fetchAll();
        fwrite($log,count($arrTrips).CHR(13).CHR(10));

        fwrite($log,'getTrips - cyklus'.CHR(13).CHR(10));
        foreach($arrTrips as $arrTrip) {
            fwrite($log,'trip_id ' . $arrTrip['trip_id'].CHR(13).CHR(10));

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

            switch($arrTrip['trip_style_id']) {
                case 1:
                    $sProfile = 'foot-walking';
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
            $arrRoute = $this->gpxManager->getRoute($sProfile, $arrCoorsBorder, $arrTrip['trip_structure_id']);

            if ($arrRoute == 22) {
                $arrRoute = $this->gpxManager->getRoute($sProfile, $arrCoorsBorder, 1);
            }
//            fwrite($log,'getRoute'.'-'.$arrCoorsBorder[0][1].'-'.$arrCoorsBorder[1][1].'-'.$arrCoorsBorder[2][1].'-'.$arrCoorsBorder[3][1].CHR(13).CHR(10));
//            dump($arrRoute);

            $arrRouteParams = $this->tripManager->getRouteParameters($arrRoute);

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
          
        //dohledani stopovers
        $arrTrips = $this->tripManager->getTrips(null, [1,2], null, null, 1, [1], 100, 0)->fetchAll();
        foreach ($arrTrips as $arrTrip) {
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
                    $dTop = $arrCoorTmp[0] + 0.009 * 10;
                    $dRight = $arrCoorTmp[1] + 0.0014 * 10;
                    $dBottom = $arrCoorTmp[0] - 0.009 * 10;
                    $dLeft = $arrCoorTmp[1] - 0.0014 * 10;

                    $resPoi = $this->guideManager->getPoints(null, null, [1,2,3], null, null, null, [$dTop, $dRight, $dBottom, $dLeft],null,null,null,null,1);
                    foreach ($resPoi as $rPoi) {
                        if (!in_array($rPoi['point_id'], $arrPointId)) {
                            if (($rPoi['point_id'] != $arrTrip['point_id1']) && ($rPoi['point_id'] != $arrTrip['point_id4'])) {
                                $arrStopover = $this->guideManager->getPoint($rPoi['point_id'],null,1);
                                if ($arrStopover['subject_type_id'] <> 1011) {
                                    $arrPointId[] = $arrStopover['point_id'];
                                    $arrPointTypeId[] =  $arrStopover['subject_type_id'];
                                }
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

            $this->database->query('UPDATE trp_trip SET', [
                'stopovers' => $sPointIds,
                'point_type_ids' => $sPointTypeIds,
                'date_upd' => new DateTime,
                'status_id' => 2,
                    ], 'WHERE trip_id = ?', $arrTrip['trip_id']);
        }
        
        //vytvoreni nazvu
        $arrTrips = $this->tripManager->getTrips(null, [4], null, null, 1, [2], 100, 0)->fetchAll();

        foreach ($arrTrips as $arrTrip) {
            //czech
            $sTitle = $this->tripManager->getTripTitle($arrTrip['trip_id'], 1);
            $sDescription = '';
            //$sDescription = $this->tripManager->getTripDescription($arrTrip['trip_id'], 1);
            $res = $this->database->query('SELECT 1'
                    . ' FROM trp_trip_name tn'
                    . ' WHERE tn.trip_id <> ?'
                    . ' AND tn.trip_url = ?' 
                    . ' AND tn.language_id = ?', 
                    $arrTrip['trip_id'], Strings::webalize($sTitle, null, false), 1);

            $sNewUrl = Strings::webalize($sTitle, null, false);
            if ($res->getRowCount() > 0) {
                $sNewUrl = Strings::webalize($sTitle, null, false) . '-' . $arrTrip['trip_id'];
            }

            $this->database->query('UPDATE trp_trip_name SET', [
                'trip_name' => $sTitle,
                'trip_url' => $sNewUrl,
                'trip_description' => $sDescription,
                    ], 'WHERE trip_id = ? AND language_id = ?', $arrTrip['trip_id'], 1);
            //english
            $sTitle = $this->tripManager->getTripTitle($arrTrip['trip_id'], 1);
            $sDescription = '';
            //$sDescription = $this->tripManager->getTripDescription($arrTrip['trip_id'], 1);
            $res = $this->database->query('SELECT 1'
                    . ' FROM trp_trip_name tn'
                    . ' WHERE tn.trip_id <> ?'
                    . ' AND tn.trip_url = ?' 
                    . ' AND tn.language_id = ?', 
                    $arrTrip['trip_id'], Strings::webalize($sTitle, null, false), 2);

            $sNewUrl = Strings::webalize($sTitle, null, false);
            if ($res->getRowCount() > 0) {
                $sNewUrl = Strings::webalize($sTitle, null, false) . '-' . $arrTrip['trip_id'];
            }

            $this->database->query('UPDATE trp_trip_name SET', [
                'trip_name' => $sTitle,
                'trip_url' => $sNewUrl,
                'trip_description' => $sDescription,
                    ], 'WHERE trip_id = ? AND language_id = ?', $arrTrip['trip_id'], 1);


            
        }
        


        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);

        //$this->database->query('INSERT INTO dat_log VALUES(1,"A")');
        //$output->writeLn('Test Command');




        return 0;
    }
}