<?php

namespace DK\Question;

class Controller
{
	public $input;
	public $valid;
	public $action;

	function addInput($in)
	{
		$this->input = $in;
	}

	function addValid($va)
	{
		$this->valid = $va;
	}

	function addAction($ac)
	{
		$this->action = $ac;
	}

	function run($output)
	{
		if ($this->valid) {
			if (!$this->valid->isValid($this->input)) {
				$output->onFailure($this->input, $this->valid);
				return;
			}
		}

		if ($this->action) {
			$this->action->execute($this->input);
		}

		$output->onSuccess($this->input, $this->action);
	}
}
