<?php
require_once 'AbstractAppAction.php';

/**
 * this class is used to display a google map
 * TODO: google map is not for free! check if we can remove
 *
 *
 */
class MapController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/map_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/map_en.csv', 'en');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }

        /* Initialize action controller here */
    }

    /**
     * TODO: refactor and make configurable
     */
    public function indexAction()
    {
        $mapdaoImpl = new Dao_Map();

        $ciTypeList = "376";
        $mapArray   = $mapdaoImpl->getCiByTypeList($ciTypeList);

        $positionId     = 410;
        $rangeAttribute = 414;
        $standortName   = 401;

        $mapList = array();
        foreach ($mapArray as $key => $map) {
            $position = $mapdaoImpl->getValueForCi($map[Db_Ci::ID], $positionId);

            $breite = null;
            $laenge = null;

            if ($position && $position[Db_CiAttribute::VALUE_TEXT])
                list($breite, $laenge) = explode(':', $position[Db_CiAttribute::VALUE_TEXT]);

            $range = $mapdaoImpl->getValueForCi($map[Db_Ci::ID], $rangeAttribute);
            $range = $range[Db_CiAttribute::VALUE_TEXT];

            if (!$range)
                $range = 0;

            $tickets = $mapdaoImpl->getCiTickets($map[Db_Ci::ID]);

            if (!$tickets || count($tickets) < 1)
                $tickets = array();

            $name = $mapdaoImpl->getValueForCi($map[Db_Ci::ID], $standortName);
            $name = $name[Db_CiAttribute::VALUE_TEXT];

            if (!$name)
                $name = 'Sender';

            $mapList[$key]['x']        = $breite;
            $mapList[$key]['y']        = $laenge;
            $mapList[$key]['standort'] = $name;
            $mapList[$key]['range']    = $range;
            $mapList[$key]['tickets']  = $tickets;
            $mapList[$key]['citype']   = $map[Db_CiType::NAME];
            $mapList[$key]['ciid']     = $map[Db_Ci::ID];

            if (!$mapList[$key]['x'] || !$mapList[$key]['y']) {
                unset($mapList[$key]);
            }
        }

        $this->view->items = $mapList;
    }
}