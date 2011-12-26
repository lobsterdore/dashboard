<?php
namespace Hoborg\Dashboard;

class Dashboard {

	protected $kernel = null;

	protected $widgetProvider = null;

	protected $widgets = array();

	public function __construct(Kernel $kernel, WidgetProvider $widgetProvider = null) {
		$this->kernel = $kernel;

		if (null == $widgetProvider) {
			$this->widgetProvider = new WidgetProvider();
		} else {
			$this->widgetProvider = $widgetProvider;
		}
	}

	public function render() {
		$config = $this->kernel->getConfig();
		$this->widgets = array();

		foreach ($config['widgets'] as $index => & $widget) {
			$w = $this->widgetProvider->createWidget($this->kernel, $widget)
					->bootstrap();
			$this->widgets[$index] = $w;
		}

		$head = $this->collectHeadData($this->widgets);

		$tpl = $config['template'] . '.phtml';
		return $this->renderTemplate($tpl);
	}

	protected function collectHeadData(array $widgets) {
		$head = '';
		$onceOnly = array();
		$onLoad = array();

		foreach ($widgets as $widget) {
			if (!$widget->hasHead()) {
				continue;
			}
			/*
			if (is_callable($widget['head'])) {
				$widget['head'] = $widget['head']();
			}

			foreach ($widget['head'] as $key => $values) {
				if ('onceOnly' === $key) {
					foreach ($values as $k => $v) {
						$onceOnly[$k] = $v;
					}
				}
				if ('onLoad' === $key) {
					foreach ($values as $k => $v) {
						$onLoad[$k] = $v;
					}
				}
				if ('always' === $key) {
					foreach ($values as $k => $v) {
						$head .= "\n" . $v;
					}
				}
			}
			*/
		}
		unset($widget);

		$head .= join("\n", $onceOnly);
		$head .= '<script type="text/javascript">'.
				'window.onload = function () {' .
				join("\n\n", $onLoad) . '};</script>';

		return $head;
	}

	protected function renderTemplate($templateName) {
		// include tpl file.
		$tpl = $this->kernel->findFileOnPath(
			$templateName,
			$this->kernel->getTemplatesPath()
		);

		if (!$tpl) {
			throw new Exception("Template $tpl `{$templateName}` not found");
		}

		ob_start();
		include $tpl;
		return ob_get_clean();
	}
}