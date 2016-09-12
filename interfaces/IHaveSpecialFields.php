<?php
namespace app\interfaces;
interface IHaveSpecialFields
{
	public function __construct();
	public function __set($name, $value);
	public function __get($name);
	public function addSpecialField($name);
}