<?php
namespace Hoborg\Dashboard\Client;

use Hoborg\Dashboard\Client\Graphite\Target;

class Graphite {

	protected $graphiteUrl = null;

	protected $options = array();

	public function __construct($graphiteUrl, array $options = array()) {
		$this->graphiteUrl = $graphiteUrl;
		$this->options = $options;
	}

	public function target($target, array $options) {
		$target = new Target($this, $target, array(), $options);
		return $target;
	}

	/**
	 * @deprecated
	 */
	public function getAvgTargetValue($target, $graphiteUrl, array $options, $nullAsZero = false) {
		$options['nullAsZero'] = (int) $nullAsZero;
		$target = new Target($this, $target, array(), $options);
		return $target->avg();
	}

	public function getTargetsData($graphiteUrl, array $targets, $from = '-5min', $to = 'now') {
		$url = $graphiteUrl . "/render?from={$from}&to={$to}&target=" . implode('&target=', $targets);

		return $this->getJsonData($url);
	}

	public function getTargetsStatisticalData($graphiteUrl, array $targets, $from = '-15min', $to = 'now',
			$avgSpan = 6, $nullAsZero = false) {
		$data = $this->getTargetsData($graphiteUrl, $targets, $from, $to);
		$statisticalData = array();

		foreach ($data as $target) {
			$min = PHP_INT_MAX;
			$max = -$min;
			$avg = 0;
			$avgSize = min($avgSpan, count($target['datapoints']));
			$avgIndex = count($target['datapoints']) - $avgSize;
			foreach ($target['datapoints'] as $i => $p) {
				if (null == $p[0]) {
					if ($nullAsZero) {
						$p[0] = 0;
					} else {
						$avgSize--;
						continue;
					}
				}
				$min = min($min, $p[0]);
				$max = max($max, $p[0]);
				if ($i >= $avgIndex) {
					$avg += $p[0];
				}
			}
			$avg = $avg / $avgSize;

			$statisticalData[] = array(
				'target' => $target['target'],
				'min' => $min,
				'max' => $max,
				'avg' => $avg,
			);
		}

		return $statisticalData;
	}

	public function getJsonData($url) {
		$jsonData = file_get_contents($url . '&format=json');
		$data = json_decode($jsonData, true);

		if (empty($data)) {
			return array();
		}

		return $data;
	}

	public function getGraphLink($target, array $functions, array $options) {
		$parts = array(
			'target' => $this->applyFunctionsToTarget($target, $functions),
		) + $options;

		array_walk($parts, function(&$val, $key) {
			$val = "{$key}=" . urlencode($val);
		});
		return $this->graphiteUrl . "/render?" . implode('&', $parts);
	}

	public function getData($target, array $functions, array $options) {
		$url = $this->getGraphLink($target, $functions, $options);
		return $this->getJsonData($url);
	}

	public function applyFunctionsToTarget($target, array $functions) {
		$origTarget = $target;

		$noParamFunctions = array('absolute', 'aliasByMetric', 'averageSeries', 'cactiStyle', 'cumulative', 'dashed',
				'derivative', 'drawAsInfinite', 'stacked', 'integral', 'keepLastValue', 'maxSeries', 'minSeries',
				'secondYAxis', 'sortByMaxima', 'sortByMinima', 'sortByName', 'sortByTotal', 'sumSeries');
		$singleStringParamFunctions = array('color', 'alias', 'cactiStyle', 'legendValue');
		$singleNumberParamFunctions = array('aliasByNode', 'alpha', 'averageAbove', 'averageBelow', 'currentAbove',
				'currentBelow', 'dashed', 'diffSeries', 'highestAverage', 'highestCurrent', 'highestMax', 'limit',
				'lineWidth', 'logarithm', 'lowestAverage', 'lowestCurrent', 'maximumAbove', 'maximumBelow',
				'minimumAbove', 'movingAverage', 'movingMedian', 'nPercentile', 'offset', 'removeAbovePercentile',
				'removeAboveValue', 'removeBelowPercentile', 'removeBelowValue', 'scale', 'transformNull');

		foreach ($functions as $function => $param) {
			if (in_array($function, $singleStringParamFunctions) && is_string($param)) {
				$target = "{$function}({$target},'{$param}')";
				continue;
			}

			if (in_array($function, $singleNumberParamFunctions)) {
				$target = "{$function}({$target},{$param})";
				continue;
			}

			if (in_array($function, $noParamFunctions)) {
				$target = "{$function}({$target})";
			}
		}

		return $target;
	}

}
