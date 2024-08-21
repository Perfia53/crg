<?php
namespace App\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Nette\Database\Context;
use Nette\Utils\SafeStream;

class RunMonthCommand extends Command
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
        $this->setName('app:runMonth');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sToday = date("ymd");
        $log = fopen('nette.safe://'.__DIR__ . '/../../log/cron/runMonth' . $sToday . '.log', 'a+'); // před jméno souboru dáme nette.safe://
        fwrite($log,CHR(13).CHR(10).'Start script - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        /*..obsah mesicniho bloku*/

        fwrite($log,'Run month finished - '.date("d.m.y H:i:s").CHR(13).CHR(10));

        fwrite($log,'Stop script - '.date("d.m.y H:i:s").CHR(13).CHR(10));
        fclose($log);
        return 0;
    }
}