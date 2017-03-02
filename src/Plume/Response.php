<?php

/***************************
 * *************************
 * 屏蔽掉Symfony的命名空间 *
 * *************************
 **************************/

namespace Plume;

use Symfony\Component\HttpFoundation\Response as BaseResponse;

class Response extends BaseResponse
{
	public function json($data, $options = null)
	{
		$this->setContent(json_encode($data, $options));
		$this->headers->set('Content-Type', 'application/json');
	}
}

?>
