<?php
namespace app\classes;

use app\interfaces\IHaveSpecialFields;
use yii\db\ActiveRecord;
use yii\db\Query;
use Yii;

class SpecialActiveRecord extends ActiveRecord implements IHaveSpecialFields
{
	private $_specialFields = [];

	public function __construct()
	{
		$this->initSpecialFieldsArray();
		parent::__construct();
	}

	public function __set($name, $value)
	{
		if (isset($this->_specialFields[$name]) || array_key_exists($name, $this->_specialFields)) {
			$this->_specialFields[$name] = $value;
			return;
		} else {
			parent::__set($name, $value);
		}
	}

	public function __get($name)
	{
		if (isset($this->_specialFields[$name]) || array_key_exists($name, $this->_specialFields)) {
			return $this->_specialFields[$name];
		}
		return parent::__get($name);
	}

	public function save($runValidation = true, $attributeNames = null)
	{
		if (count($this->_specialFields)) {
			$table = Yii::$app->params['specialTable'];

			foreach ($this->_specialFields as $key => $value) {
				$newCommand = Yii::$app->db->createCommand();

				if (isset($value)) {
					if (self::getIsSpecialPropInDb($key)) {
						$newCommand->update($table, [
							'value' => $value
						], [
							'modelName' => $this->className(),
							'property' => $key,
						])->execute();
					} else {
						$newCommand->insert($table, [
							'value' => $value,
							'modelName' => $this->className(),
							'property' => $key,
						])->execute();
					}
				} else {
					$newCommand->delete($table, [
						'modelName' => $this->className(),
						'property' => $key,
					])->execute();
				}
			}
		}

		return parent::save($runValidation, $attributeNames);
	}

	public function addSpecialField($name)
	{
		$this->_specialFields[$name] = null;
		return null;
	}

	private function initSpecialFieldsArray(){
		$query = (new Query())
			->select('property, value')
			->from(Yii::$app->params['specialTable'])
			->where(['modelName' => $this::className()])
			->all();
		foreach ($query as $row) {
			$this->_specialFields[$row['property']] = $row['value'];
		}
	}

	private function getIsSpecialPropInDb($name)
	{
		$table = Yii::$app->params['specialTable'];

		return (new Query())
			->from($table)
			->where("property = '$name'")
			->one();
	}

}