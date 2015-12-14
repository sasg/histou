<?php

namespace tests\grafana;

class GraphpanelTest extends \MyPHPUnitFrameworkTestCase
{
    public function init()
    {
        $_GET['host'] = 'host';
        \histou\Basic::parsArgs();
        define('INFLUX_DB', 'nagflux');
        define('INFLUX_FIELDSEPERATOR', '&');
    }

    public function testCreateGraphPanel()
    {
        $this->init();
        $gpanel = new \histou\grafana\GraphPanel('gpanel');
        $this->assertSame(2, $gpanel->toArray()['linewidth']);

        $gpanel->setTooltip(array(true));
        $this->assertSame(array(true), $gpanel->toArray()['tooltip']);

        $this->assertSame(0, sizeof($gpanel->toArray()['targets']));
        $gpanel->addTargetSimple("target1", "alias1", array('tag1', 'tag2'));
        $this->assertSame(1, sizeof($gpanel->toArray()['targets']));
        $this->assertSame('alias1', $gpanel->toArray()['targets'][0]['alias']);
        $this->assertSame('nagflux', $gpanel->toArray()['targets'][0]['datasource']);
        $this->assertSame(array('tag1', 'tag2'), $gpanel->toArray()['targets'][0]['tags']);

        $gpanel->addTargetSimple("target2");
        $this->assertSame(2, sizeof($gpanel->toArray()['targets']));
        $this->assertSame('', $gpanel->toArray()['targets'][1]['alias']);
        $this->assertSame(array(), $gpanel->toArray()['targets'][1]['tags']);

        $gpanel->addAliasColor('alias1', '#123');
        $this->assertSame(1, sizeof($gpanel->toArray()['aliasColors']));
        $this->assertSame('#123', $gpanel->toArray()['aliasColors']['alias1']);

        $gpanel->setleftYAxisLabel('ms');
        $this->assertSame('ms', $gpanel->toArray()['leftYAxisLabel']);

        $this->assertSame(2, sizeof($gpanel->toArray()['targets']));
        $gpanel->addWarning('host0', 'service1', 'command2', 'perfLabel3%');
        $this->assertSame(5, sizeof($gpanel->toArray()['targets']));
        $this->assertSame(
            'host0&service1&command2&perfLabel3%&warn',
            $gpanel->toArray()['targets'][2]['measurement']
        );
        $this->assertSame(
            array(array('key'=>'type','operator'=>'=', 'value' => 'normal')),
            $gpanel->toArray()['targets'][2]['tags']
        );
        $this->assertSame(
            'select mean(value) from "host0&service1&command2&perfLabel3%&warn" where AND $timeFilter group by time($interval)',
            $gpanel->toArray()['targets'][3]['query']
        );
        $this->assertSame(
            array(array('key'=>'type','operator'=>'=', 'value' => 'min')),
            $gpanel->toArray()['targets'][3]['tags']
        );
        $this->assertSame('warn-max', $gpanel->toArray()['targets'][4]['alias']);
        $this->assertSame('#FFFC15', $gpanel->toArray()['aliasColors']['warn-max']);

        $this->assertSame(5, sizeof($gpanel->toArray()['targets']));
        $gpanel->addCritical('host&0', 'service1', 'command2', 'perfLabel3%');
        $this->assertSame(8, sizeof($gpanel->toArray()['targets']));
        $this->assertSame('#FF3727', $gpanel->toArray()['aliasColors']['crit']);

        $gpanel->setLinewidth(10);
        $this->assertSame(10, $gpanel->toArray()['linewidth']);

        $this->assertSame(0, sizeof($gpanel->toArray()['seriesOverrides']));
        $this->assertSame(8, sizeof($gpanel->toArray()['targets']));
        $gpanel->addDowntime('host0', 'service1', 'command2', 'perfLabel3');
        $this->assertSame(1, sizeof($gpanel->toArray()['seriesOverrides']));
        $this->assertSame(9, sizeof($gpanel->toArray()['targets']));
        $this->assertSame(
            array(array('key'=>'downtime','operator'=>'=', 'value' => '1')),
            $gpanel->toArray()['targets'][8]['tags']
        );
        $this->assertSame(
            array('lines' => true,'alias' => 'downtime','linewidth' => 3,'legend' => false,'fill' => 3),
            $gpanel->toArray()['seriesOverrides'][0]
        );

        $this->assertSame(1, sizeof($gpanel->toArray()['seriesOverrides']));
        $gpanel->fillBelowLine('foo', 1);
        $this->assertSame(2, sizeof($gpanel->toArray()['seriesOverrides']));
        $this->assertSame(
            array('alias' => 'foo', 'fill' => 1),
            $gpanel->toArray()['seriesOverrides'][1]
        );
    }
}
