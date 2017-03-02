<?php

namespace Plume;

use Symfony\Component\HttpFoundation\Request as BaseRequest;

class Request
{
	private $request;

	public function getRequest()
	{
		if (empty($this->request)) {
			$this->request = BaseRequest::createFromGlobals();
		}

		return $this->request;
	}
}

?>
