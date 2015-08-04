<?php

namespace Mrc\FrontBundle\Controller;

use Morocron\Parser\CronTabParser;
use Morocron\Processor\DistributionCronTabProcessor;
use Morocron\Processor\OptimizeCronTabProcessor;
use Morocron\Processor\SortCronTabProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class MainController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $source = sprintf('%s/Resources/fixtures/crontab', $this->get('kernel')->getRootDir());

        $cronTabParser = new CronTabParser();
        $cronTabDefinition = $cronTabParser->computeData($source);

        // actual
        $distributionCronTabProcessor = new DistributionCronTabProcessor();
        $distributionCronTabProcessor->compute($cronTabDefinition);
        $actualDistribution = $cronTabDefinition->getDistribution();
        $actualDistributionSliced = array_slice(json_decode($actualDistribution, true), 0, 61);

        // optimized
        $sortCronTabProcessor = new SortCronTabProcessor();
        $actualCronTabDefinition = $sortCronTabProcessor->computeDataAndSort($source, SortCronTabProcessor::FREQUENCY_STRATEGY);
        $optimizeCronTabProcessor = new OptimizeCronTabProcessor();
        $newCronTabDefinition = $optimizeCronTabProcessor->optimize($actualCronTabDefinition);
        $distributionCronTabProcessor->compute($newCronTabDefinition);
        $optimizedDistribution = $newCronTabDefinition->getDistribution();
        $optimizedDistributionSliced = array_slice(json_decode($optimizedDistribution, true), 0, 61);

        $distribution = $this->computeComparableDistribution($actualDistributionSliced, $optimizedDistributionSliced);

        return $this->render('MrcFrontBundle:Main:index.html.twig', array(
            'data'          => array_merge($cronTabDefinition->getPeriodicCronDefinitions(), $cronTabDefinition->getNonPeriodicCronDefinitions()),
            'distribution'  => $distribution,
            'optimizedData' => array_merge($newCronTabDefinition->getPeriodicCronDefinitions(), $newCronTabDefinition->getNonPeriodicCronDefinitions()),
        ));
    }

    /**
     * @param $actualDistribution
     * @param $optimizedDistribution
     * @return string
     */
    private function computeComparableDistribution($actualDistribution, $optimizedDistribution)
    {
        $distribution = array();
        foreach ($actualDistribution as $index => $hitsInfo) {
            $distribution[$index]['time'] = $hitsInfo['time'];
            $distribution[$index]['actualHits'] = $hitsInfo['hits'];
            $distribution[$index]['optimizedHits'] = $optimizedDistribution[$index]['hits'];
        }

        return json_encode($distribution);
    }
}
