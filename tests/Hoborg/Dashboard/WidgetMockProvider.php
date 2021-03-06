<?php
namespace Hoborg\Dashboard;

class WidgetMockProvider extends WidgetProvider {

	protected $mock = null;

	public function injectMock($mock) {
		$this->mock = $mock;
	}

	public function createWidget(array $widget) {
		if (null !== $this->mock) {
			$mock = $this->mock;
			unset($this->mock);
			return $mock;
		}

		return new Widget($this->kernel, $widget);
	}
}