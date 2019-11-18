<?php
require_once 'AbstractAppAction.php';

/**
 * This is a statistic class that uses google charts to display charts
 * TODO: currently unused
 *
 *
 *
 */
class StatisticsController extends AbstractAppAction
{

    public function init()
    {
        parent::init();
        parent::setTranslatorLocal();
        /* Initialize action controller here */
        try {
            $this->translator->addTranslation($this->languagePath . '/de/statistics_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/statistics_en.csv', 'en');
            parent::addUserTranslation('statistics');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function indexAction()
    {
        $this->logger->log('Statistics action has been invoked', Zend_Log::DEBUG);

        // TODO:

        // chart ci auf citypen (verteilung)

        $chart = new Util_Charts_Google();


        // TODO: select active ci'S
        $ciDaoImpl   = new Dao_Ci();
        $ciCount     = $ciDaoImpl->countActiveCi();
        $gesamtCount = $ciCount['cnt'];

        $ciTypeCount = $ciDaoImpl->countMaxFiveCiTypes();

        $data = array();
        $add  = 0;

        foreach ($ciTypeCount as $cnt) {
            $proz                               = $cnt['cnt'] / ($gesamtCount / 100);
            $data[$cnt[Db_CiType::DESCRIPTION]] = $proz;
            $add                                = $add + $cnt['cnt'];
        }


        $sonstige         = $gesamtCount - $add;
        $proz             = $sonstige / ($gesamtCount / 100);
        $data['Sonstige'] = $proz;

        // Set graph colors
        $color = array(
            '#99C754',
            '#54C7C5',
            '#999999',
        );

        /* # Chart 1 # */
        $chart->setChartAttrs(array(
            'type'  => 'pie',
            'title' => 'CI Verteilung',
            'data'  => $data,
            'size'  => array(400, 300),
            'color' => $color,
        ));

        $this->view->chart = $chart;
    }


}