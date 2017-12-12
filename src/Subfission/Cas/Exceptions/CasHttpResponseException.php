<?php namespace Subfission\Cas\Exceptions;

use CAS_GracefullTerminationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class CasHttpResponseException extends HttpResponseException {
	protected $gracefulTerminationException = null;

	public function __construct( CAS_GracefullTerminationException $e ) {
		$this->gracefulTerminationException = $e;
		parent::__construct( Response::create() );
	}

	public function getException() {
		return $this->gracefulTerminationException;
	}
}