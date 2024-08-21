<?php
namespace App\Console;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;
use Nette\Utils\Datetime;
use Nette\Utils\Strings;


class GenTripsXCommand extends Command
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
        $this->setName('app:genTrips2');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/genTrips2_' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        
        fwrite($log,'checkPoint 1'.CHR(13).CHR(10));
        //startovni pozice
        //vesnice 5003
        //zatim mam mesta, vesnice nejsou ready
        $resCity = $this->guideManager->getPoints([5002], null, null, null, null, null, null, null, null, null, null, 1);
        foreach ($resCity as $r) {
            fwrite($log,'checkPoint 2 - city id = '.$r['point_id'].CHR(13).CHR(10));
            $arrCity = $this->guideManager->getPoint($r['point_id'],null,1);
            //$arrTripTypeId, $arrTripStyleId, $arrPointTypeId, $arrDistanceId, $iLanguageId, $arrStatusId, $iPageLength, $iPageOffset, $arrPointIds
            $arrTrips = $this->tripManager->getTrips([1], [1,2], null, null, 1, [3], 5,0)->fetchAll();
            foreach ($arrTrips as $arrTrip) {
                $arrTripSelected = $this->tripManager->getTrip($arrTrip['trip_id']);
                $arrPointStart = $this->guideManager->getPoint($arrTripSelected['point_id1'],null,1);
                $iDistance = $this->tripManager->getDistancesBetweenPoints($arrCity['lat'], $arrPointStart['lat'], $arrCity['lon'], $arrPointStart['lon']);
                if ($iDistance < 80) {
                    $this->tripManager->generateTrips3($arrCity, $arrTripSelected, $iDistance);
                }
            }
        }
        fwrite($log,'checkPoint 3'.CHR(13).CHR(10));
        

        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);

        return 0;
    }
}