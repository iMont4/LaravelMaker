<?php

namespace Mont4\LaravelMaker\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ConstResource extends JsonResource
{
	private $translateNamespace;

	public function __construct($resource, $translateNamespace)
	{
		parent::__construct($resource);

		$this->translateNamespace = $translateNamespace;
	}

	/**
	 * Transform the resource into an array.
	 *
	 * @param  \Illuminate\Http\Request $request
	 * @return array
	 */
	public function toArray($request)
	{
		$data = [];
		foreach ($this->resource as $value) {
			$data[] = [
				'value' => $value,
				'label' => trans("$this->translateNamespace.$value"),
			];
		}

		return $data;
	}
}
